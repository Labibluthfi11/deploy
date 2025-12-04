<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Absensi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Models\Notification;
use Illuminate\Support\Facades\Validator; // ⬅️ Pastiin ini ada

class AbsensiController extends Controller
{
    private $workflowTemplates = [
        'freelance' => [
            'mas_yuli' => 'pending',
            'mas_nu'   => 'pending',
            'mba_nadya'=> 'pending',
        ],
        'organik' => [
            'mas_nu'   => 'pending',
            'mba_nadya'=> 'pending',
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
            ]);

            $user = Auth::user();
            $today = Carbon::today();

            $existingAbsensi = Absensi::where('user_id', $user->id)
                ->whereDate('check_in_at', $today)
                ->whereIn('status_approval', ['pending', 'approved'])
                ->first();

            if ($existingAbsensi && $existingAbsensi->check_in_at) {
                if ($existingAbsensi->tipe == 'sakit' || $existingAbsensi->tipe == 'izin') {
                    return response()->json([
                        'message' => 'Absensi dibatalkan. Anda sudah mengajukan ' . ucfirst($existingAbsensi->tipe) . ' untuk hari ini.',
                        'tipe' => $existingAbsensi->tipe
                    ], 409);
                }
                return response()->json(['message' => 'Anda sudah absen masuk hari ini.'], 409);
            }

            $fotoPath = $request->file('foto')->store('absensi_foto', 'public');
            $lokasiMasuk = $request->lat . ',' . $request->lng;
            $checkInTime = now();

