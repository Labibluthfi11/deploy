<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    protected $workflowMap = [
    'produksi' => [1 => 'Supervisor', 2 => 'Manager', 3 => 'HRGA'],
    'office'   => [1 => 'Manager', 2 => 'HRGA'],
];

    public function index()
    {
        return redirect()->route('admin.absensi.approval.supervisor');
    }

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


    private function getSubmissions(Request $request, $type, $level, $status = 'pending')
    {
        $search = $request->input('search');

        $query = Absensi::with('user')
            ->whereHas('user', fn($q) => $q->where('work_location', $type))
            ->where('current_approval_level', $level);

        if (is_array($status)) {
            $query->whereIn('status_approval', $status);
        } else {
            $query->where('status_approval', $status);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('id_karyawan', 'like', "%{$search}%");
            });
        }

        return $query->join('users', 'absensis.user_id', '=', 'users.id')
            ->select('absensis.*')
            ->orderBy('users.name', 'asc')
            ->orderBy('absensis.check_in_at', 'desc')
            ->get();
    }

    public function supervisor(Request $request)
    {
        $produksiSupervisor = $this->getSubmissions($request, 'produksi', 1, ['pending', 'rejected']);
return view('admin.absensi.approval.supervisor', [
    'freelanceYuli' => $produksiSupervisor,
    'submissions'   => $produksiSupervisor,
    'approverName'  => 'Supervisor',
    'approverRole'  => 'Level 1 (Produksi)',
]);
    }

    public function manager(Request $request)
    {
        $produksiManager = $this->getSubmissions($request, 'produksi', 2);
        $officeManager   = $this->getSubmissions($request, 'office', 1);

return view('admin.absensi.approval.manager', [
    'freelanceManager' => $produksiManager,
    'organikManager'   => $officeManager,
    'approverName'     => 'Manager',
    'approverRole'     => 'Level 2 Approval',
]);
    }

    public function hrga(Request $request)
    {
        $produksiHRGA = $this->getSubmissions($request, 'produksi', 3);
        $officeHRGA   = $this->getSubmissions($request, 'office', 2);

return view('admin.absensi.approval.hrga', [
    'freelanceHRGA' => $produksiHRGA,
    'organikHRGA'   => $officeHRGA,
    'approverName'  => 'HRGA',
    'approverRole'  => 'Final Approval',
]);
    }

    public function handleAction(Request $request, Absensi $absensi, string $action)
    {
        if ($absensi->status_approval !== 'pending') {
            return back()->with('error', 'Pengajuan sudah diproses sebelumnya.');
        }

        $userTipe = $absensi->user->work_location ?? 'office';
        $currentLevel = $absensi->current_approval_level ?? 1;
        $workflowMap = $this->workflowMap[$userTipe] ?? $this->workflowMap['office'];
        $currentApprover = $workflowMap[$currentLevel] ?? 'Unknown';

        $approverToKey = [
            'Supervisor' => 'supervisor',
            'Manager'    => 'manager',
            'HRGA'       => 'hrga',
        ];
        $workflowKey = $approverToKey[$currentApprover] ?? strtolower(str_replace(' ', '_', $currentApprover));

        $workflowStatus = is_array($absensi->workflow_status)
            ? $absensi->workflow_status
            : (json_decode($absensi->workflow_status ?? '[]', true) ?: []);

        if(empty($workflowStatus)) {
             $workflowStatus = $this->workflowTemplates[$userTipe] ?? $this->workflowTemplates['office'];
        }

        $submissionType = $absensi->tipe ?? 'absensi';
        $targetPageMap = [
            'lembur'  => '/lembur_detail',
            'sakit'   => '/sakit_detail',
            'izin'    => '/izin_detail',
            'absensi' => '/absensi_detail',
        ];
        $targetPage = $targetPageMap[$submissionType] ?? '/absensi';

        // =====================================================
        // 🟥 REJECT ACTION
        // =====================================================
        if ($action === 'reject') {
    $request->validate(['catatan_admin' => 'required|min:5']);
    $workflowStatus[$workflowKey] = 'rejected';
    $rejectedBy = $workflowKey;
    $resubmitLevel = $this->determineResubmitLevel($rejectedBy, $workflowStatus);
    $resetWorkflow = $this->resetWorkflowFromLevel($workflowStatus, $resubmitLevel, $userTipe);

    // ✅ CEK APAKAH INI IZIN/SAKIT (GAK ADA GAJI)
    $isLeaveType = in_array(strtolower($absensi->status ?? ''), ['izin', 'sakit']) ||
                   in_array(strtolower($absensi->tipe ?? ''), ['izin', 'sakit']);

    Log::info('🔍 [REJECT] Processing rejection', [
        'id' => $absensi->id,
        'tipe' => $absensi->tipe,
        'status' => $absensi->status,
        'is_leave_type' => $isLeaveType,
    ]);

    // ✅ HITUNG GAJI BERDASARKAN TIPE
    if ($isLeaveType) {
        // IZIN/SAKIT → Gak ada gaji
        $finalSalary = 0;
        $baseSalary = 0;
        $latePenalty = 0;

        Log::info('💰 [REJECT] Leave type - No salary');

    } else {
        // HADIR/LEMBUR → Ada gaji pokok, tapi lembur gak masuk

        // Pastikan base_salary udah ada (kalo belum, hitung dulu)
        if ($absensi->base_salary === null) {
            $salaryData = \App\Models\Absensi::calculateSalary(
                $absensi->late_minutes ?? 0,
                $absensi->status,
                null, // Bukan lembur
                $absensi->is_weekend_overtime ?? false
            );

            $baseSalary = $salaryData['base_salary'];
            $latePenalty = $salaryData['late_penalty'];

            Log::info('💰 [REJECT] Calculated base salary', [
                'base_salary' => $baseSalary,
                'late_penalty' => $latePenalty,
            ]);

        } else {
            // Udah ada, pake yang ada
            $baseSalary = $absensi->base_salary;
            $latePenalty = $absensi->late_penalty ?? 0;

            Log::info('💰 [REJECT] Using existing base salary', [
                'base_salary' => $baseSalary,
                'late_penalty' => $latePenalty,
            ]);
        }

        // FINAL SALARY = Gaji Pokok - Potongan (TANPA LEMBUR)
        $finalSalary = $baseSalary - $latePenalty;

        Log::info('💰 [REJECT] Final salary (no overtime)', [
            'final_salary' => $finalSalary,
        ]);
    }

    // ✅ UPDATE DATABASE
    $absensi->update([
        'status_approval' => 'rejected',
        'catatan_admin' => $request->catatan_admin,
        'rejected_by' => $rejectedBy,
        'rejected_at' => now(),
        'workflow_status' => $resetWorkflow,
        'current_approval_level' => $resubmitLevel,

        // ✅ RESET LEMBUR (karena lembur ditolak)
        'overtime_minutes' => 0,
        'overtime_pay'     => 0,

        // ✅ TETEP KASIH GAJI POKOK (kalo bukan izin/sakit)
        'base_salary'   => $baseSalary,
        'late_penalty'  => $latePenalty,
        'final_salary'  => $finalSalary, // ✅ Gaji pokok - potongan (tanpa lembur)
    ]);

    Log::info('✅ [REJECT] Updated main record', [
        'id' => $absensi->id,
        'final_salary' => $finalSalary,
    ]);

    // ✅ UPDATE CHILDREN (kalo ada)
    if ($absensi->children()->exists()) {
        $absensi->children()->update([
            'status_approval' => 'rejected',
            'workflow_status' => $resetWorkflow,
            'current_approval_level' => $resubmitLevel,
            'rejected_by' => $rejectedBy,
            'rejected_at' => now(),
            'catatan_admin' => $request->catatan_admin,
            'approved_at' => null,
            'overtime_minutes' => 0,
            'overtime_pay' => 0,
            'base_salary' => $baseSalary,
            'late_penalty' => $latePenalty,
            'final_salary' => $finalSalary,
        ]);

        Log::info("✅ Synced REJECT status to " . $absensi->children()->count() . " child records");
    }

    // ✅ NOTIFIKASI
    Notification::create([
        'user_id' => $absensi->user_id,
        'title' => "Pengajuan " . ucfirst($submissionType) . " Ditolak ❌",
        'message' => "Pengajuan kamu ditolak oleh $currentApprover. Alasan: " . $request->catatan_admin,
        'type' => "{$submissionType}_rejected",
        'target_page' => $targetPage,
        'target_id' => $absensi->id,
    ]);

    return back()->with('success', 'Pengajuan ditolak. Gaji pokok tetap dihitung.');
}

        // =====================================================
        // ✅ APPROVE ACTION
        // =====================================================
        if ($action === 'approve') {
            $maxLevel = count($workflowMap);
            $workflowStatus[$workflowKey] = 'approved';

            if ($currentLevel >= $maxLevel) {
                // ✅ FINAL APPROVAL (HRGA)

                // 🔥 FIX: CEK APAKAH INI IZIN/SAKIT (BUKAN LEMBUR/HADIR)
                $isLeaveType = in_array(strtolower($absensi->status ?? ''), ['izin', 'sakit']) ||
                               in_array(strtolower($absensi->tipe ?? ''), ['izin', 'sakit']);

                Log::info('🔍 [APPROVAL] Processing final approval', [
                    'id' => $absensi->id,
                    'tipe' => $absensi->tipe,
                    'status' => $absensi->status,
                    'is_leave_type' => $isLeaveType,
                    'has_children' => $absensi->children()->exists(),
                ]);

                // 🔥 FIX: JANGAN HITUNG GAJI UNTUK IZIN/SAKIT
                // 🔥 MODIFIKASI FINAL APPROVAL UNTUK IZIN/SAKIT 🔥
                if ($isLeaveType) {
                    Log::info('⏭️ [FINAL APPROVAL] Leave type processing');

                    DB::transaction(function () use ($absensi, $workflowStatus, $currentApprover, $submissionType, $targetPage) {
                        // 1. UPDATE DATA UTAMA (PARENT)
                        $absensi->update([
                            'status_approval' => 'approved',
                            'approved_at' => now(),
                            'workflow_status' => $workflowStatus,
                            'rejected_by' => null,
                            'rejected_at' => null,
                            'overtime_minutes' => 0,
                            'overtime_pay' => 0,
                            'base_salary' => 0,
                            'late_penalty' => 0,
                            'final_salary' => 0,
                        ]);

                        
$user = \App\Models\User::find($absensi->user_id);


if ($user && $absensi->parent_id === null) { 
    $hariCuti = $absensi->total_days ?? 1;
    
    $user->update([
        'sisa_cuti' => $user->sisa_cuti - $hariCuti,
        'total_cuti_diambil' => $user->total_cuti_diambil + $hariCuti,
    ]);

    Log::info('✅ [CUTI] Berhasil potong saldo (Dihitung dari Parent)', [
        'user' => $user->name,
        'hari' => $hariCuti
    ]);
} else {
    
    Log::info('⏭️ [CUTI] Baris Child dilewati agar tidak double cut.');
}

                        // 3. UPDATE SEMUA ANAKNYA (CHILD RECORDS) JIKA MULTI-DAY
                        if ($absensi->children()->exists()) {
                            $absensi->children()->update([
                                'status_approval' => 'approved',
                                'workflow_status' => $workflowStatus,
                                'current_approval_level' => $absensi->current_approval_level,
                                'approved_at' => now(),
                                'rejected_by' => null,
                                'rejected_at' => null,
                                'base_salary' => 0,
                                'late_penalty' => 0,
                                'final_salary' => 0,
                            ]);
                            Log::info("✅ [SYNC] Anak record ikut di-approve");
                        }

                        // 4. KIRIM NOTIFIKASI KE USER
                        \App\Models\Notification::create([
                            'user_id' => $absensi->user_id,
                            'title' => "Pengajuan " . ucfirst($submissionType) . " Disetujui ✅",
                            'message' => "Pengajuan kamu telah disetujui penuh oleh $currentApprover.",
                            'type' => "{$submissionType}_approved",
                            'target_page' => $targetPage,
                            'target_id' => $absensi->id,
                        ]);
                    });

                } else {

                    // Pastikan base_salary & late_penalty ada
                    if ($absensi->base_salary === null) {
                        $salaryData = Absensi::calculateSalary($absensi->late_minutes ?? 0, $absensi->status, $absensi->tipe);
                        $absensi->base_salary = $salaryData['base_salary'];
                        $absensi->late_penalty = $salaryData['late_penalty'];
                        $absensi->save();
                        $absensi->refresh();
                    }

                    $overtimeMinutes = 0;
                    $overtimePay = 0;

                    // HITUNG LEMBUR (hanya untuk tipe lembur yang punya data jam)
                    if (strtolower($absensi->tipe ?? '') === 'lembur' && $absensi->lembur_start && $absensi->lembur_end) {
                        try {
                            $overtimeData = Absensi::calculateOvertimeFromInput(
                                $absensi->lembur_start,
                                $absensi->lembur_end,
                                (bool) $absensi->lembur_rest
                            );

                            $overtimeMinutes = $overtimeData['minutes'];
                            $overtimePay = $overtimeData['pay'];

                            Log::info('✅ [OVERTIME] Calculated', [
                                'minutes' => $overtimeMinutes,
                                'pay' => $overtimePay
                            ]);

                        } catch (\Exception $e) {
                            Log::error('❌ Gagal kalkulasi lembur saat approval', [
                                'absensi_id' => $absensi->id,
                                'error' => $e->getMessage()
                            ]);
                            $overtimeMinutes = 0;
                            $overtimePay = 0;
                        }
                    }

                    // HITUNG FINAL SALARY
                    $gajiPokok = $absensi->base_salary ?? 0;
                    $potongan  = $absensi->late_penalty ?? 0;
                    $newFinalSalary = ($gajiPokok - $potongan) + $overtimePay;

                    Log::info('💰 [SALARY] Calculation', [
                        'base' => $gajiPokok,
                        'penalty' => $potongan,
                        'overtime' => $overtimePay,
                        'final' => $newFinalSalary
                    ]);

                    
                        DB::transaction(function () use ($absensi, $workflowStatus, $overtimeMinutes, $overtimePay, $newFinalSalary, $currentApprover, $submissionType, $targetPage) {

                            // ✅ KALAU TIPE TELAT DAN DIAPPROVE → RESET JAM MASUK KE 08:00 & LATE MINUTES KE 0
                            $updateData = [
                                'status_approval' => 'approved',
                                'approved_at'     => now(),
                                'workflow_status' => $workflowStatus,
                                'rejected_by'     => null,
                                'rejected_at'     => null,
                                'overtime_minutes'=> $overtimeMinutes,
                                'overtime_pay'    => $overtimePay,
                                'final_salary'    => $newFinalSalary,
                            ];

                            if (strtolower($absensi->tipe ?? '') === 'telat') {
                                // Reset jam masuk ke 08:00 (dianggap tidak telat)
                                $checkInDate = \Carbon\Carbon::parse($absensi->check_in_at)->format('Y-m-d');
                                $updateData['check_in_at']  = $checkInDate . ' 08:00:00';
                                $updateData['late_minutes'] = 0;
                                $updateData['rounded_late_minutes'] = 0;
                                $updateData['late_penalty'] = 0;
                                $updateData['tipe']         = null; // Reset tipe biar ga keliatan telat lagi
                                
                                // Recalculate final salary tanpa potongan
                                $updateData['final_salary'] = $absensi->base_salary ?? $newFinalSalary;
                                
                                Log::info('✅ [TELAT APPROVED] Reset check_in_at to 08:00', [
                                    'id' => $absensi->id,
                                    'original_check_in' => $absensi->check_in_at,
                                    'new_check_in' => $checkInDate . ' 08:00:00',
                                ]);
                            }

                            $absensi->update($updateData);

                            Log::info('✅ [APPROVED] Attendance/Overtime record updated', [
                                'id' => $absensi->id,
                                'final_salary' => $newFinalSalary
                            ]);

                            // Notifikasi
                            Notification::create([
                                'user_id' => $absensi->user_id,
                                'title' => "Pengajuan " . ucfirst($submissionType) . " Disetujui ✅",
                                'message' => "Pengajuan kamu telah disetujui penuh oleh $currentApprover.",
                                'type' => "{$submissionType}_approved",
                                'target_page' => $targetPage,
                                'target_id' => $absensi->id,
                            ]);
                        });
                }

            } else {
                // Belum level terakhir → lanjut ke level berikutnya
                $absensi->update([
                    'current_approval_level' => $currentLevel + 1,
                    'workflow_status' => $workflowStatus,
                ]);

                if ($absensi->children()->exists()) {
                    $absensi->children()->update([
                        'current_approval_level' => $currentLevel + 1,
                        'workflow_status' => $workflowStatus,
                    ]);

                    Log::info("✅ Synced approval level to " . $absensi->children()->count() . " child records");
                }
            }

            return back()->with('success', 'Berhasil disetujui.');
        }

        return back()->with('error', 'Aksi tidak valid.');
    }

    private function determineResubmitLevel($rejectedBy, $workflowStatus)
    {
        if (!$rejectedBy || !$workflowStatus) return 1;
        $levelMap = [
            'supervisor' => 1, 'mas_yuli' => 1,
            'manager' => 2, 'mas_nu' => 2,
            'hrga' => 3, 'mba_nadya' => 3,
        ];
        $rejectorLower = strtolower(trim($rejectedBy));
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
        if ($employment === 'produksi') {
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
}
