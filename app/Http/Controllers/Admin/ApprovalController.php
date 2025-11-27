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
        'freelance' => [1 => 'Supervisor', 2 => 'Manager', 3 => 'HRGA'],
        'organik'   => [1 => 'Manager', 2 => 'HRGA'],
    ];

    public function index()
    {
        return redirect()->route('admin.absensi.approval.supervisor');
    }

    private $workflowTemplates = [
        'freelance' => [
            'supervisor' => 'pending',
            'manager' => 'pending',
            'hrga' => 'pending',
        ],
        'organik' => [
            'manager' => 'pending',
            'hrga' => 'pending',
        ],
    ];

    private function getSubmissions(Request $request, $type, $level, $status = 'pending')
    {
        $search = $request->input('search');

        $query = Absensi::with('user')
            ->whereHas('user', fn($q) => $q->where('employment_type', $type))
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
        $freelanceYuli = $this->getSubmissions($request, 'freelance', 1, ['pending', 'rejected']);
        return view('admin.absensi.approval.supervisor', [
            'freelanceYuli' => $freelanceYuli,
            'submissions' => $freelanceYuli,
            'approverName' => 'Supervisor',
            'approverRole' => 'Level 1 (Freelance)',
        ]);
    }

    public function manager(Request $request)
    {
        $freelanceManager = $this->getSubmissions($request, 'freelance', 2);
        $organikManager = $this->getSubmissions($request, 'organik', 1);

        return view('admin.absensi.approval.manager', [
            'freelanceManager' => $freelanceManager,
            'organikManager'   => $organikManager,
            'approverName'     => 'Manager',
            'approverRole'     => 'Level 2 Approval',
        ]);
    }

    public function hrga(Request $request)
    {
        $freelanceHRGA = $this->getSubmissions($request, 'freelance', 3);
        $organikHRGA = $this->getSubmissions($request, 'organik', 2);

        return view('admin.absensi.approval.hrga', [
            'freelanceHRGA' => $freelanceHRGA,
            'organikHRGA'   => $organikHRGA,
            'approverName'  => 'HRGA',
            'approverRole'  => 'Final Approval',
        ]);
    }

    public function handleAction(Request $request, Absensi $absensi, string $action)
    {
        if ($absensi->status_approval !== 'pending') {
            return back()->with('error', 'Pengajuan sudah diproses sebelumnya.');
        }

        $userTipe = $absensi->user->employment_type ?? 'organik';
        $currentLevel = $absensi->current_approval_level ?? 1;
        $workflowMap = $this->workflowMap[$userTipe] ?? $this->workflowMap['organik'];
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
             $workflowStatus = $this->workflowTemplates[$userTipe] ?? $this->workflowTemplates['organik'];
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

            $absensi->update([
                'status_approval' => 'rejected',
                'catatan_admin' => $request->catatan_admin,
                'rejected_by' => $rejectedBy,
                'rejected_at' => now(),
                'workflow_status' => $resetWorkflow,
                'current_approval_level' => $resubmitLevel,
                'overtime_minutes' => 0,
                'overtime_pay'     => 0,
                'final_salary'     => ($absensi->base_salary ?? 0) - ($absensi->late_penalty ?? 0),
            ]);

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
                    'final_salary' => ($absensi->base_salary ?? 0) - ($absensi->late_penalty ?? 0),
                ]);

                Log::info("✅ Synced REJECT status to " . $absensi->children()->count() . " child records");
            }

            Notification::create([
                'user_id' => $absensi->user_id,
                'title' => "Pengajuan " . ucfirst($submissionType) . " Ditolak ❌",
                'message' => "Pengajuan kamu ditolak oleh $currentApprover. Alasan: " . $request->catatan_admin,
                'type' => "{$submissionType}_rejected",
                'target_page' => $targetPage,
                'target_id' => $absensi->id,
            ]);

            return back()->with('success', 'Pengajuan ditolak dan dikembalikan ke level yang sesuai.');
        }

        // =====================================================
        // ✅ APPROVE ACTION
        // =====================================================
        if ($action === 'approve') {
            $maxLevel = count($workflowMap);
            $workflowStatus[$workflowKey] = 'approved';

            if ($currentLevel >= $maxLevel) {
                // ✅ FINAL APPROVAL (HRGA)

                // 🔥 FIX: CEK APAKAH INI RECORD IZIN/SAKIT MULTI-DAY (PARENT)
                $isMultiDayParent = ($absensi->end_date !== null && $absensi->total_days > 1);

                Log::info('🔍 [APPROVAL] Processing approval', [
                    'id' => $absensi->id,
                    'tipe' => $absensi->tipe,
                    'is_multi_day_parent' => $isMultiDayParent,
                    'has_lembur_data' => ($absensi->lembur_start && $absensi->lembur_end)
                ]);

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

                // 🔥 FIX: HANYA HITUNG LEMBUR JIKA BUKAN PARENT MULTI-DAY
                if (!$isMultiDayParent && strtolower($absensi->tipe ?? '') === 'lembur' && $absensi->lembur_start && $absensi->lembur_end) {
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
                } elseif ($isMultiDayParent) {
                    Log::info('⏭️ [SKIP] Parent multi-day record - no overtime calculation needed');
                }

                // 🔥 HITUNG FINAL SALARY
                $gajiPokok = $absensi->base_salary ?? 0;
                $potongan  = $absensi->late_penalty ?? 0;
                $newFinalSalary = ($gajiPokok - $potongan) + $overtimePay;

                Log::info('💰 [SALARY] Calculation', [
                    'base' => $gajiPokok,
                    'penalty' => $potongan,
                    'overtime' => $overtimePay,
                    'final' => $newFinalSalary
                ]);

                // 🔥 UPDATE DATABASE DENGAN TRANSACTION
                DB::transaction(function () use ($absensi, $workflowStatus, $overtimeMinutes, $overtimePay, $newFinalSalary, $currentApprover, $submissionType, $targetPage, $isMultiDayParent) {

                    $absensi->update([
                        'status_approval' => 'approved',
                        'approved_at' => now(),
                        'workflow_status' => $workflowStatus,
                        'rejected_by' => null,
                        'rejected_at' => null,
                        'overtime_minutes' => $overtimeMinutes,
                        'overtime_pay'     => $overtimePay,
                        'final_salary'     => $newFinalSalary,
                    ]);

                    Log::info('✅ [APPROVED] Parent record updated', [
                        'id' => $absensi->id,
                        'final_salary' => $newFinalSalary
                    ]);

                    // 🔥 AUTO-APPROVE SEMUA CHILD RECORDS
                    if ($absensi->children()->exists()) {
                        $childCount = $absensi->children()->count();

                        $absensi->children()->update([
                            'status_approval' => 'approved',
                            'workflow_status' => $workflowStatus,
                            'current_approval_level' => $absensi->current_approval_level,
                            'rejected_by' => null,
                            'rejected_at' => null,
                            'approved_at' => now(),
                            'overtime_minutes' => 0,
                            'overtime_pay' => 0,
                            // 🔥 FIX: Pastikan child juga punya gaji yang valid
                            'final_salary' => ($absensi->base_salary ?? 0) - ($absensi->late_penalty ?? 0),
                        ]);

                        Log::info("✅ Synced APPROVE status to {$childCount} child records");
                    }

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
}