            $employment = strtolower($user->employment_type ?? 'organik');
            $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];

            // 🆕 CEK APAKAH HARI INI WEEKEND
            $isWeekend = Absensi::isWeekend($checkInTime);

            // Hitung keterlambatan
            $lateMinutes = 0;
            if ($request->status === 'hadir') {
                $lateMinutes = $this->calculateLateMinutes($checkInTime, $isWeekend);
            }

            // 🆕 Hitung gaji dengan parameter weekend
            $salaryData = Absensi::calculateSalary($lateMinutes, $request->status, null, $isWeekend);

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
                'is_weekend_overtime' => false, // Normal absen masuk bukan weekend overtime
            ]);

            $absensi->load('user');
            $absensi->foto_masuk_url = Storage::url($absensi->foto_masuk);

            return response()->json([
                'message' => 'Absensi masuk berhasil' . ($isWeekend ? ' (Weekend - Gaji 2x Lipat)' : ''),
                'data' => $absensi
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan server: ' . $e->getMessage()], 500);
        }
    }
    // Method lain tetap sama (absenPulang, absenLembur, dll.)
    public function absenPulang(Request $request)
    {
        try {
            $request->validate([
                'foto' => 'required|image|max:2048',
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
                'tipe' => 'nullable|in:lembur,cuti',
            ]);

            $user = Auth::user();
            $today = Carbon::today();

            $absensi = Absensi::where('user_id', $user->id)
                ->whereDate('check_in_at', $today)
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

            $fotoPath = $request->file('foto')->store('absensi_foto', 'public');
            $lokasiPulang = $request->lat . ',' . $request->lng;

            if ($request->tipe === 'lembur') {
                $statusApproval = 'pending';
                $employment = strtolower($user->employment_type ?? 'organik');
                $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];
                $currentLevel = 1;
            } else {
                $statusApproval = 'approved';
                $workflow = $absensi->workflow_status;
                $currentLevel = $absensi->current_approval_level;
            }

            $absensi->update([
                'check_out_at' => now(),
                'foto_pulang' => $fotoPath,
                'lokasi_pulang' => $lokasiPulang,
                'tipe' => $request->tipe,
                'status_approval' => $statusApproval,
                'workflow_status' => $workflow,
                'current_approval_level' => $currentLevel,
            ]);

            $absensi->load('user');
            $absensi->foto_pulang_url = Storage::url($absensi->foto_pulang);

            return response()->json(['message' => 'Absensi pulang berhasil', 'data' => $absensi]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan server: ' . $e->getMessage()], 500);
        }
    }

   public function meAbsensi(Request $request)
{
    $userId = Auth::id();

    // ✅ TAMBAH FILTER TANGGAL (default 3 bulan terakhir)
    $startDate = $request->input('start_date', Carbon::now()->subMonths(3));
    $endDate = $request->input('end_date', Carbon::now());

    // ✅ FIX QUERY: Ambil DISTINCT per tanggal + prioritas status
    $absensi = Absensi::with('user')
        ->where('user_id', $userId)
        ->whereBetween('check_in_at', [$startDate, $endDate])
        ->orderByRaw("
            CASE status_approval
                WHEN 'approved' THEN 3
                WHEN 'pending' THEN 2
                WHEN 'rejected' THEN 1
                WHEN 'ditolak' THEN 1
                ELSE 0
            END DESC
        ")
        ->orderBy('check_in_at', 'desc')
        ->orderBy('id', 'desc')
        ->get()
        ->unique(function ($item) {
            // ✅ UNIQUE berdasarkan tanggal + tipe
            $date = Carbon::parse($item->check_in_at)->format('Y-m-d');
            return $date . '_' . ($item->tipe ?? 'hadir');
        })
        ->values();

    // ✅ TRANSFORM DATA
    $result = $absensi->map(function($item) {
        $item->foto_masuk_url = $item->foto_masuk ? Storage::url($item->foto_masuk) : null;
        $item->foto_pulang_url = $item->foto_pulang ? Storage::url($item->foto_pulang) : null;
        $item->file_bukti_url = $item->file_bukti ? Storage::url($item->file_bukti) : null;
        return $item->toArray(); // ✅ Biar appended attributes muncul
    });

    return response()->json(['data' => $result]);
}
    // Method lainnya (absenSakit, absenLembur, resubmit) tetap sama seperti kode asli
     public function absenSakit(Request $request)
    {
        // ✅ START TRANSACTION
        DB::beginTransaction();

        try {
            // ✅ VALIDASI INPUT
            $request->validate([
                'file_bukti' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'keterangan_izin_sakit' => 'required|string|max:500',
                'status' => 'required|in:sakit,izin',
                'start_date' => 'nullable|date|after_or_equal:today', // ⬅️ Tambah validasi
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $user = Auth::user();

            // ✅ PARSE TANGGAL
            $startDate = $request->start_date
                ? Carbon::parse($request->start_date)->startOfDay()
                : Carbon::today()->startOfDay();

            $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->startOfDay()
            : $startDate->copy()->startOfDay();

            // Hitung total hari
            $totalDays = $startDate->diffInDays($endDate) + 1;

            // ✅ VALIDASI: Max 30 hari
            if ($totalDays > 30) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan maksimal 30 hari. Silakan hubungi HRD untuk pengajuan lebih dari 30 hari.'
                ], 422);
            }

            // ✅ VALIDASI: Cek overlap dengan pengajuan lain
            $existingAbsensi = Absensi::where('user_id', $user->id)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('check_in_at', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('check_in_at', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                          });
                })
                ->whereIn('status_approval', ['pending', 'approved'])
                ->first();

            if ($existingAbsensi) {
                DB::rollBack();

                $conflictDate = Carbon::parse($existingAbsensi->check_in_at)->format('d/m/Y');
                return response()->json([
                    'success' => false,
                    'message' => "Anda sudah memiliki pengajuan {$existingAbsensi->status} pada tanggal {$conflictDate}."
                ], 409);
            }

            // ✅ UPLOAD FILE
            $fileBuktiPath = $request->file('file_bukti')->store('bukti_sakit_izin', 'public');

            // ✅ GET WORKFLOW
            $employment = strtolower($user->employment_type ?? 'organik');
            $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];

            // ✅ CREATE PARENT RECORD
            $parentAbsensi = Absensi::create([
                'user_id' => $user->id,
                'check_in_at' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'status' => $request->status,
                'tipe' => $request->status,
                'status_approval' => 'pending',
                'file_bukti' => $fileBuktiPath,
                'keterangan_izin_sakit' => $request->keterangan_izin_sakit,
                'workflow_status' => $workflow,
                'current_approval_level' => 1,
                'late_minutes' => 0,
                'base_salary' => 0,
                'late_penalty' => 0,
                'final_salary' => 0,
            ]);

            // ✅ BULK INSERT CHILDREN
                $currentDate = $startDate->copy();
                $childRecords = [];
                $childCount = 0;

                while ($currentDate->lte($endDate)) {
                    if (!$currentDate->isSameDay($startDate)) {
                        $childRecords[] = [
                            'user_id' => $user->id,
                            'parent_id' => $parentAbsensi->id,
                            'check_in_at' => $currentDate->copy()->toDateTimeString(),
                            'end_date' => null,
                            'total_days' => 1,
                            'status' => $request->status,
                            'tipe' => $request->status,
                            'status_approval' => 'pending',
                            'file_bukti' => $fileBuktiPath,
                            'keterangan_izin_sakit' => $request->keterangan_izin_sakit,
                            'workflow_status' => json_encode($workflow),
                            'current_approval_level' => 1,
                            'late_minutes' => 0,
                            'base_salary' => 0,
                            'late_penalty' => 0,
                            'final_salary' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $childCount++;
                    }

                    $currentDate->addDay();
                }

                // INSERT SEMUA SEKALIGUS
                if (!empty($childRecords)) {
                    Absensi::insert($childRecords);
                }

                $createdRecords = 1 + $childCount; // Parent + children

            // ✅ CREATE NOTIFICATION (opsional)
            Notification::create([
                'user_id' => $user->id,
                'title' => "Pengajuan " . ucfirst($request->status) . " Berhasil",
                'message' => "Pengajuan {$request->status} untuk {$totalDays} hari telah diajukan dan menunggu approval.",
                'type' => $request->status . '_submitted',
                'target_page' => '/' . $request->status . '_detail',
                'target_id' => $parentAbsensi->id,
            ]);

            // ✅ COMMIT TRANSACTION
            DB::commit();

            // ✅ LOAD RELASI & FORMAT URL
            $parentAbsensi->load('user');
            $parentAbsensi->file_bukti_url = Storage::url($parentAbsensi->file_bukti);

            return response()->json([
            'success' => true,
            'message' => "Pengajuan {$request->status} berhasil diajukan untuk {$totalDays} hari.",
            'data' => $parentAbsensi,
            'summary' => [
                'total_days' => $totalDays,
                'total_records' => $createdRecords,  // ✅ LANGSUNG PAKE VARIABLE
                'parent_id' => $parentAbsensi->id,
                'child_count' => $childCount,
                'date_range' => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'),
            ]
        ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ HAPUS FILE JIKA SUDAH DIUPLOAD
            if (isset($fileBuktiPath) && Storage::disk('public')->exists($fileBuktiPath)) {
                Storage::disk('public')->delete($fileBuktiPath);
            }

            // ✅ LOG ERROR
            \Log::error('❌ [ABSEN SAKIT] Error: ' . $e->getMessage());
            \Log::error('❌ [ABSEN SAKIT] Stack: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ FIXED: absenIzin dengan Transaction
    public function absenIzin(Request $request)
    {
        // ✅ START TRANSACTION
        DB::beginTransaction();

        try {
            // ✅ VALIDASI INPUT
            $request->validate([
                'file_bukti' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'keterangan_izin_sakit' => 'required|string|max:500',
                'catatan_admin' => 'nullable|string|max:255',
                'start_date' => 'nullable|date|after_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $user = Auth::user();

            // ✅ PARSE TANGGAL
            $startDate = $request->start_date
                ? Carbon::parse($request->start_date)->startOfDay()
                : Carbon::today()->startOfDay();

            $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->startOfDay()  // ✅ GANTI
            : $startDate->copy()->startOfDay();

            $totalDays = $startDate->diffInDays($endDate) + 1;

            // ✅ VALIDASI: Max 30 hari
            if ($totalDays > 30) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan izin maksimal 30 hari.'
                ], 422);
            }

            // ✅ VALIDASI: Cek overlap
            $existingAbsensi = Absensi::where('user_id', $user->id)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('check_in_at', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('check_in_at', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                          });
                })
                ->whereIn('status_approval', ['pending', 'approved'])
                ->first();

            if ($existingAbsensi) {
                DB::rollBack();

                $conflictDate = Carbon::parse($existingAbsensi->check_in_at)->format('d/m/Y');
                return response()->json([
                    'success' => false,
                    'message' => "Anda sudah memiliki pengajuan {$existingAbsensi->status} pada tanggal {$conflictDate}."
                ], 409);
            }

            // ✅ UPLOAD FILE
            $fileBuktiPath = $request->file('file_bukti')->store('bukti_sakit_izin', 'public');

            // ✅ GET WORKFLOW
            $employment = strtolower($user->employment_type ?? 'organik');
            $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];

            // ✅ CREATE PARENT
            $parentAbsensi = Absensi::create([
                'user_id' => $user->id,
                'check_in_at' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'status' => 'izin',
                'tipe' => 'izin',
                'status_approval' => 'pending',
                'file_bukti' => $fileBuktiPath,
                'keterangan_izin_sakit' => $request->keterangan_izin_sakit,
                'catatan_admin' => $request->catatan_admin,
                'workflow_status' => $workflow,
                'current_approval_level' => 1,
                'late_minutes' => 0,
                'base_salary' => 0,
                'late_penalty' => 0,
                'final_salary' => 0,
            ]);

            // ✅ BULK INSERT CHILDREN
                $currentDate = $startDate->copy();
                $childRecords = [];
                $childCount = 0;

                while ($currentDate->lte($endDate)) {
                    if (!$currentDate->isSameDay($startDate)) {
                        $childRecords[] = [
                            'user_id' => $user->id,
                            'parent_id' => $parentAbsensi->id,
                            'check_in_at' => $currentDate->copy()->toDateTimeString(),
                            'end_date' => null,
                            'total_days' => 1,
                            'status' => 'izin',
                            'tipe' => 'izin',
                            'status_approval' => 'pending',
                            'file_bukti' => $fileBuktiPath,
                            'keterangan_izin_sakit' => $request->keterangan_izin_sakit,
                            'catatan_admin' => $request->catatan_admin,
                            'workflow_status' => json_encode($workflow),
                            'current_approval_level' => 1,
                            'late_minutes' => 0,
                            'base_salary' => 0,
                            'late_penalty' => 0,
                            'final_salary' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $childCount++;
                    }

                    $currentDate->addDay();
                }

                // INSERT SEMUA SEKALIGUS
                if (!empty($childRecords)) {
                    Absensi::insert($childRecords);
                }

                $createdRecords = 1 + $childCount; // Parent + children

            // ✅ CREATE NOTIFICATION
            Notification::create([
                'user_id' => $user->id,
                'title' => "Pengajuan Izin Berhasil",
                'message' => "Pengajuan izin untuk {$totalDays} hari telah diajukan dan menunggu approval.",
                'type' => 'izin_submitted',
                'target_page' => '/izin_detail',
                'target_id' => $parentAbsensi->id,
            ]);

            // ✅ COMMIT TRANSACTION
            DB::commit();

            $parentAbsensi->load('user');
            $parentAbsensi->file_bukti_url = Storage::url($parentAbsensi->file_bukti);

            return response()->json([
    'success' => true,
    'message' => "Pengajuan izin berhasil diajukan untuk {$totalDays} hari.",
    'data' => $parentAbsensi,
    'summary' => [
        'total_days' => $totalDays,
        'total_records' => $createdRecords,  // ✅ LANGSUNG PAKE VARIABLE
        'parent_id' => $parentAbsensi->id,
        'child_count' => $childCount,
        'date_range' => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'),
        ]
        ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ HAPUS FILE JIKA GAGAL
            if (isset($fileBuktiPath) && Storage::disk('public')->exists($fileBuktiPath)) {
                Storage::disk('public')->delete($fileBuktiPath);
            }

            \Log::error('❌ [ABSEN IZIN] Error: ' . $e->getMessage());
            \Log::error('❌ [ABSEN IZIN] Stack: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
            ], 500);
        }
    }
    // Resubmit methods tetap sama...
    public function absenLembur(Request $request)
    {
        try {
            $request->validate([
                'jam_mulai'     => 'required|date_format:H:i',
                'jam_selesai'   => 'required|date_format:H:i|after:jam_mulai',
                'istirahat'     => 'required|boolean',
                'keterangan'    => 'required|string|max:500',
                'foto'          => 'required|image|max:2048',
                'lat'           => 'required|numeric',
                'lng'           => 'required|numeric',
                'is_weekend'    => 'nullable|boolean', // 🆕 Parameter baru dari Flutter
            ]);

            $user = Auth::user();
            $today = Carbon::today();

            $absensi = Absensi::where('user_id', $user->id)
                ->whereDate('check_in_at', $today)
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

            if ($absensi->check_out_at) {
                return response()->json(['success' => false, 'message' => 'Anda sudah absen pulang hari ini.'], 409);
            }

            $fotoPath = $request->file('foto')->store('absensi_foto', 'public');
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

            // 🆕 HITUNG GAJI POKOK (dengan weekend multiplier)
            $lateMinutes = $absensi->late_minutes ?? 0;
            $salaryData = Absensi::calculateSalary($lateMinutes, $absensi->status, 'lembur', $isWeekendOvertime);

            $employment = strtolower($user->employment_type ?? 'organik');
            $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];

            $absensi->update([
                'check_out_at'          => now(),
                'foto_pulang'           => $fotoPath,
                'lokasi_pulang'         => $lokasiPulang,
                'tipe'                  => 'lembur',
                'status_approval'       => 'pending',
                'workflow_status'       => $workflow,
                'current_approval_level' => 1,
                'lembur_start'          => $lemburStart,
                'lembur_end'            => $lemburEnd,
                'lembur_rest'           => $request->istirahat,
                'lembur_keterangan'     => $request->keterangan,
                'overtime_minutes'      => $overtimeData['minutes'],
                'overtime_pay'          => $overtimeData['pay'],
                'base_salary'           => $absensi->base_salary ?? $salaryData['base_salary'],
                'late_penalty'          => $absensi->late_penalty ?? $salaryData['late_penalty'],
                'final_salary'          => $absensi->final_salary ?? $salaryData['final_salary'],
                'is_weekend_overtime'   => $isWeekendOvertime, // 🆕 Simpan flag weekend
            ]);

            $absensi->load('user');
            $absensi->foto_pulang_url = Storage::url($absensi->foto_pulang);

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

    public function resubmitSakit(Request $request, $id)
    {
        try {
            $request->validate([
                'file_bukti' => 'required|file|max:2048',
                'keterangan_izin_sakit' => 'nullable|string|max:500',
            ]);

            $absensi = Absensi::find($id);
            if (!$absensi) {
                return response()->json(['success' => false, 'message' => 'Record tidak ditemukan.'], 404);
            }

            if ($absensi->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }

            if ($absensi->status_approval !== 'rejected' && $absensi->status_approval !== 'ditolak') {
                return response()->json(['success' => false, 'message' => 'Hanya pengajuan yang ditolak yang bisa diajukan ulang.'], 409);
            }

            if ($absensi->file_bukti && Storage::disk('public')->exists($absensi->file_bukti)) {
                Storage::disk('public')->delete($absensi->file_bukti);
            }

            $filePath = $request->file('file_bukti')->store('bukti_sakit_izin', 'public');
            $employment = strtolower($absensi->user->employment_type ?? 'organik');
            $startLevel = $this->determineResubmitLevel($absensi->rejected_by, $absensi->workflow_status);
            $baseWorkflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];
            $workflow = $this->resetWorkflowFromLevel($baseWorkflow, $startLevel, $employment);

            $absensi->update([
                'file_bukti' => $filePath,
                'keterangan_izin_sakit' => $request->keterangan_izin_sakit ?? $absensi->keterangan_izin_sakit,
                'status_approval' => 'pending',
                'workflow_status' => $workflow,
                'current_approval_level' => $startLevel,
                'rejected_by' => null,
                'rejected_at' => null,
                'catatan_admin' => null,
                'updated_at' => now(),
            ]);

            $absensi->load('user');
            $absensi->file_bukti_url = Storage::url($absensi->file_bukti);

            Notification::create([
                'user_id' => $absensi->user_id,
                'title' => "Pengajuan Sakit Diajukan Ulang",
                'message' => "Pengajuan kamu telah diajukan ulang dan akan direview oleh approver yang menolak sebelumnya.",
                'type' => 'sakit_resubmitted',
                'target_page' => '/sakit_detail',
                'target_id' => $absensi->id,
            ]);

            return response()->json(['success' => true, 'message' => 'Pengajuan sakit berhasil diajukan ulang. Menunggu approval.', 'data' => $absensi], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function resubmitIzin(Request $request, $id)
    {
        try {
            $request->validate([
                'file_bukti' => 'required|file|max:2048',
                'catatan' => 'nullable|string|max:500',
                'catatan_panggilan' => 'nullable|string|max:255',
            ]);

            $absensi = Absensi::find($id);
            if (!$absensi) {
                return response()->json(['success' => false, 'message' => 'Record tidak ditemukan.'], 404);
            }

            if ($absensi->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }

            if ($absensi->status_approval !== 'rejected' && $absensi->status_approval !== 'ditolak') {
                return response()->json(['success' => false, 'message' => 'Hanya pengajuan yang ditolak yang bisa diajukan ulang.'], 409);
            }

            if ($absensi->file_bukti && Storage::disk('public')->exists($absensi->file_bukti)) {
                Storage::disk('public')->delete($absensi->file_bukti);
            }

            $filePath = $request->file('file_bukti')->store('bukti_sakit_izin', 'public');
            $employment = strtolower($absensi->user->employment_type ?? 'organik');
            $startLevel = $this->determineResubmitLevel($absensi->rejected_by, $absensi->workflow_status);
            $baseWorkflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];
            $workflow = $this->resetWorkflowFromLevel($baseWorkflow, $startLevel, $employment);

            $absensi->update([
                'file_bukti' => $filePath,
                'keterangan_izin_sakit' => $request->catatan ?? $absensi->keterangan_izin_sakit,
                'status_approval' => 'pending',
                'workflow_status' => $workflow,
                'current_approval_level' => $startLevel,
                'rejected_by' => null,
                'rejected_at' => null,
                'catatan_admin' => null,
                'updated_at' => now(),
            ]);

            $absensi->load('user');
            $absensi->file_bukti_url = Storage::url($absensi->file_bukti);

            Notification::create([
                'user_id' => $absensi->user_id,
                'title' => "Pengajuan Izin Diajukan Ulang",
                'message' => "Pengajuan kamu telah diajukan ulang dan akan direview oleh approver yang menolak sebelumnya.",
                'type' => 'izin_resubmitted',
                'target_page' => '/izin_detail',
                'target_id' => $absensi->id,
            ]);

            return response()->json(['success' => true, 'message' => 'Pengajuan izin berhasil diajukan ulang. Menunggu approval.', 'data' => $absensi], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    // app/Http/Controllers/Api/AbsensiController.php
// TAMBAHKAN LOGGING UNTUK DEBUG

    public function resubmitLembur(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'foto'        => 'required|image|max:2048',
                'lat'         => 'required|numeric',
                'lng'         => 'required|numeric',
                'jam_mulai'   => 'required|date_format:H:i',
                'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
                'istirahat'   => 'required|boolean',
                'keterangan'  => 'required|string|max:500',
                'is_weekend'  => 'nullable|boolean', // 🆕
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $absensi = Absensi::find($id);
            if (!$absensi) {
                return response()->json(['success' => false, 'message' => 'Record tidak ditemukan.'], 404);
            }

            if ($absensi->user_id != Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }

            if ($absensi->status_approval !== 'rejected' && $absensi->status_approval !== 'ditolak') {
                return response()->json(['success' => false, 'message' => 'Hanya pengajuan yang ditolak yang bisa diajukan ulang.'], 409);
            }

            // Hapus foto lama
            if ($absensi->foto_pulang && Storage::disk('public')->exists($absensi->foto_pulang)) {
                Storage::disk('public')->delete($absensi->foto_pulang);
            }

            $filePath = $request->file('foto')->store('absensi_foto', 'public');
            $lokasiPulang = $request->lat . ',' . $request->lng;

            $baseDate = $absensi->check_in_at
                ? Carbon::parse($absensi->check_in_at)->format('Y-m-d')
                : Carbon::today()->format('Y-m-d');

            $lemburStart = Carbon::parse($baseDate . ' ' . $request->jam_mulai);
            $lemburEnd = Carbon::parse($baseDate . ' ' . $request->jam_selesai);

            // 🆕 CEK WEEKEND
            $isWeekendOvertime = $request->boolean('is_weekend', Absensi::isWeekend($lemburStart));

            // Workflow
            $employment = strtolower($absensi->user->employment_type ?? 'organik');
            $startLevel = $this->determineResubmitLevel($absensi->rejected_by, $absensi->workflow_status);
            $baseWorkflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];
            $workflow = $this->resetWorkflowFromLevel($baseWorkflow, $startLevel, $employment);

            // 🆕 Kalkulasi dengan weekend multiplier
            $overtimeData = Absensi::calculateOvertimeFromInput(
                $lemburStart,
                $lemburEnd,
                $request->istirahat,
                $isWeekendOvertime
            );
            $salaryData = Absensi::calculateSalary($absensi->late_minutes ?? 0, $absensi->status, 'lembur', $isWeekendOvertime);

            // Update
            $absensi->update([
                'foto_pulang'           => $filePath,
                'lokasi_pulang'         => $lokasiPulang,
                'lembur_start'          => $lemburStart,
                'lembur_end'            => $lemburEnd,
                'lembur_rest'           => $request->istirahat,
                'lembur_keterangan'     => $request->keterangan,
                'tipe'                  => 'lembur',
                'status_approval'       => 'pending',
                'workflow_status'       => $workflow,
                'current_approval_level'=> $startLevel,
                'rejected_by'           => null,
                'rejected_at'           => null,
                'catatan_admin'         => null,
                'check_out_at'          => now(),
                'updated_at'            => now(),
                'overtime_minutes'      => $overtimeData['minutes'],
                'overtime_pay'          => $overtimeData['pay'],
                'base_salary'           => $absensi->base_salary ?? $salaryData['base_salary'],
                'late_penalty'          => $absensi->late_penalty ?? $salaryData['late_penalty'],
                'final_salary'          => $absensi->final_salary ?? $salaryData['final_salary'],
                'is_weekend_overtime'   => $isWeekendOvertime, // 🆕
            ]);

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
        $absensi = Absensi::find($id);

        if (!$absensi) {
            return response()->json([
                'success' => false,
                'message' => 'Data absensi tidak ditemukan.'
            ], 404);
        }

        // ✅ SIMPLE CHECK (hapus semua log)
        if ($absensi->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.'
            ], 403);
        }

        // Format response (sama kayak sebelumnya)
        $formattedAbsensi = [
            'id' => $absensi->id,
            'user_id' => $absensi->user_id,
            // ... dst
            'lembur_start' => $absensi->lembur_start
                ? Carbon::parse($absensi->lembur_start)->format('H:i')
                : null,
            'lembur_end' => $absensi->lembur_end
                ? Carbon::parse($absensi->lembur_end)->format('H:i')
                : null,
            // ... dst
        ];

        return response()->json([
            'success' => true,
            'absensi' => $formattedAbsensi
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
        ], 500);
    }
}

}
