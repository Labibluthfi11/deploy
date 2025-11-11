<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Absensi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Models\Notification;

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
    // 🆕 METHOD: Hitung keterlambatan
private function calculateLateMinutes($checkInTime): int
{
    $checkIn = Carbon::parse($checkInTime);
    $standardTime = $checkIn->copy()->setTime(8, 0, 0);

    if ($checkIn->greaterThan($standardTime)) {
        // ✅ Hitung menit dari jam 8 ke waktu check-in (PASTI POSITIF)
        return (int) abs($checkIn->diffInMinutes($standardTime));
    }

    return 0;
}
    // Update method absenMasuk di App\Http\Controllers\Api\AbsensiController

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

        // 🆕 Hitung keterlambatan
        $lateMinutes = 0;
        if ($request->status === 'hadir') {
            $lateMinutes = $this->calculateLateMinutes($checkInTime);
        }

        // 🆕 Hitung gaji
        $salaryData = Absensi::calculateSalary($lateMinutes, $request->status);

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
        ]);

        $absensi->load('user');
        $absensi->foto_masuk_url = Storage::url($absensi->foto_masuk);

        return response()->json([
            'message' => 'Absensi masuk berhasil',
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

   public function meAbsensi()
{
    $userId = Auth::id();

    // Ambil semua absensi dengan relasi user
    $allAbsensi = Absensi::with('user')
        ->where('user_id', $userId)
        ->orderBy('check_in_at', 'desc')
        ->get();

    $grouped = [];
    foreach ($allAbsensi as $item) {
        $date = $item->check_in_at ? Carbon::parse($item->check_in_at)->format('Y-m-d') : null;
        if (!$date) continue;

        $key = $date . '_' . ($item->tipe ?? 'hadir');
        if (!isset($grouped[$key])) {
            $grouped[$key] = [];
        }
        $grouped[$key][] = $item;
    }

    $filtered = [];
    foreach ($grouped as $group) {
        if (count($group) == 1) {
            $filtered[] = $group[0];
        } else {
            usort($group, function($a, $b) {
                $statusA = strtolower($a->status_approval ?? '');
                $statusB = strtolower($b->status_approval ?? '');
                $priority = ['approved' => 3, 'pending' => 2, 'rejected' => 1, 'ditolak' => 1];
                $prioA = $priority[$statusA] ?? 0;
                $prioB = $priority[$statusB] ?? 0;
                if ($prioA != $prioB) {
                    return $prioB - $prioA;
                }
                return $b->id - $a->id;
            });
            $filtered[] = $group[0];
        }
    }

    usort($filtered, function($a, $b) {
        $dateA = $a->check_in_at ? Carbon::parse($a->check_in_at) : Carbon::now();
        $dateB = $b->check_in_at ? Carbon::parse($b->check_in_at) : Carbon::now();
        return $dateB->timestamp - $dateA->timestamp;
    });

    // ✅ FIX CRITICAL: Transform ke array agar appended attributes (late_minutes, late_duration_text, is_late) muncul
    $result = collect($filtered)->map(function($item) {
        $item->foto_masuk_url = $item->foto_masuk ? Storage::url($item->foto_masuk) : null;
        $item->foto_pulang_url = $item->foto_pulang ? Storage::url($item->foto_pulang) : null;
        $item->file_bukti_url = $item->file_bukti ? Storage::url($item->file_bukti) : null;

        // ✅ PENTING: Konversi ke array agar appended attributes ikut
        return $item->toArray();
    });

    return response()->json(['data' => $result->values()]);
}
    // Method lainnya (absenSakit, absenLembur, resubmit) tetap sama seperti kode asli
    public function absenSakit(Request $request)
    {
        try {
            $request->validate([
                'file_bukti' => 'required|file|max:2048',
                'keterangan_izin_sakit' => 'required|string|max:500',
                'status' => 'required|in:sakit,izin',
            ]);

            $user = Auth::user();
            $today = Carbon::today();

            $existingAbsensi = Absensi::where('user_id', $user->id)
                ->whereDate('check_in_at', $today)
                ->whereIn('status_approval', ['pending','approved'])
                ->first();

            if ($existingAbsensi) {
                $tipe = $existingAbsensi->tipe ?? 'hadir';
                return response()->json([
                    'message' => 'Anda sudah memiliki catatan absensi (' . ucfirst($tipe) . ') hari ini.'
                ], 409);
            }

            $fileBuktiPath = $request->file('file_bukti')->store('bukti_sakit_izin', 'public');

            $employment = strtolower($user->employment_type ?? 'organik');
            $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];

            $absensi = Absensi::create([
                'user_id' => $user->id,
                'check_in_at' => now(),
                'status' => $request->status,
                'tipe' => $request->status,
                'status_approval' => 'pending',
                'file_bukti' => $fileBuktiPath,
                'keterangan_izin_sakit' => $request->keterangan_izin_sakit,
                'lembur_keterangan' => null,
                'catatan_admin' => null,
                'workflow_status' => $workflow,
                'current_approval_level' => 1,
                'late_minutes' => 0, // Tidak ada keterlambatan untuk sakit/izin
            ]);

            $absensi->load('user');
            $absensi->file_bukti_url = Storage::url($absensi->file_bukti);

            return response()->json(['message' => 'Pengajuan berhasil diajukan', 'data' => $absensi], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan server: ' . $e->getMessage()], 500);
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

        // 🆕 HITUNG LEMBUR MENGGUNAKAN METHOD MODEL
        $overtimeData = Absensi::calculateOvertimeFromInput(
            $lemburStart,
            $lemburEnd,
            $request->istirahat
        );

        // 🆕 HITUNG GAJI POKOK (karena lembur tetap dapat gaji harian + bonus lembur)
        $lateMinutes = $absensi->late_minutes ?? 0;
        $salaryData = Absensi::calculateSalary($lateMinutes, $absensi->status, 'lembur');

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

            // 🆕 TAMBAHKAN DATA LEMBUR
            'overtime_minutes'      => $overtimeData['minutes'],
            'overtime_pay'          => $overtimeData['pay'],

            // 🆕 UPDATE GAJI POKOK (jika belum ada)
            'base_salary'           => $absensi->base_salary ?? $salaryData['base_salary'],
            'late_penalty'          => $absensi->late_penalty ?? $salaryData['late_penalty'],
            'final_salary'          => $absensi->final_salary ?? $salaryData['final_salary'],
        ]);

        $absensi->load('user');
        $absensi->foto_pulang_url = Storage::url($absensi->foto_pulang);

        return response()->json([
            'success' => true,
            'message' => 'Absensi lembur berhasil diajukan',
            'data' => $absensi,
            'overtime_info' => [
                'minutes' => $overtimeData['minutes'],
                'pay' => 'Rp ' . number_format($overtimeData['pay'], 0, ',', '.'),
                'formatted_duration' => floor($overtimeData['minutes'] / 60) . ' jam ' . ($overtimeData['minutes'] % 60) . ' menit'
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

    public function resubmitLembur(Request $request, $id)
    {
        try {
            $request->validate([
                'foto' => 'required|image|max:2048',
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
                'jam_mulai' => 'required|date_format:H:i',
                'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
                'istirahat' => 'required|boolean',
                'keterangan' => 'required|string|max:500',
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

            if ($absensi->tipe !== 'lembur') {
                return response()->json(['success' => false, 'message' => 'Record ini bukan pengajuan lembur.'], 400);
            }

            if ($absensi->foto_pulang && Storage::disk('public')->exists($absensi->foto_pulang)) {
                Storage::disk('public')->delete($absensi->foto_pulang);
            }

            $filePath = $request->file('foto')->store('absensi_foto', 'public');
            $lokasiPulang = $request->lat . ',' . $request->lng;

            $baseDate = $absensi->check_in_at ? Carbon::parse($absensi->check_in_at)->format('Y-m-d') : Carbon::today()->format('Y-m-d');
            $lemburStart = Carbon::parse($baseDate . ' ' . $request->jam_mulai);
            $lemburEnd = Carbon::parse($baseDate . ' ' . $request->jam_selesai);

            $employment = strtolower($absensi->user->employment_type ?? 'organik');
            $startLevel = $this->determineResubmitLevel($absensi->rejected_by, $absensi->workflow_status);

            $baseWorkflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];
            $workflow = $this->resetWorkflowFromLevel($baseWorkflow, $startLevel, $employment);

            $absensi->update([
                'foto_pulang' => $filePath,
                'lokasi_pulang' => $lokasiPulang,
                'lembur_start' => $lemburStart,
                'lembur_end' => $lemburEnd,
                'lembur_rest' => $request->istirahat,
                'lembur_keterangan' => $request->keterangan,
                'tipe' => 'lembur',
                'status_approval' => 'pending',
                'workflow_status' => $workflow,
                'current_approval_level' => $startLevel,
                'rejected_by' => null,
                'rejected_at' => null,
                'catatan_admin' => null,
                'check_out_at' => now(),
                'updated_at' => now(),
            ]);

            $absensi->load('user');
            $absensi->foto_pulang_url = Storage::url($absensi->foto_pulang);

            Notification::create([
                'user_id' => $absensi->user_id,
                'title' => "Pengajuan Lembur Diajukan Ulang",
                'message' => "Pengajuan lembur kamu telah diajukan ulang dan akan direview oleh approver yang menolak sebelumnya.",
                'type' => "lembur_resubmitted",
                'target_page' => '/lembur_detail',
                'target_id' => $absensi->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan lembur berhasil diajukan ulang. Menunggu approval.',
                'data' => $absensi
            ], 200);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}
