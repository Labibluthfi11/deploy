<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Absensi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Models\Notification;
use Illuminate\Support\Facades\Validator;
use App\Models\ActivityLog;
use App\Http\Resources\AbsensiResource;


class AbsensiController extends Controller
{
    private $workflowTemplates = [
    'produksi' => [
        'supervisor' => 'pending',
        'manager'    => 'pending',
        'hrga'       => 'pending',
    ],
    'office' => [
        'manager' => 'pending',
        'hrga'    => 'pending',
    ],
];

    private function determineResubmitLevel($rejectedBy, $workflowStatus)
    {
        if (!$rejectedBy || !$workflowStatus) {
            return 1;
        }

        $levelMap = [
            'yuli' => 1, 'supervisor' => 1, 'mas_yuli' => 1,
            'nu' => 2, 'manager' => 2, 'mas_nu' => 2,
            'nadya' => 3, 'hrga' => 3, 'mba_nadya' => 3,
        ];

        $rejectorLower = strtolower(trim($rejectedBy));
        $rejectorLower = str_replace([' ', '_', '-'], '', $rejectorLower);

        foreach ($levelMap as $key => $level) {
            if (strpos($rejectorLower, $key) !== false) {
                return $level;
            }
        }

        return 1;
    }

    private function resetWorkflowFromLevel($workflow, $startLevel, $employment)
    {
        $employment = strtolower($employment);

        if ($employment === 'freelance') {
            $roleToLevel = [
                'supervisor' => 1,
                'manager' => 2,
                'hrga' => 3,
            ];
        } else {
            $roleToLevel = [
                'manager' => 1,
                'hrga' => 2,
            ];
        }

        $resetWorkflow = [];
        foreach ($roleToLevel as $role => $level) {
            if ($level >= $startLevel) {
                $resetWorkflow[$role] = 'pending';
            } else {
                $resetWorkflow[$role] = $workflow[$role] ?? 'approved';
            }
        }

        return $resetWorkflow;
    }

    // 🆕 METHOD: Hitung keterlambatan
    private function calculateLateMinutes($checkInTime, bool $isWeekend = false): int
    {
        $checkIn = Carbon::parse($checkInTime);
        $standardTime = $checkIn->copy()->setTime(8, 0, 0);

        if ($checkIn->greaterThan($standardTime)) {
            return (int) abs($checkIn->diffInMinutes($standardTime));
        }

        return 0;
    }

