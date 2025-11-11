<?php
// ========================================================================
// === APPROVAL CONTROLLER (FIXED - GAJI LEMBUR COMPLETE) ===
// ========================================================================

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    // ================== APPROVAL SUPERVISOR ==================
    public function supervisor()
    {
        $freelanceYuli = Absensi::with('user')
            ->whereHas('user', fn($q) => $q->where('employment_type', 'freelance'))
            ->whereIn('status_approval', ['pending', 'rejected'])
            ->where('current_approval_level', 1)
            ->orderBy('check_in_at', 'desc')
            ->get();

        return view('admin.absensi.approval.supervisor', [
            'freelanceYuli' => $freelanceYuli,
            'submissions' => $freelanceYuli,
            'approverName' => 'Supervisor',
            'approverRole' => 'Level 1 (Freelance)',
        ]);
    }

    // ================== APPROVAL MANAGER ==================
    public function manager()
    {
        $freelanceManager = Absensi::with('user')
            ->whereHas('user', fn($u) => $u->where('employment_type', 'freelance'))
            ->where('status_approval', 'pending')
            ->where('current_approval_level', 2)
            ->orderBy('check_in_at', 'desc')
            ->get();

        $organikManager = Absensi::with('user')
            ->whereHas('user', fn($u) => $u->where('employment_type', 'organik'))
            ->where('status_approval', 'pending')
            ->where('current_approval_level', 1)
            ->orderBy('check_in_at', 'desc')
            ->get();

        return view('admin.absensi.approval.manager', [
            'freelanceManager' => $freelanceManager,
            'organikManager'   => $organikManager,
            'approverName'     => 'Manager',
            'approverRole'     => 'Level 2 Approval',
        ]);
    }

    public function hrga()
    {
        $freelanceHRGA = Absensi::with('user')
            ->whereHas('user', fn($u) => $u->where('employment_type', 'freelance'))
            ->where('status_approval', 'pending')
            ->where('current_approval_level', 3)
            ->orderBy('check_in_at', 'desc')
            ->get();

        $organikHRGA = Absensi::with('user')
            ->whereHas('user', fn($u) => $u->where('employment_type', 'organik'))
            ->where('status_approval', 'pending')
            ->where('current_approval_level', 2)
            ->orderBy('check_in_at', 'desc')
            ->get();

        return view('admin.absensi.approval.hrga', [
            'freelanceHRGA' => $freelanceHRGA,
            'organikHRGA'   => $organikHRGA,
            'approverName'  => 'HRGA',
            'approverRole'  => 'Final Approval',
        ]);
    }

    // ================== HANDLE ACTION APPROVE/REJECT ==================
    public function handleAction(Request $request, Absensi $absensi, string $action)
    {
        // Cegah double proses
        if ($absensi->status_approval !== 'pending') {
            return back()->with('error', 'Pengajuan sudah diproses sebelumnya.');
        }

        $userTipe = $absensi->user->employment_type ?? 'organik';
        $currentLevel = $absensi->current_approval_level ?? 1;
        $workflowMap = $this->workflowMap[$userTipe] ?? $this->workflowMap['organik'];
        $currentApprover = $workflowMap[$currentLevel] ?? 'Unknown';

        // 🧩 Mapping nama approver
        $approverToKey = [
            'Supervisor' => 'supervisor', // ❗️ Ganti 'mas_yuli'
            'Manager'    => 'manager',    // ❗️ Ganti 'mas_nu'
            'HRGA'       => 'hrga',       // ❗️ Ganti 'mba_nadya'
        ];
        $workflowKey = $approverToKey[$currentApprover] ?? strtolower(str_replace(' ', '_', $currentApprover));


        $workflowStatus = is_array($absensi->workflow_status)
            ? $absensi->workflow_status
            : (json_decode($absensi->workflow_status ?? '[]', true) ?: []);

        // ❗️ Pastikan template-nya konsisten
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

            // ❗️ Saat reject, kita reset gaji lemburnya juga
            $absensi->update([
                'status_approval' => 'rejected',
                'catatan_admin' => $request->catatan_admin,
                'rejected_by' => $rejectedBy,
                'rejected_at' => now(),
                'workflow_status' => $resetWorkflow,
                'current_approval_level' => $resubmitLevel,
                'overtime_minutes' => 0, // Reset
                'overtime_pay'     => 0, // Reset
                'final_salary'     => $absensi->base_salary - $absensi->late_penalty, // Kembalikan ke gaji pokok - telat
            ]);

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

                // 🔥 CRITICAL FIX: Pastikan base_salary, late_penalty, final_salary sudah ada
                // Kita panggil ini untuk mastiin data gaji pokok (sebelum lembur) ada
                if ($absensi->base_salary === null) {
                    $salaryData = Absensi::calculateSalary($absensi->late_minutes ?? 0, $absensi->status, $absensi->tipe);
                    $absensi->base_salary = $salaryData['base_salary'];
                    $absensi->late_penalty = $salaryData['late_penalty'];
                    $absensi->final_salary = $salaryData['final_salary'];
                    $absensi->save(); // Simpen dulu
                    $absensi->refresh(); // Ambil data baru
                }

                // Inisialisasi nilai default
                $newFinalSalary = $absensi->final_salary ?? 0;
                $overtimeMinutes = 0;
                $overtimePay = 0;

                // 🆕 HITUNG LEMBUR HANYA JIKA TIPE LEMBUR
                if (strtolower($absensi->tipe ?? '') === 'lembur' && $absensi->lembur_start && $absensi->lembur_end) {
                    try {
                        $overtimeData = Absensi::calculateOvertimeFromInput(
                            $absensi->lembur_start,
                            $absensi->lembur_end,
                            (bool) $absensi->lembur_rest
                        );

                        $overtimeMinutes = $overtimeData['minutes'];
                        $overtimePay = $overtimeData['pay'];

                        // Gaji bersih akhir = (Gaji Pokok - Potongan Telat) + Gaji Lembur
                        $newFinalSalary = ($absensi->final_salary ?? 0) + $overtimePay;

                    } catch (\Exception $e) {
                        Log::error('❌ Gagal kalkulasi lembur saat approval', [
                            'absensi_id' => $absensi->id,
                            'error' => $e->getMessage()
                        ]);
                        $overtimeMinutes = 0;
                        $overtimePay = 0;
                        $newFinalSalary = $absensi->final_salary ?? 0; // Gagal = Gaji bersih tetep
                    }
                }

                // 🔥 INI DIA PERBAIKANNYA (BARIS 284)
                $updateData = [
                    'status_approval' => 'approved',
                    'approved_at' => now(),
                    'workflow_status' => $workflowStatus,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'overtime_minutes' => $overtimeMinutes,
                    'overtime_pay'     => $overtimePay,
                    'final_salary'     => $newFinalSalary, // ⬅️ INI YANG ILANG TADI
                ];

                $absensi->update($updateData);

                Notification::create([
                    'user_id' => $absensi->user_id,
                    'title' => "Pengajuan " . ucfirst($submissionType) . " Disetujui ✅",
                    'message' => "Pengajuan kamu telah disetujui penuh oleh $currentApprover.",
                    'type' => "{$submissionType}_approved",
                    'target_page' => $targetPage,
                    'target_id' => $absensi->id,
                ]);

            } else {
                // Belum level terakhir → lanjut ke level berikutnya
                $absensi->update([
                    'current_approval_level' => $currentLevel + 1,
                    'workflow_status' => $workflowStatus,
                ]);
            }

            return back()->with('success', 'Berhasil disetujui.');
        }

        // =====================================================
        // ❌ INVALID ACTION
        // =====================================================
        return back()->with('error', 'Aksi tidak valid.');
    }

    // ===================== SUPPORT FUNCTION =====================
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
