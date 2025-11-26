<?php
// ========================================================================
// === APPROVAL CONTROLLER (FINAL FIX - LEMBUR & GAJI BERSIH AMAN) ===
// ========================================================================

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // ⬅️ PENTING: TRANSACTION

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

    // =============================================================
    // 🔥 HELPER QUERY (SEARCH & SORT A-Z)
    // =============================================================
    private function getSubmissions(Request $request, $type, $level, $status = 'pending')
    {
        $search = $request->input('search');

        $query = Absensi::with('user')
            ->whereHas('user', fn($q) => $q->where('employment_type', $type))
            ->where('current_approval_level', $level);

        // Filter status (bisa array atau string)
        if (is_array($status)) {
            $query->whereIn('status_approval', $status);
        } else {
            $query->where('status_approval', $status);
        }

        // 🔥 LOGIKA PENCARIAN 🔥
        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('id_karyawan', 'like', "%{$search}%");
            });
        }

        // 🔥 URUTKAN A-Z (Sesuai Request) 🔥
        return $query->join('users', 'absensis.user_id', '=', 'users.id')
            ->select('absensis.*') // Ambil kolom absensi aja biar id gak bentrok
            ->orderBy('users.name', 'asc') // A-Z
            ->orderBy('absensis.check_in_at', 'desc') // Tanggal terbaru
            ->get();
    }

    // ================== APPROVAL SUPERVISOR ==================
    public function supervisor(Request $request)
    {
        // Level 1 Freelance (Pending/Rejected)
        $freelanceYuli = $this->getSubmissions($request, 'freelance', 1, ['pending', 'rejected']);

        return view('admin.absensi.approval.supervisor', [
            'freelanceYuli' => $freelanceYuli,
            'submissions' => $freelanceYuli, // Variabel umum buat view
            'approverName' => 'Supervisor',
            'approverRole' => 'Level 1 (Freelance)',
        ]);
    }

    // ================== APPROVAL MANAGER ==================
    public function manager(Request $request)
    {
        // Level 2 Freelance
        $freelanceManager = $this->getSubmissions($request, 'freelance', 2);

        // Level 1 Organik
        $organikManager = $this->getSubmissions($request, 'organik', 1);

        return view('admin.absensi.approval.manager', [
            'freelanceManager' => $freelanceManager,
            'organikManager'   => $organikManager,
            'approverName'     => 'Manager',
            'approverRole'     => 'Level 2 Approval',
        ]);
    }

    // ================== APPROVAL HRGA ==================
    public function hrga(Request $request)
    {
        // Level 3 Freelance
        $freelanceHRGA = $this->getSubmissions($request, 'freelance', 3);

        // Level 2 Organik
        $organikHRGA = $this->getSubmissions($request, 'organik', 2);

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

        // Mapping nama approver
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

            // Saat reject, reset gaji lembur & kembalikan gaji ke normal
            $absensi->update([
                'status_approval' => 'rejected',
                'catatan_admin' => $request->catatan_admin,
                'rejected_by' => $rejectedBy,
                'rejected_at' => now(),
                'workflow_status' => $resetWorkflow,
                'current_approval_level' => $resubmitLevel,
                'overtime_minutes' => 0,
                'overtime_pay'     => 0,
                'final_salary'     => ($absensi->base_salary ?? 0) - ($absensi->late_penalty ?? 0), // Reset ke Pokok - Denda
            ]);

            // 🔥 AUTO-REJECT SEMUA CHILD RECORDS
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

                // Pastikan base_salary & late_penalty ada (kalau null, hitung dulu)
                if ($absensi->base_salary === null) {
                    $salaryData = Absensi::calculateSalary($absensi->late_minutes ?? 0, $absensi->status, $absensi->tipe);
                    $absensi->base_salary = $salaryData['base_salary'];
                    $absensi->late_penalty = $salaryData['late_penalty'];
                    // final_salary nanti di-override di bawah
                    $absensi->save();
                    $absensi->refresh();
                }

                $overtimeMinutes = 0;
                $overtimePay = 0;

                // 🔥 HITUNG LEMBUR (JIKA TIPE LEMBUR)
                if (strtolower($absensi->tipe ?? '') === 'lembur' && $absensi->lembur_start && $absensi->lembur_end) {
                    try {
                        $overtimeData = Absensi::calculateOvertimeFromInput(
                            $absensi->lembur_start,
                            $absensi->lembur_end,
                            (bool) $absensi->lembur_rest
                        );

                        $overtimeMinutes = $overtimeData['minutes'];
                        $overtimePay = $overtimeData['pay'];

                    } catch (\Exception $e) {
                        Log::error('❌ Gagal kalkulasi lembur saat approval', [
                            'absensi_id' => $absensi->id,
                            'error' => $e->getMessage()
                        ]);
                        // Kalau error, lembur dianggap 0 biar gak ngerusak data lain
                        $overtimeMinutes = 0;
                        $overtimePay = 0;
                    }
                }

                // 🔥 HITUNG FINAL SALARY YANG BENAR 🔥
                // Rumus: (Gaji Pokok - Potongan Telat) + Uang Lembur
                $gajiPokok = $absensi->base_salary ?? 0;
                $potongan  = $absensi->late_penalty ?? 0;
                $newFinalSalary = ($gajiPokok - $potongan) + $overtimePay;

                // 🔥 UPDATE DATABASE DENGAN TRANSACTION 🔥
                DB::transaction(function () use ($absensi, $workflowStatus, $overtimeMinutes, $overtimePay, $newFinalSalary, $currentApprover, $submissionType, $targetPage) {

                    $absensi->update([
                        'status_approval' => 'approved',
                        'approved_at' => now(),
                        'workflow_status' => $workflowStatus,
                        'rejected_by' => null,
                        'rejected_at' => null,
                        'overtime_minutes' => $overtimeMinutes,
                        'overtime_pay'     => $overtimePay,
                        'final_salary'     => $newFinalSalary, // ✅ MASUKIN HASIL HITUNGAN BARU
                    ]);

                    // 🔥 AUTO-APPROVE SEMUA CHILD RECORDS
                    if ($absensi->children()->exists()) {
                        $absensi->children()->update([
                            'status_approval' => 'approved',
                            'workflow_status' => $workflowStatus,
                            'current_approval_level' => $absensi->current_approval_level,
                            'rejected_by' => null,
                            'rejected_at' => null,
                            'approved_at' => now(),
                            'overtime_minutes' => 0, // Child records biasanya gak ada lembur
                            'overtime_pay' => 0,
                            'final_salary' => ($absensi->base_salary ?? 0) - ($absensi->late_penalty ?? 0),
                        ]);

                        Log::info("✅ Synced APPROVE status to " . $absensi->children()->count() . " child records");
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

                // 🔥 SYNC KE CHILD RECORDS (LEVEL NAIK)
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