    public function absenMasuk(Request $request)
{
    try {
        $request->validate([
            'foto' => 'required|image|max:2048',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'status' => 'required|in:hadir,sakit,izin',
            'is_mocked' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        $today = Carbon::today();

        // 🆕 CEK HARI LIBUR
        if ($request->status === 'hadir' && \App\Models\Holiday::isHoliday($today)) {
            $holiday = \App\Models\Holiday::where('holiday_date', $today->toDateString())->first();
            return response()->json([
                'message' => "Hari ini adalah hari libur ({$holiday->name}). Anda tidak dapat melakukan absen reguler. Silakan gunakan menu Absen Lembur jika Anda ditugaskan masuk."
            ], 403);
        }

        //  FIX #1: CEK SEMUA STATUS (termasuk REJECTED)
        $existingAbsensi = Absensi::where('user_id', $user->id)
            ->whereBetween('check_in_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->first();

        if ($existingAbsensi) {
            //  CASE 1: Izin APPROVED (Disetujui) → BLOKIR
            if (in_array($existingAbsensi->status_approval, ['approved', 'pending'])
                && in_array($existingAbsensi->tipe, ['sakit', 'izin'])) {

                $statusText = $existingAbsensi->status_approval == 'approved' ? 'Disetujui' : 'Sedang Diproses';

                return response()->json([
                    'message' => "Anda sudah mengajukan {$existingAbsensi->tipe} ({$statusText}). Tidak perlu absen masuk.",
                    'tipe' => $existingAbsensi->tipe,
                    'status_approval' => $existingAbsensi->status_approval
                ], 409);
            }

            //  CASE 2: Izin REJECTED → UPDATE jadi HADIR
            if ($existingAbsensi->status_approval == 'rejected'
                && in_array($existingAbsensi->tipe, ['sakit', 'izin'])) {

                $fotoPath = $request->file('foto')->store('absensi_foto', 'public');

                $lokasiMasuk = $request->lat . ',' . $request->lng; // ✅ FIX: Quote lurus
                $checkInTime = now();
                $isWeekend = Absensi::isWeekend($checkInTime);
                $lateMinutes = 0;

                if ($request->status === 'hadir') {
                    $kategori = $user->kategori_absensi;

                    // Organik dan Magang flexible time (tidak ada telat)
                    if ($kategori !== 'organik' && $kategori !== 'magang') {
                        $lateMinutes = $this->calculateLateMinutes($checkInTime, $isWeekend);
                    }
                }

                    $salaryData = Absensi::calculateSalary(
                    $lateMinutes,
                    'hadir',
                    null,
                    $isWeekend,
                    $checkInTime,
                    null,
                    $kategori
                );


                //  UPDATE record yang REJECTED jadi HADIR

                $existingAbsensi->update([
                    'check_in_at' => $checkInTime,
                    'foto_masuk' => $fotoPath,
                    'lokasi_masuk' => $lokasiMasuk,
                    'status' => 'hadir',
                    'tipe' => null,
                    'status_approval' => 'approved',
                    'keterangan_izin_sakit' => null,
                    'file_bukti' => null,
                    'late_minutes' => $lateMinutes,
                    'rounded_late_minutes' => $salaryData['rounded_late_minutes'],
                    'base_salary' => $salaryData['base_salary'],
                    'late_penalty' => $salaryData['late_penalty'],
                    'final_salary' => $salaryData['final_salary'],
                    'is_weekend_overtime' => false,
                    'is_mocked' => $request->boolean('is_mocked'),
                ]);


                $existingAbsensi->load('user');

                $existingAbsensi->foto_masuk_url = Storage::url($existingAbsensi->foto_masuk);

                ActivityLog::log('Absen Masuk (Update)', "User: {$user->name}", "Izin ditolak diubah jadi hadir.");

                return response()->json([

                    'message' => 'Absensi berhasil! Izin yang ditolak otomatis diubah jadi hadir.'
                                . ($isWeekend ? ' (Weekend - Gaji 2x Lipat)' : ''),
                    'data' => $existingAbsensi
                ], 200);
            }

            //  CASE 3: Udah Hadir Sebelumnya
            if ($existingAbsensi->status == 'hadir' && $existingAbsensi->check_in_at) {
                return response()->json([
                    'message' => 'Anda sudah absen masuk hari ini.'
                ], 409);
            }
        }

        //  CASE 4: Belum Ada Data → CREATE BARU (Normal Flow)
        $fotoPath = $request->file('foto')->store('absensi_foto', 'public');
        $lokasiMasuk = $request->lat . ',' . $request->lng; // ✅ FIX: Quote lurus

        $checkInTime = now();
        $employment = strtolower($user->work_location ?? 'office');
        $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];
        $isWeekend = Absensi::isWeekend($checkInTime);
        $lateMinutes = 0;

        if ($request->status === 'hadir') {
            $kategori = $user->kategori_absensi;

            // Organik dan Magang flexible time (tidak ada telat)
            if ($kategori !== 'organik' && $kategori !== 'magang') {
                $lateMinutes = $this->calculateLateMinutes($checkInTime, $isWeekend);
            }
        }

        $salaryData = Absensi::calculateSalary(
    $lateMinutes, 
    $request->status, 
    null, 
    $isWeekend,
    $checkInTime,  
    null,
    $kategori
);

        $absensi = Absensi::create([
            'user_id' => $user->id,
            'check_in_at' => $checkInTime,
            'foto_masuk' => $fotoPath,
            'lokasi_masuk' => $lokasiMasuk,
            'status' => $request->status,
            'tipe' => ($request->status == 'hadir') ? null : $request->status,
            'status_approval' => ($request->status == 'hadir') ? 'approved' : 'pending',
            'current_approval_level' => 1,
            'workflow_status' => $workflow,
            'late_minutes' => $lateMinutes,
            'rounded_late_minutes' => $salaryData['rounded_late_minutes'],
            'base_salary' => $salaryData['base_salary'],
            'late_penalty' => $salaryData['late_penalty'],
            'final_salary' => $salaryData['final_salary'],
            'is_weekend_overtime' => false,
        ]);


        $absensi->load('user');

        $absensi->foto_masuk_url = Storage::url($absensi->foto_masuk);

        ActivityLog::log('Absen Masuk', "User: {$user->name}", "Melakukan absen masuk status: {$request->status}");

        return response()->json([

            'message' => 'Absensi masuk berhasil' . ($isWeekend ? ' (Weekend - Gaji 2x Lipat)' : ''),
            'data' => $absensi
        ], 201);

    } catch (ValidationException $e) {
        return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        return response()->json(['message' => 'Terjadi kesalahan server. ' . (config('app.debug') ? $e->getMessage() : '')], 500);
    }
}

   public function absenPulang(Request $request)
    {
        try {
            $request->validate([
                'foto' => 'required|image|max:2048',
                'foto_2' => 'nullable|image|max:2048',
                'foto_3' => 'nullable|image|max:2048',
                'foto_4' => 'nullable|image|max:2048',
                'foto_5' => 'nullable|image|max:2048',
                'foto_6' => 'nullable|image|max:2048',
                'keterangan_goals' => 'nullable|string|max:2000',
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
                'tipe' => 'nullable|in:lembur,cuti,sakit,izin',
                'file_bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'keterangan_izin_sakit' => 'nullable|string|max:500',
                'is_mocked' => 'nullable|boolean',
            ]);

            $user = Auth::user();
            $today = Carbon::today();

            $absensi = Absensi::where('user_id', $user->id)
                ->whereBetween('check_in_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->whereIn('status_approval', ['pending', 'approved'])
                ->first();

            if (!$absensi) {
                return response()->json(['message' => 'Anda belum absen masuk hari ini.'], 400);
            }

            if ($absensi->tipe === 'sakit' || $absensi->tipe === 'izin') {
                return response()->json([
                    'message' => 'Anda tidak perlu absen pulang. Anda telah mengajukan ' . ucfirst($absensi->tipe) . ' hari ini.',
                    'tipe' => $absensi->tipe
                ], 400);
            }

            if ($absensi->check_out_at) {
                return response()->json(['message' => 'Anda sudah absen pulang hari ini.'], 409);
            }

            $checkInTimeParsed = \Carbon\Carbon::parse($absensi->check_in_at);

            // 🆕 FLAG PELANGGARAN DURASI
            $isKurang8Jam = false;

            // ATURAN WAKTU KERJA
            if ($request->tipe !== 'sakit' && $request->tipe !== 'izin') {
                $kategori = $user->kategori_absensi;

                $isFreelance = ($kategori === 'freelance');
                $isBorongan = ($kategori === 'borongan');
                $isMagang = ($kategori === 'magang');
                $isOrganik = ($kategori === 'organik');

                $currentTime = \Carbon\Carbon::now('Asia/Jakarta');
                $minutesWorked = $checkInTimeParsed->diffInMinutes($currentTime);
                $isAfterFivePM = $currentTime->hour >= 17;

                // 🆕 Cek apakah kurang dari 9 jam (8 kerja + 1 istirahat)
                if ($minutesWorked < 540) {
                    $isKurang8Jam = true;
                }

                // Freelance & Borongan boleh pulang asal sudah jam 5 sore (17:00)
                if ($isFreelance || $isBorongan) {
                    if (!$isAfterFivePM && $minutesWorked < 540) {
                        return response()->json([
                            'message' => 'Freelance/Borongan baru boleh pulang kalau sudah jam 17:00 (5 sore) atau sudah kerja 9 jam. Semangat bang!'
                        ], 400);
                    }
                } 
                // Organik & Magang: Dulu wajib 9 jam, sekarang IZINKAN pulang, TAPI tandai sebagai pelanggaran
                // else {
                //     if ($minutesWorked < 540) {
                //         return response()->json([
                //             'message' => 'Anda belum memenuhi waktu kerja wajib 9 jam (8 jam kerja + 1 jam istirahat). Organik dilarang pulang gasik!'
                //         ], 400);
                //     }
                // }
            }

            // CEK IZIN KELUAR RULES

            // 1. Cek ada pelanggaran hari ini?
            $hasPelanggaran = \App\Models\IzinKeluar::where('user_id', $user->id)
                ->whereBetween('waktu_keluar', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->where('is_pelanggaran', true)
                ->exists();

            if ($hasPelanggaran) {
                // Stripping salary jika freelance/borongan sudah dilakukan sewaktu 'endIzin' tapi untuk amannya kita juga tolak checkout ini
                return response()->json([
                    'message' => 'Absen Pulang Ditolak! Anda melanggar aturan jam keluar perusahaan hari ini.'
                ], 403);
            }

            // 2. Cek Izin Keluar yang masih berjalan
            $activeIzin = \App\Models\IzinKeluar::where('user_id', $user->id)
                ->where('status_izin', 'berjalan')
                ->first();

            if ($activeIzin) {
                if ($activeIzin->tipe_izin === 'tugas_kantor') {
                    // AUTO-CLOSE jika tugas kantor karena bisa dari luar kantor
                    $activeIzin->update([
                        'waktu_kembali' => now(),
                        'status_izin' => 'selesai',
                        'keterangan_kembali' => 'Ditutup Otomatis oleh Sistem saat Absen Pulang dari luar kantor.'
                    ]);
                } else {
                    // TOLAK JIKA MENDESAK belum ditutup
                    // Cek apakah sudah > 2 jam dan belum ditutup?
                    $diffMinutes = Carbon::parse($activeIzin->waktu_keluar)->diffInMinutes(now());
                    
                    if ($diffMinutes > 120) {
                        // Flag pelanggaran otomatis & Cabut gaji
                        $activeIzin->update([
                            'waktu_kembali' => now(),
                            'status_izin' => 'selesai',
                            'keterangan_kembali' => 'Ditutup Otomatis karena melanggar/melebihi batas 2 jam saat mau absen pulang.',
                            'is_pelanggaran' => true
                        ]);
                        
                        $kategori = $user->kategori_absensi;

                        if (in_array($kategori, ['freelance', 'borongan'])) {
                            $absensi->update([
                                'base_salary' => 0,
                                'final_salary' => 0,
                                'late_penalty' => 0
                            ]);
                        }

                        return response()->json([
                            'message' => 'Absen Pulang Ditolak karena Izin Keluar Mendesak telah terlewat lebih dari 2 jam (Pelanggaran Otomatis).'
                        ], 403);
                    } else {
                        // Belum 2 jam tapi belum nutup sesi
                        return response()->json([
                            'message' => 'Harap lampirkan bukti foto Izin Keluar terlebih dahulu untuk menyelesaikan sesi izin, baru Anda dapat melakukan absensi pulang.'
                        ], 400);
                    }
                }
            }

            $fotoPath = $request->file('foto')->store('absensi_foto', 'public');
            $fotoPath2 = $request->hasFile('foto_2') ? $request->file('foto_2')->store('absensi_foto', 'public') : null;
            $fotoPath3 = $request->hasFile('foto_3') ? $request->file('foto_3')->store('absensi_foto', 'public') : null;
            $fotoPath4 = $request->hasFile('foto_4') ? $request->file('foto_4')->store('absensi_foto', 'public') : null;
            $fotoPath5 = $request->hasFile('foto_5') ? $request->file('foto_5')->store('absensi_foto', 'public') : null;
            $fotoPath6 = $request->hasFile('foto_6') ? $request->file('foto_6')->store('absensi_foto', 'public') : null;
            $fileBuktiPath = $request->hasFile('file_bukti') ? $request->file('file_bukti')->store('bukti_sakit_izin', 'public') : $absensi->file_bukti;
            $lokasiPulang = $request->lat . ',' . $request->lng;
            $checkOutTime = \Carbon\Carbon::now('Asia/Jakarta');
            $isWeekend = Absensi::isWeekend($absensi->check_in_at);
            $lateMinutes = $absensi->late_minutes ?? 0;

            $kategori = $user->kategori_absensi;

            $updatedSalary = Absensi::calculateSalary(
                $lateMinutes,
                $absensi->status,
                $request->tipe,
                $isWeekend,
                $absensi->check_in_at,
                $checkOutTime,
                $kategori
            );

            if ($request->tipe === 'lembur') {
                $statusApproval = 'pending';
                $employment = strtolower($user->work_location ?? 'office');
                $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];
                $currentLevel = 1;
            } else {
                $statusApproval = 'approved';
                $workflow = $absensi->workflow_status;
                $currentLevel = $absensi->current_approval_level;
            }

                $absensi->update([
                'check_out_at' => $checkOutTime,
                'is_kurang_8_jam' => $isKurang8Jam, // 🆕 TAMBAHKAN INI
                'foto_pulang' => $fotoPath,
                'foto_pulang_2' => $fotoPath2,
                'foto_pulang_3' => $fotoPath3,
                'foto_pulang_4' => $fotoPath4,
                'foto_pulang_5' => $fotoPath5,
                'foto_pulang_6' => $fotoPath6,
                'keterangan_goals' => $request->keterangan_goals,
                'lokasi_pulang' => $lokasiPulang,
                'tipe' => $request->tipe,
                'file_bukti' => $fileBuktiPath,
                'keterangan_izin_sakit' => $request->keterangan_izin_sakit ?? $absensi->keterangan_izin_sakit,
                'status_approval' => $statusApproval,
                'workflow_status' => $workflow,
                'current_approval_level' => $currentLevel,
                'base_salary' => $updatedSalary['base_salary'],
                'late_penalty' => $updatedSalary['late_penalty'],
                'final_salary' => $updatedSalary['final_salary'],
                'is_mocked' => $request->boolean('is_mocked'),
            ]);

            $absensi->load('user');
            $absensi->foto_pulang_url = Storage::url($absensi->foto_pulang);
            if ($absensi->foto_pulang_2) $absensi->foto_pulang_2_url = Storage::url($absensi->foto_pulang_2);
            if ($absensi->foto_pulang_3) $absensi->foto_pulang_3_url = Storage::url($absensi->foto_pulang_3);
            if ($absensi->foto_pulang_4) $absensi->foto_pulang_4_url = Storage::url($absensi->foto_pulang_4);
            if ($absensi->foto_pulang_5) $absensi->foto_pulang_5_url = Storage::url($absensi->foto_pulang_5);
            if ($absensi->foto_pulang_6) $absensi->foto_pulang_6_url = Storage::url($absensi->foto_pulang_6);

            ActivityLog::log('Absen Pulang', "User: {$user->name}", "Melakukan absen pulang tipe: {$request->tipe}");

            return response()->json(['message' => 'Absensi pulang berhasil', 'data' => $absensi]);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan server. ' . (config('app.debug') ? $e->getMessage() : '')], 500);
        }
    }

public function meAbsensi(Request $request)
{
    try {
        $userId = Auth::id();
        
        // MATA-MATAIN URL LENGKAP DARI FLUTTER
        \Log::info("📡 [ME ABSENSI] URL Called: " . $request->fullUrl());
        \Log::info("📡 [ME ABSENSI] All Inputs:", $request->all());

        // 1. Inisialisasi Query Dasar
        $query = Absensi::with('user')->where('user_id', $userId);

        // 2. LOGIKA FILTER
        if ($request->filled('search_date')) {
            $searchDate = Carbon::parse($request->input('search_date'));
            $query->whereBetween('check_in_at', [$searchDate->copy()->startOfDay(), $searchDate->copy()->endOfDay()]);
        }
        elseif ($request->filled('month')) {
            $month = $request->input('month');
            $year = $request->input('year', Carbon::now('Asia/Jakarta')->year);
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = (clone $startOfMonth)->endOfMonth();
            $query->whereBetween('check_in_at', [$startOfMonth, $endOfMonth]);
        }
        else {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now('Asia/Jakarta')->subYears(2)->startOfDay();
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now('Asia/Jakarta')->addMonth()->endOfDay();
            $query->whereBetween('check_in_at', [$startDate, $endDate]);
        }

        // 3. PAGINATION
        $absensi = $query->orderBy('check_in_at', 'desc')->orderBy('id', 'desc')->paginate(30);

        // 4. MAPPING URL (Balik ke cara lama yang aman tapi rapi)
        $absensi->getCollection()->transform(function($item) {
            $item->foto_masuk_url = $item->foto_masuk ? Storage::url($item->foto_masuk) : null;
            $item->foto_pulang_url = $item->foto_pulang ? Storage::url($item->foto_pulang) : null;
            $item->foto_pulang_2_url = $item->foto_pulang_2 ? Storage::url($item->foto_pulang_2) : null;
            $item->foto_pulang_3_url = $item->foto_pulang_3 ? Storage::url($item->foto_pulang_3) : null;
            $item->foto_pulang_4_url = $item->foto_pulang_4 ? Storage::url($item->foto_pulang_4) : null;
            $item->foto_pulang_5_url = $item->foto_pulang_5 ? Storage::url($item->foto_pulang_5) : null;
            $item->foto_pulang_6_url = $item->foto_pulang_6 ? Storage::url($item->foto_pulang_6) : null;
            $item->file_bukti_url = $item->file_bukti ? Storage::url($item->file_bukti) : null;
            return $item;
        });

        \Log::info("✅ [ME ABSENSI] Found: " . $absensi->total() . " records for User: " . $userId);

        return response()->json($absensi);

    } catch (\Exception $e) {
        \Log::error("❌ [ME ABSENSI] Error: " . $e->getMessage());
        return response()->json([
            'success' => false, 
            'message' => 'Terjadi kesalahan server. ' . (config('app.debug') ? $e->getMessage() : '')
        ], 500);
    }
}

    public function absenSakit(Request $request)
    {
        return $this->handlePengajuanIzinSakit($request, 'sakit');
    }

    public function absenIzin(Request $request)
    {
        $user = Auth::user();
        $submissionType = $request->catatan_admin ?? null;

        // Organik tidak boleh ngajuin izin biasa — harus lewat cuti
        if ($user->employment_type === 'organik') {
            // Kalau submission_type adalah jenis cuti yang valid, lanjutkan
            $validCutiTypes = [
                'cuti_tahunan', 'cuti_melahirkan', 'cuti_keguguran',
                'cuti_haji', 'cuti_umroh', 'cuti_menikah', 'cuti_khitanan',
                'cuti_baptis', 'cuti_meninggal', 'change_off', 'unpaid_leave'
            ];
            if (!$submissionType || !in_array($submissionType, $validCutiTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan organik tidak bisa mengajukan izin biasa. Gunakan fitur Cuti untuk pengajuan izin.'
                ], 422);
            }
        }

        return $this->handlePengajuanIzinSakit($request, 'izin');
    }

    /**
     * Helper Unified logic for Sick and Leave requests
     */
    private function handlePengajuanIzinSakit(Request $request, string $defaultStatus)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'file_bukti' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'keterangan_izin_sakit' => 'required|string|max:500',
                'status' => 'nullable|in:sakit,izin',
                'start_date' => 'nullable|date|after_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'catatan_admin' => 'nullable|string|max:255',
                'jam_pulang_rencana' => 'nullable|date_format:H:i',
            ]);

            $user = Auth::user();
            $status = $request->status ?? $defaultStatus;
            $submissionType = $request->catatan_admin ?? null; // Pastikan ini di-assign sebelum validasi
            $jamPulangRencana = $request->jam_pulang_rencana;
            
            // 🆕 TAMBAH VALIDASI: Tidak boleh izin/cuti jika sudah absen masuk (kecuali lembur & izin_pulang_cepat)
            if ($status !== 'lembur' && $submissionType !== 'izin_pulang_cepat') {
                $today = \Carbon\Carbon::today();
                $hasCheckInToday = Absensi::where('user_id', $user->id)
                    ->whereDate('check_in_at', $today)
                    ->where('status', 'hadir')
                    ->exists();

                if ($hasCheckInToday) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda tidak bisa mengajukan izin atau cuti karena Anda sudah melakukan absen masuk hari ini.'
                    ], 422);
                }
            }

            // Ketentuan per jenis cuti
            $cutiConfig = [
                'cuti_tahunan'    => ['maxDays' => 12,  'potongJatah' => true,  'gratisHari' => 0],
                'cuti_melahirkan' => ['maxDays' => 90,  'potongJatah' => false, 'gratisHari' => 0],
                'cuti_keguguran'  => ['maxDays' => 45,  'potongJatah' => false, 'gratisHari' => 0],
                'cuti_haji'       => ['maxDays' => 60,  'potongJatah' => true,  'gratisHari' => 14],
                'cuti_umroh'      => ['maxDays' => 60,  'potongJatah' => true,  'gratisHari' => 12],
                'cuti_menikah'    => ['maxDays' => 3,   'potongJatah' => false, 'gratisHari' => 0],
                'cuti_khitanan'   => ['maxDays' => 2,   'potongJatah' => false, 'gratisHari' => 0],
                'cuti_baptis'     => ['maxDays' => 2,   'potongJatah' => false, 'gratisHari' => 0],
                'cuti_meninggal'  => ['maxDays' => 2,   'potongJatah' => false, 'gratisHari' => 0],
                'change_off'      => ['maxDays' => 1,   'potongJatah' => false, 'gratisHari' => 0],
                'unpaid_leave'    => ['maxDays' => 30,  'potongJatah' => false, 'gratisHari' => 0],
            ];

            $startDateInput = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::today()->startOfDay();
            $endDateInput = $request->end_date ? Carbon::parse($request->end_date)->startOfDay() : $startDateInput->copy()->startOfDay();

            // Saring hari kerja efektif (Opsi 2 - Backend Pintar)
            $calendarCutiTypes = ['cuti_melahirkan', 'cuti_keguguran', 'unpaid_leave'];
            $isCalendarLeave = in_array($submissionType, $calendarCutiTypes);

            $validDates = [];
            $tempDate = $startDateInput->copy();
            while ($tempDate->lte($endDateInput)) {
                $isWeekend = Absensi::isWeekend($tempDate);
                $isHoliday = \App\Models\Holiday::isHoliday($tempDate);

                if ($isCalendarLeave || (!$isWeekend && !$isHoliday)) {
                    $validDates[] = $tempDate->copy();
                }
                $tempDate->addDay();
            }

            if (empty($validDates)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Tidak ada hari kerja efektif pada rentang tanggal yang dipilih.'], 422);
            }

            $startDate = $validDates[0];
            $endDate = end($validDates);
            $totalDays = count($validDates);

            // Validasi maxDays per jenis cuti
            $maxDays = 30;
            $gratisHari = 0;
            $potongJatah = false;
            if ($submissionType && isset($cutiConfig[$submissionType])) {
                $maxDays = $cutiConfig[$submissionType]['maxDays'];
                $gratisHari = $cutiConfig[$submissionType]['gratisHari'];
                $potongJatah = $cutiConfig[$submissionType]['potongJatah'];
            }

            if ($totalDays > $maxDays) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => "Pengajuan {$submissionType} maksimal {$maxDays} hari."], 422);
            }

            // Hitung hari potong cuti tahunan (haji/umroh ada gratis N hari)
            $hariPotongCuti = 0;
            $hariUnpaid = 0;
            if ($submissionType && $gratisHari > 0 && $totalDays > $gratisHari) {
                $hariMelebihiGratis = $totalDays - $gratisHari;
                $sisaCuti = $user->sisa_cuti ?? 0;
                if ($sisaCuti >= $hariMelebihiGratis) {
                    $hariPotongCuti = $hariMelebihiGratis;
                } else {
                    $hariPotongCuti = $sisaCuti;
                    $hariUnpaid = $hariMelebihiGratis - $sisaCuti;
                }
            } elseif ($potongJatah && $gratisHari === 0) {
                $hariPotongCuti = $totalDays;
                $sisaCuti = $user->sisa_cuti ?? 0;
                if ($sisaCuti < $hariPotongCuti) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "Sisa cuti tidak cukup. Sisa: {$sisaCuti} hari, Dibutuhkan: {$hariPotongCuti} hari."], 422);
                }
            }

            // Check overlap
            $existingAbsensi = Absensi::where('user_id', $user->id)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('check_in_at', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('check_in_at', '<=', $startDate)->where('end_date', '>=', $endDate);
                          });
                })
                ->whereIn('status_approval', ['pending', 'approved', 'rejected'])
                ->first();

            if ($existingAbsensi) {
                DB::rollBack();
                $conflictDate = Carbon::parse($existingAbsensi->check_in_at)->format('d/m/Y');
                if ($existingAbsensi->status_approval == 'rejected') {
                    return response()->json([
                        'success' => false,
                        'message' => "Anda sudah memiliki pengajuan {$existingAbsensi->tipe} yang DITOLAK pada tanggal {$conflictDate}. Silakan gunakan tombol 'Ajukan Ulang'.",
                        'rejected_id' => $existingAbsensi->id,
                        'action' => 'use_resubmit'
                    ], 409);
                }
                return response()->json(['success' => false, 'message' => "Anda sudah memiliki pengajuan {$existingAbsensi->tipe} pada tanggal {$conflictDate}."], 409);
            }

            $fileBuktiPath = $request->file('file_bukti')->store('bukti_sakit_izin', 'public');
            $employment = strtolower($user->work_location ?? 'office');
            $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];

            // Cek apakah sudah ada record absensi untuk tanggal ini
            $existingTodayAbsensi = Absensi::where('user_id', $user->id)
                ->whereDate('check_in_at', $startDate)
                ->first();

            if ($existingTodayAbsensi && $submissionType === 'izin_pulang_cepat') {
                $parentAbsensi = $existingTodayAbsensi;
                $parentAbsensi->update([
                    'status' => $status,
                    'tipe' => $status,
                    'status_approval' => 'pending',
                    'file_bukti' => $fileBuktiPath,
                    'keterangan_izin_sakit' => $request->keterangan_izin_sakit,
                    'submission_type' => $submissionType,
                    'catatan_admin' => $request->catatan_admin,
                    'jam_pulang_rencana' => $jamPulangRencana,
                    'workflow_status' => $workflow,
                    'current_approval_level' => 1,
                ]);
            } else {
                $parentAbsensi = Absensi::create([
                    'hari_potong_cuti' => $hariPotongCuti,
                    'hari_unpaid' => $hariUnpaid,
                    'user_id' => $user->id,
                    'check_in_at' => $startDate,
                    'end_date' => $endDate,
                    'total_days' => $totalDays,
                    'status' => $status,
                    'tipe' => $status,
                    'status_approval' => 'pending',
                    'file_bukti' => $fileBuktiPath,
                    'keterangan_izin_sakit' => $request->keterangan_izin_sakit,
                    'submission_type' => $submissionType,
                    'catatan_admin' => $request->catatan_admin,
                    'jam_pulang_rencana' => $jamPulangRencana,
                    'workflow_status' => $workflow,
                    'current_approval_level' => 1,
                    'late_minutes' => 0,
                    'base_salary' => 0,
                    'late_penalty' => 0,
                    'final_salary' => 0,
                ]);
            }

            // Bulk Insert Children
            $childRecords = [];
            foreach ($validDates as $index => $date) {
                if ($index > 0) { // Lewati hari pertama (induk)
                    $childRecords[] = [
                        'user_id' => $user->id,
                        'parent_id' => $parentAbsensi->id,
                        'check_in_at' => $date->copy()->setTime(8, 0, 0)->toDateTimeString(),
                        'status' => $status,
                        'tipe' => $status,
                        'submission_type' => $submissionType,
                        'status_approval' => 'pending',
                        'file_bukti' => $fileBuktiPath,
                        'keterangan_izin_sakit' => $request->keterangan_izin_sakit,
                        'workflow_status' => json_encode($workflow),
                        'current_approval_level' => 1,
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                }
            }

            if (!empty($childRecords)) {
                Absensi::insert($childRecords);
            }

            Notification::create([
                'user_id' => $user->id,
                'title' => "Pengajuan " . ucfirst($status) . " Berhasil",
                'message' => "Pengajuan {$status} untuk {$totalDays} hari telah diajukan dan menunggu approval.",
                'type' => $status . '_submitted',
                'target_page' => '/' . $status . '_detail',
                'target_id' => $parentAbsensi->id,
            ]);

            DB::commit();

            // 🔥 Potongan sisa_cuti TIDAK lagi terjadi di sini. Jatah cuti baru dipotong
            // saat admin approve final (lihat ApprovalController). Nilai hari_potong_cuti
            // sudah tersimpan di row Absensi di atas, tinggal dipakai nanti pas approve.
            if ($hariPotongCuti > 0 || $hariUnpaid > 0) {
                Log::info('📝 [CUTI] Pengajuan tercatat, menunggu approval', [
                    'user' => $user->name,
                    'submission_type' => $submissionType,
                    'hari_potong_cuti_rencana' => $hariPotongCuti,
                    'hari_unpaid' => $hariUnpaid,
                ]);
            }

            $parentAbsensi->load('user');
            $parentAbsensi->file_bukti_url = Storage::url($parentAbsensi->file_bukti);

            return response()->json([
                'success' => true,
                'message' => "Pengajuan {$status} berhasil diajukan untuk {$totalDays} hari." . ($hariUnpaid > 0 ? " ({$hariPotongCuti} hari potong cuti, {$hariUnpaid} hari unpaid)" : ""),
                'data' => $parentAbsensi,
                'summary' => [
                    'total_days' => $totalDays,
                    'total_records' => 1 + count($childRecords),
                    'parent_id' => $parentAbsensi->id,
                    'date_range' => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($fileBuktiPath)) Storage::disk('public')->delete($fileBuktiPath);
            \Log::error("❌ [API {$defaultStatus}] Error: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan server. ' . (config('app.debug') ? $e->getMessage() : '')
            ], 500);
        }
    }

    // Resubmit methods tetap sama...
    public function absenLembur(Request $request)
    {
        try {
            $request->validate([
                'jam_mulai'     => 'required|date_format:H:i',
                'jam_selesai'   => 'required|date_format:H:i',
                'istirahat'     => 'required|boolean',
                'keterangan'    => 'required|string|max:500',
                'keterangan_goals' => 'nullable|string|max:2000',
                'foto'          => 'required|image|max:2048',
                'foto_2'        => 'nullable|image|max:2048',
                'foto_3'        => 'nullable|image|max:2048',
                'foto_4'        => 'nullable|image|max:2048',
                'foto_5'        => 'nullable|image|max:2048',
                'foto_6'        => 'nullable|image|max:2048',
                'lat'           => 'required|numeric',
                'lng'           => 'required|numeric',
                'is_weekend'    => 'nullable|boolean', // 🆕 Parameter baru dari Flutter
                'is_mocked'     => 'nullable|boolean',
            ]);

            $jamMulai = Carbon::createFromFormat('H:i', $request->jam_mulai);
            $jamSelesai = Carbon::createFromFormat('H:i', $request->jam_selesai);
            
            if ($jamSelesai->lte($jamMulai)) {
                return response()->json(['success' => false, 'message' => 'Jam selesai harus lebih dari jam mulai.'], 422);
            }

            // ✅ FIX SECURITY #2: Batas Maksimal Lembur 5 Jam
            if ($jamMulai->diffInHours($jamSelesai) > 5) {
                return response()->json(['success' => false, 'message' => 'Durasi lembur tidak wajar (Maksimal 5 jam sehari). Harap hubungi HR untuk lembur khusus.'], 422);
            }

            $user = Auth::user();
            $today = Carbon::today();

            $absensi = Absensi::where('user_id', $user->id)
                ->whereBetween('check_in_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->whereIn('status_approval', ['pending', 'approved'])
                ->first();

            if (!$absensi) {
                return response()->json(['success' => false, 'message' => 'Anda belum absen masuk hari ini.'], 400);
            }

            if ($absensi->tipe === 'sakit' || $absensi->tipe === 'izin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat mengajukan lembur karena Anda sudah mengajukan ' . ucfirst($absensi->tipe) . ' hari ini.'
                ], 400);
            }

            // NOTE: check_out_at check dihapus — lembur sekarang independen dari absen pulang

            $fotoPath = $request->file('foto')->store('absensi_foto', 'public');
            $fotoPath2 = $request->hasFile('foto_2') ? $request->file('foto_2')->store('absensi_foto', 'public') : null;
            $fotoPath3 = $request->hasFile('foto_3') ? $request->file('foto_3')->store('absensi_foto', 'public') : null;
            $fotoPath4 = $request->hasFile('foto_4') ? $request->file('foto_4')->store('absensi_foto', 'public') : null;
            $fotoPath5 = $request->hasFile('foto_5') ? $request->file('foto_5')->store('absensi_foto', 'public') : null;
            $fotoPath6 = $request->hasFile('foto_6') ? $request->file('foto_6')->store('absensi_foto', 'public') : null;
            $lokasiPulang = $request->lat . ',' . $request->lng;

            $lemburStart = Carbon::parse($today->format('Y-m-d') . ' ' . $request->jam_mulai);
            $lemburEnd = Carbon::parse($today->format('Y-m-d') . ' ' . $request->jam_selesai);

            // 🆕 CEK WEEKEND (Dari request atau auto-detect)
            $isWeekendOvertime = $request->boolean('is_weekend', Absensi::isWeekend($lemburStart));

            // 🆕 HITUNG LEMBUR DENGAN MULTIPLIER WEEKEND
            $overtimeData = Absensi::calculateOvertimeFromInput(
                $lemburStart,
                $lemburEnd,
                $request->istirahat,
                $isWeekendOvertime // 🔥 Pass parameter weekend
            );


            $kategori = $user->kategori_absensi;
            $lateMinutes = $absensi->late_minutes ?? 0; // ✅ FIX: Ambil data telat dari absen masuk
            $salaryData = Absensi::calculateSalary(
                $lateMinutes,
                $absensi->status,
                'lembur',
                $isWeekendOvertime,
                $absensi->check_in_at,
                now(),
                $kategori
            );

            $employment = strtolower($user->work_location ?? 'office');
            $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];

            $absensi->update([
                'check_out_at'          => $absensi->check_out_at ?? now(),
                'foto_pulang'           => $fotoPath,
                'foto_pulang_2'         => $fotoPath2,
                'foto_pulang_3'         => $fotoPath3,
                'foto_pulang_4'         => $fotoPath4,
                'foto_pulang_5'         => $fotoPath5,
                'foto_pulang_6'         => $fotoPath6,
                'lokasi_pulang'         => $lokasiPulang,
                'tipe'                  => 'lembur',
                'status_approval'       => 'pending',
                'workflow_status'       => $workflow,
                'current_approval_level' => 1,
                'lembur_start'          => $lemburStart,
                'lembur_end'            => $lemburEnd,
                'lembur_rest'           => $request->istirahat,
                'lembur_keterangan'     => $request->keterangan,
                'keterangan_goals'      => $request->keterangan_goals,
                'overtime_minutes'      => $overtimeData['minutes'],
                'overtime_pay'          => $overtimeData['pay'],
                'base_salary'           => $absensi->base_salary ?? $salaryData['base_salary'],
                'late_penalty'          => $absensi->late_penalty ?? $salaryData['late_penalty'],
                'final_salary'          => $absensi->final_salary ?? $salaryData['final_salary'],
                'is_weekend_overtime'   => $isWeekendOvertime, // 🆕 Simpan flag weekend
                'is_mocked'             => $request->boolean('is_mocked'),
            ]);

            $absensi->load('user');
            $absensi->foto_pulang_url = Storage::url($absensi->foto_pulang);
            if ($absensi->foto_pulang_2) $absensi->foto_pulang_2_url = Storage::url($absensi->foto_pulang_2);
            if ($absensi->foto_pulang_3) $absensi->foto_pulang_3_url = Storage::url($absensi->foto_pulang_3);
            if ($absensi->foto_pulang_4) $absensi->foto_pulang_4_url = Storage::url($absensi->foto_pulang_4);
            if ($absensi->foto_pulang_5) $absensi->foto_pulang_5_url = Storage::url($absensi->foto_pulang_5);
            if ($absensi->foto_pulang_6) $absensi->foto_pulang_6_url = Storage::url($absensi->foto_pulang_6);

            return response()->json([
                'success' => true,
                'message' => 'Absensi lembur berhasil diajukan' . ($isWeekendOvertime ? ' (Weekend - Rate 2x)' : ''),
                'data' => $absensi,
                'overtime_info' => [
                    'minutes' => $overtimeData['minutes'],
                    'pay' => 'Rp ' . number_format($overtimeData['pay'], 0, ',', '.'),
                    'formatted_duration' => floor($overtimeData['minutes'] / 60) . ' jam ' . ($overtimeData['minutes'] % 60) . ' menit',
                    'is_weekend' => $isWeekendOvertime // 🆕 Info ke client
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Submit Lembur Independen (tanpa foto selfie, lat, lng)
     * Flutter method: submitLembur()
     */
    public function submitLembur(Request $request)
    {
        try {
            $request->validate([
                'jam_mulai'        => 'required|date_format:H:i',
                'jam_selesai'      => 'required|date_format:H:i',
                'istirahat'        => 'required|boolean',
                'keterangan'       => 'required|string|max:500',
                'keterangan_goals' => 'nullable|string|max:2000',
                'foto_bukti'       => 'required|image|max:2048',
                'foto_2'           => 'nullable|image|max:2048',
                'foto_3'           => 'nullable|image|max:2048',
                'foto_4'           => 'nullable|image|max:2048',
                'foto_5'           => 'nullable|image|max:2048',
                'is_weekend'       => 'nullable|boolean',
                'is_mocked'        => 'nullable|boolean',
            ]);

            $jamMulai = Carbon::createFromFormat('H:i', $request->jam_mulai);
            $jamSelesai = Carbon::createFromFormat('H:i', $request->jam_selesai);
            
            if ($jamSelesai->lte($jamMulai)) {
                return response()->json(['success' => false, 'message' => 'Jam selesai harus lebih dari jam mulai.'], 422);
            }

            // ✅ FIX SECURITY #2: Batas Maksimal Lembur 5 Jam
            if ($jamMulai->diffInHours($jamSelesai) > 5) {
                return response()->json(['success' => false, 'message' => 'Durasi lembur tidak wajar (Maksimal 5 jam sehari). Harap hubungi HR untuk lembur khusus.'], 422);
            }

            $user = Auth::user();
            $today = Carbon::today();

            // Cari absensi hari ini (harus sudah absen masuk)
            $absensi = Absensi::where('user_id', $user->id)
                ->whereBetween('check_in_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->whereIn('status_approval', ['pending', 'approved'])
                ->first();

            if (!$absensi) {
                return response()->json(['success' => false, 'message' => 'Anda belum absen masuk hari ini.'], 400);
            }

            if ($absensi->tipe === 'sakit' || $absensi->tipe === 'izin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat mengajukan lembur karena Anda sudah mengajukan ' . ucfirst($absensi->tipe) . ' hari ini.'
                ], 400);
            }

            // NOTE: Tidak ada check check_out_at — lembur independen dari absen pulang

            // Upload foto bukti (opsional)
            $fotoBukti = $request->hasFile('foto_bukti') ? $request->file('foto_bukti')->store('absensi_foto', 'public') : null;
            $fotoPath2 = $request->hasFile('foto_2') ? $request->file('foto_2')->store('absensi_foto', 'public') : null;
            $fotoPath3 = $request->hasFile('foto_3') ? $request->file('foto_3')->store('absensi_foto', 'public') : null;
            $fotoPath4 = $request->hasFile('foto_4') ? $request->file('foto_4')->store('absensi_foto', 'public') : null;
            $fotoPath5 = $request->hasFile('foto_5') ? $request->file('foto_5')->store('absensi_foto', 'public') : null;

            $lemburStart = Carbon::parse($today->format('Y-m-d') . ' ' . $request->jam_mulai);
            $lemburEnd = Carbon::parse($today->format('Y-m-d') . ' ' . $request->jam_selesai);

            $isWeekendOvertime = $request->boolean('is_weekend', Absensi::isWeekend($lemburStart));

            $overtimeData = Absensi::calculateOvertimeFromInput(
                $lemburStart,
                $lemburEnd,
                $request->istirahat,
                $isWeekendOvertime
            );

            $kategori = $user->kategori_absensi;
            $lateMinutes = $absensi->late_minutes ?? 0; // ✅ FIX: Ambil data telat dari absen masuk
            $salaryData = Absensi::calculateSalary(
                $lateMinutes,
                $absensi->status,
                'lembur',
                $isWeekendOvertime,
                $absensi->check_in_at,
                now(),
                $kategori
            );

            $employment = strtolower($user->work_location ?? 'office');
            $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];

            $absensi->update([
                'check_out_at'          => $absensi->check_out_at ?? now(),
                'foto_pulang'           => $fotoBukti ?? $absensi->foto_pulang,
                'foto_pulang_2'         => $fotoPath2 ?? $absensi->foto_pulang_2,
                'foto_pulang_3'         => $fotoPath3 ?? $absensi->foto_pulang_3,
                'foto_pulang_4'         => $fotoPath4 ?? $absensi->foto_pulang_4,
                'foto_pulang_5'         => $fotoPath5 ?? $absensi->foto_pulang_5,
                'tipe'                  => 'lembur',
                'status_approval'       => 'pending',
                'workflow_status'       => $workflow,
                'current_approval_level' => 1,
                'lembur_start'          => $lemburStart,
                'lembur_end'            => $lemburEnd,
                'lembur_rest'           => $request->istirahat,
                'lembur_keterangan'     => $request->keterangan,
                'keterangan_goals'      => $request->keterangan_goals,
                'overtime_minutes'      => $overtimeData['minutes'],
                'overtime_pay'          => $overtimeData['pay'],
                'base_salary'           => $absensi->base_salary ?? $salaryData['base_salary'],
                'late_penalty'          => $absensi->late_penalty ?? $salaryData['late_penalty'],
                'final_salary'          => $absensi->final_salary ?? $salaryData['final_salary'],
                'is_weekend_overtime'   => $isWeekendOvertime,
                'is_mocked'             => $request->boolean('is_mocked'),
            ]);

            $absensi->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan lembur berhasil' . ($isWeekendOvertime ? ' (Weekend - Rate 2x)' : ''),
                'data' => $absensi,
                'overtime_info' => [
                    'minutes' => $overtimeData['minutes'],
                    'pay' => 'Rp ' . number_format($overtimeData['pay'], 0, ',', '.'),
                    'formatted_duration' => floor($overtimeData['minutes'] / 60) . ' jam ' . ($overtimeData['minutes'] % 60) . ' menit',
                    'is_weekend' => $isWeekendOvertime
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Unified Resubmit API Endpoint
     */
    public function resubmit(Request $request, $id)
    {
        // 1. Coba cari di Absensi (Sakit, Izin, Lembur, Telat)
        $absensi = Absensi::find($id);
        if ($absensi) {
            if ($absensi->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }

            $type = strtolower($absensi->tipe ?? $absensi->status ?? 'absensi');
            if (in_array($type, ['sakit', 'izin'])) {
                return $this->handleResubmitIzinSakit($request, $id, $type);
            } elseif ($type === 'lembur') {
                return $this->handleResubmitLembur($request, $id);
            } elseif ($type === 'telat') {
                // Bisa pakai handler izin/sakit karena tabelnya sama
                return $this->handleResubmitIzinSakit($request, $id, 'telat');
            }
        }

        // 2. Jika tidak ada di Absensi, coba cari di IzinKeluar
        $izinKeluar = \App\Models\IzinKeluar::find($id);
        if ($izinKeluar) {
            if ($izinKeluar->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
            return $this->handleResubmitIzinKeluar($request, $id);
        }

        // 3. Coba cari di ScheduledLembur (Lembur Terjadwal)
        $scheduledLembur = \App\Models\ScheduledLembur::find($id);
        if ($scheduledLembur) {
            if ($scheduledLembur->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
            return $this->handleResubmitScheduledLembur($request, $id);
        }

        return response()->json(['success' => false, 'message' => 'Data tidak ditemukan atau tipe tidak didukung.'], 404);
    }

    /**
     * Handler for Izin Keluar Resubmission
     */
    private function handleResubmitIzinKeluar(Request $request, $id)
    {
        $izin = \App\Models\IzinKeluar::findOrFail($id);
        
        if (!in_array($izin->status_approval, ['rejected', 'ditolak'])) {
            return response()->json(['success' => false, 'message' => 'Hanya pengajuan yang ditolak yang bisa diajukan ulang.'], 409);
        }

        $izin->update([
            'status_approval' => 'pending',
            'current_approval_level' => 1,
            'rejected_by' => null,
            'rejected_at' => null,
            'catatan_admin' => null,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Pengajuan Izin Keluar berhasil diajukan ulang.'], 200);
    }

    /**
     * Handler for Scheduled Lembur Resubmission
     */
    private function handleResubmitScheduledLembur(Request $request, $id)
    {
        $lembur = \App\Models\ScheduledLembur::findOrFail($id);

        if (!in_array($lembur->status, ['rejected', 'ditolak'])) {
            return response()->json(['success' => false, 'message' => 'Hanya pengajuan yang ditolak yang bisa diajukan ulang.'], 409);
        }

        $request->validate([
            'tanggal_lembur' => 'required|date|after:today',
            'keterangan'     => 'required|max:500',
            'foto_bukti'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        $fotoPath = $lembur->foto_bukti;
        if ($request->hasFile('foto_bukti')) {
            if ($lembur->foto_bukti && Storage::disk('public')->exists($lembur->foto_bukti)) {
                Storage::disk('public')->delete($lembur->foto_bukti);
            }
            $fotoPath = $request->file('foto_bukti')->store('absensi_foto', 'public');
        }

        $lembur->update([
            'tanggal_lembur' => $request->tanggal_lembur,
            'keterangan'     => $request->keterangan,
            'foto_bukti'     => $fotoPath,
            'status'         => 'pending',
            'current_approval_level' => 1,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Lembur terjadwal berhasil diajukan ulang.', 'data' => $lembur], 200);
    }


    /**
     * Helper for Lembur resubmission logic (extracted from resubmitLembur)
     */
    private function handleResubmitLembur(Request $request, $id)
    {
        // ... (Logika lama resubmitLembur pindah ke sini)
    }

    // ... (Fungsi resubmitSakit dan resubmitIzin bisa dihapus/dibiarkan sebagai alias)
    public function resubmitSakit(Request $request, $id) { return $this->resubmit($request, $id); }
    public function resubmitIzin(Request $request, $id) { return $this->resubmit($request, $id); }
    public function resubmitLembur(Request $request, $id) { return $this->resubmit($request, $id); }

    /**
     * Helper Unified logic for Resubmitting Sick and Leave requests
     */
    private function handleResubmitIzinSakit(Request $request, $id, string $type)
    {
        DB::beginTransaction();
        try {
            if (!is_numeric($id) || $id <= 0) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'ID tidak valid.'], 400);
            }

            // Validasi tanggal: Boleh mundur karena ini revisi
            $request->validate([
                'file_bukti' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'keterangan_izin_sakit' => 'nullable|string|max:500',
                'catatan' => 'nullable|string|max:500', // support both keys
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $absensi = Absensi::find($id);
            if (!$absensi || (int) $absensi->user_id !== (int) Auth::id()) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Record tidak ditemukan atau akses ditolak.'], 404);
            }

            if (!in_array($absensi->status_approval, ['rejected', 'ditolak'])) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Hanya pengajuan yang ditolak yang bisa diajukan ulang.'], 409);
            }

            // Hitung tanggal baru jika diberikan
            $startDateInput = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::parse($absensi->check_in_at)->startOfDay();
            $endDateInput = $request->end_date ? Carbon::parse($request->end_date)->startOfDay() : ($absensi->end_date ? Carbon::parse($absensi->end_date)->startOfDay() : $startDateInput->copy());

            // Saring hari kerja efektif (Opsi 2 - Backend Pintar)
            $calendarCutiTypes = ['cuti_melahirkan', 'cuti_keguguran', 'unpaid_leave'];
            $isCalendarLeave = in_array($absensi->submission_type, $calendarCutiTypes);

            $validDates = [];
            $tempDate = $startDateInput->copy();
            while ($tempDate->lte($endDateInput)) {
                $isWeekend = Absensi::isWeekend($tempDate);
                $isHoliday = \App\Models\Holiday::isHoliday($tempDate);

                if ($isCalendarLeave || (!$isWeekend && !$isHoliday)) {
                    $validDates[] = $tempDate->copy();
                }
                $tempDate->addDay();
            }

            if (empty($validDates)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Tidak ada hari kerja efektif pada rentang tanggal yang dipilih.'], 422);
            }

            $startDate = $validDates[0];
            $endDate = end($validDates);
            $totalDays = count($validDates);

            if ($totalDays > 30) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => "Pengajuan ulang maksimal 30 hari."], 422);
            }

            // Delete old file
            if ($absensi->file_bukti && Storage::disk('public')->exists($absensi->file_bukti)) {
                Storage::disk('public')->delete($absensi->file_bukti);
            }

            $filePath = $request->file('file_bukti')->store('bukti_sakit_izin', 'public');
            $employment = strtolower($absensi->user->work_location ?? 'office');
            $startLevel = $this->determineResubmitLevel($absensi->rejected_by, $absensi->workflow_status);
            $baseWorkflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];
            $workflow = $this->resetWorkflowFromLevel($baseWorkflow, $startLevel, $employment);

            $updateData = [
                'check_in_at' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'file_bukti' => $filePath,
                'keterangan_izin_sakit' => $request->keterangan_izin_sakit ?? $request->catatan ?? $absensi->keterangan_izin_sakit,
                'status_approval' => 'pending',
                'workflow_status' => $workflow,
                'current_approval_level' => $startLevel,
                'rejected_by' => null,
                'rejected_at' => null,
                'catatan_admin' => null,
                'updated_at' => now(),
            ];

            $absensi->update($updateData);

            // Bersihkan data anak lama
            Absensi::where('parent_id', $absensi->id)->delete();

            // Buat ulang data anak jika lebih dari 1 hari
            if ($totalDays > 1) {
                $childRecords = [];
                foreach ($validDates as $index => $date) {
                    if ($index > 0) { // Lewati hari pertama (induk)
                        $childRecords[] = [
                            'user_id' => $absensi->user_id,
                            'parent_id' => $absensi->id,
                            'check_in_at' => $date->copy()->setTime(8, 0, 0)->toDateTimeString(),
                            'status' => $type,
                            'tipe' => $type,
                            'submission_type' => $absensi->submission_type,
                            'status_approval' => 'pending',
                            'file_bukti' => $filePath,
                            'keterangan_izin_sakit' => $updateData['keterangan_izin_sakit'],
                            'workflow_status' => is_array($workflow) ? json_encode($workflow) : $workflow,
                            'current_approval_level' => $startLevel,
                            'created_at' => now(), 'updated_at' => now(),
                        ];
                    }
                }
                if (!empty($childRecords)) {
                    Absensi::insert($childRecords);
                }
            }

            Notification::create([
                'user_id' => $absensi->user_id,
                'title' => "Pengajuan " . ucfirst($type) . " Diajukan Ulang",
                'message' => "Pengajuan kamu telah direvisi (durasi {$totalDays} hari) dan akan direview oleh approver.",
                'type' => $type . '_resubmitted',
                'target_page' => "/{$type}_detail",
                'target_id' => $absensi->id,
            ]);

            DB::commit();
            $absensi->load('user');
            $absensi->file_bukti_url = Storage::url($absensi->file_bukti);

            return response()->json(['success' => true, 'message' => "Pengajuan {$type} berhasil direvisi dan diajukan ulang.", 'data' => $absensi], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false, 
                'message' => 'Server error. ' . (config('app.debug') ? $e->getMessage() : '')
            ], 500);
        }
    }

    public function resubmitLembur(Request $request, $id)
    {
        try {
            // ✅ VALIDASI ID
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID tidak valid.'
                ], 400);
            }

            $request->validate([
                'foto'        => 'required|image|max:2048',
                'foto_2'      => 'nullable|image|max:2048',
                'foto_3'      => 'nullable|image|max:2048',
                'foto_4'      => 'nullable|image|max:2048',
                'foto_5'      => 'nullable|image|max:2048',
                'foto_6'      => 'nullable|image|max:2048',
                'lat'         => 'required|numeric',
                'lng'         => 'required|numeric',
                'jam_mulai'   => 'required|date_format:H:i',
                'jam_selesai' => 'required|date_format:H:i',
                'istirahat'   => 'required|boolean',
                'keterangan'  => 'required|string|max:500',
                'keterangan_goals' => 'nullable|string|max:2000',
                'is_weekend'  => 'nullable|boolean',
                'is_mocked'   => 'nullable|boolean',
            ]);

            $jamMulai = Carbon::createFromFormat('H:i', $request->jam_mulai);
            $jamSelesai = Carbon::createFromFormat('H:i', $request->jam_selesai);
            
            if ($jamSelesai->lte($jamMulai)) {
                return response()->json(['success' => false, 'message' => 'Jam selesai harus lebih dari jam mulai.'], 422);
            }

            // ✅ FIX SECURITY #2: Batas Maksimal Lembur 5 Jam
            if ($jamMulai->diffInHours($jamSelesai) > 5) {
                return response()->json(['success' => false, 'message' => 'Durasi lembur tidak wajar (Maksimal 5 jam sehari). Harap hubungi HR untuk lembur khusus.'], 422);
            }

            $absensi = Absensi::find($id);
            if (!$absensi || $absensi->user_id != Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Record tidak ditemukan atau akses ditolak.'], 404);
            }

            if (!in_array($absensi->status_approval, ['rejected', 'ditolak'])) {
                return response()->json(['success' => false, 'message' => 'Hanya pengajuan yang ditolak yang bisa diajukan ulang.'], 409);
            }

            // --- 1. HANDLE PHOTOS (CLEAN WAY) ---
            $photoFields = ['foto_pulang', 'foto_pulang_2', 'foto_pulang_3', 'foto_pulang_4', 'foto_pulang_5', 'foto_pulang_6'];
            $requestFields = ['foto', 'foto_2', 'foto_3', 'foto_4', 'foto_5', 'foto_6'];
            $newPaths = [];

            foreach ($photoFields as $index => $field) {
                $reqField = $requestFields[$index];
                
                if ($request->hasFile($reqField)) {
                    // Delete old
                    if ($absensi->$field && Storage::disk('public')->exists($absensi->$field)) {
                        Storage::disk('public')->delete($absensi->$field);
                    }
                    // Store new
                    $newPaths[$field] = $request->file($reqField)->store('absensi_foto', 'public');
                } else {
                    $newPaths[$field] = $absensi->$field;
                }
            }

            $lokasiPulang = $request->lat . ',' . $request->lng;
            $baseDate = $absensi->check_in_at ? Carbon::parse($absensi->check_in_at)->format('Y-m-d') : Carbon::today()->format('Y-m-d');
            $lemburStart = Carbon::parse($baseDate . ' ' . $request->jam_mulai);
            $lemburEnd = Carbon::parse($baseDate . ' ' . $request->jam_selesai);
            $isWeekendOvertime = $request->boolean('is_weekend', Absensi::isWeekend($lemburStart));

            $employment = strtolower($absensi->user->work_location ?? 'office');
            $startLevel = $this->determineResubmitLevel($absensi->rejected_by, $absensi->workflow_status);
            $baseWorkflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];
            $workflow = $this->resetWorkflowFromLevel($baseWorkflow, $startLevel, $employment);

            $overtimeData = Absensi::calculateOvertimeFromInput($lemburStart, $lemburEnd, $request->istirahat, $isWeekendOvertime);
            
            $kategori = $absensi->user->kategori_absensi;
            $salaryData = Absensi::calculateSalary(
                $absensi->late_minutes ?? 0,
                $absensi->status,
                'lembur',
                $isWeekendOvertime,
                $absensi->check_in_at,
                $absensi->check_out_at ?? now(),
                $kategori
            );

            $absensi->update(array_merge($newPaths, [
            'lokasi_pulang'         => $lokasiPulang,
            'lembur_start'          => $lemburStart,
            'lembur_end'            => $lemburEnd,
            'lembur_rest'           => $request->istirahat,
            'lembur_keterangan'     => $request->keterangan,
            'keterangan_goals'      => $request->keterangan_goals ?? $absensi->keterangan_goals,
            'tipe'                  => 'lembur',
            'status_approval'       => 'pending',
            'workflow_status'       => $workflow,
            'current_approval_level'=> $startLevel,
            'is_mocked'             => $request->boolean('is_mocked'),
            'rejected_by'           => null,
            'rejected_at'           => null,
            'catatan_admin'         => null,
            'check_out_at'          => $absensi->check_out_at ?? now(),
            'updated_at'            => now(),
            'overtime_minutes'      => $overtimeData['minutes'],
            'overtime_pay'          => $overtimeData['pay'],
            'base_salary'           => $absensi->base_salary ?? $salaryData['base_salary'],
            'late_penalty'          => $absensi->late_penalty ?? $salaryData['late_penalty'],
            'final_salary'          => $absensi->final_salary ?? $salaryData['final_salary'],
            'is_weekend_overtime'   => $isWeekendOvertime,
        ]));

        $absensi->load('user');
        $absensi->foto_pulang_url = Storage::url($absensi->foto_pulang);

        Notification::create([
            'user_id'     => $absensi->user_id,
            'title'       => "Pengajuan Lembur Diajukan Ulang",
            'message'     => "Pengajuan lembur kamu telah diajukan ulang dan akan direview oleh approver yang menolak sebelumnya.",
            'type'        => "lembur_resubmitted",
            'target_page' => '/lembur_detail',
            'target_id'   => $absensi->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lembur berhasil diajukan ulang.' . ($isWeekendOvertime ? ' (Weekend Rate)' : ''),
            'data'    => $absensi
        ], 200);

    } catch (ValidationException $e) {
        return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
    }
}



public function getDetailAbsensi($id)
{
    try {
        // ✅ VALIDASI ID
        if (!is_numeric($id) || $id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID tidak valid.'
            ], 400);
        }

        $absensi = Absensi::with('user')->find($id);

        if (!$absensi) {
            return response()->json([
                'success' => false,
                'message' => 'Data absensi tidak ditemukan.'
            ], 404);
        }

        //  CEK OWNERSHIP
        if ($absensi->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'absensi' => new AbsensiResource($absensi)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan server. ' . (config('app.debug') ? $e->getMessage() : '')
        ], 500);
    }
}

public function pengajuanTelat(Request $request)
{
    DB::beginTransaction();

    try {
        $request->validate([
            'file_bukti' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'keterangan' => 'required|string|max:500',
            'absensi_id' => 'required|integer|exists:absensis,id',
        ]);

        $user = Auth::user();

        // Ambil absensi yang dimaksud
        $absensi = Absensi::where('id', $request->absensi_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$absensi) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Data absensi tidak ditemukan.'
            ], 404);
        }

        // Pastikan sudah absen masuk
        if (!$absensi->check_in_at) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Anda belum absen masuk.'
            ], 400);
        }

        // Cek kalau sudah pernah ajukan telat untuk absensi ini
        if ($absensi->tipe === 'telat' && in_array($absensi->status_approval, ['pending', 'approved'])) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah mengajukan keterangan telat untuk hari ini.'
            ], 409);
        }

        // Hitung keterlambatan dari jam absen masuk
        $checkIn = Carbon::parse($absensi->check_in_at);
        $standardTime = $checkIn->copy()->setTime(8, 0, 0);
        $lateMinutes = $checkIn->greaterThan($standardTime)
            ? (int) abs($checkIn->diffInMinutes($standardTime))
            : 0;

        if ($lateMinutes === 0) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak tercatat terlambat.'
            ], 400);
        }

        // Hapus file bukti lama kalau ada
        if ($absensi->file_bukti && Storage::disk('public')->exists($absensi->file_bukti)) {
            Storage::disk('public')->delete($absensi->file_bukti);
        }

        // Upload file bukti baru
        $fileBuktiPath = $request->file('file_bukti')->store('bukti_telat', 'public');

        // Tentukan workflow berdasarkan employment type
        $employment = strtolower($user->work_location ?? 'office');

        if ($employment === 'freelance') {
            $workflow = [
                'supervisor' => 'pending',
                'manager'    => 'pending',
                'hrga'       => 'pending',
            ];
        } else {
            $workflow = [
                'manager' => 'pending',
                'hrga'    => 'pending',
            ];
        }

        // ✅ UPDATE record absensi yang ADA (bukan bikin baru!)
        $absensi->update([
            'tipe'                  => 'telat',
            'status_approval'       => 'pending',
            'file_bukti'            => $fileBuktiPath,
            'keterangan_izin_sakit' => $request->keterangan,
            'workflow_status'       => $workflow,
            'current_approval_level'=> 1,
            'rejected_by'           => null,
            'rejected_at'           => null,
            'catatan_admin'         => null,
        ]);

        // Notifikasi
        Notification::create([
            'user_id'     => $user->id,
            'title'       => 'Pengajuan Keterangan Telat',
            'message'     => "Pengajuan keterangan telat ({$lateMinutes} menit) telah diajukan dan menunggu approval.",
            'type'        => 'telat_submitted',
            'target_page' => '/telat_detail',
            'target_id'   => $absensi->id,
        ]);

        DB::commit();

        $absensi->load('user');
        $absensi->file_bukti_url = Storage::url($absensi->file_bukti);

        return response()->json([
            'success'      => true,
            'message'      => "Pengajuan keterangan telat ({$lateMinutes} menit) berhasil diajukan.",
            'data'         => $absensi,
            'late_minutes' => $lateMinutes,
        ], 200);

    } catch (ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors'  => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        if (isset($fileBuktiPath) && Storage::disk('public')->exists($fileBuktiPath)) {
            Storage::disk('public')->delete($fileBuktiPath);
        }
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan server. ' . (config('app.debug') ? $e->getMessage() : '')
        ], 500);
    }
}

}
