<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Notification;
use App\Models\ScheduledLembur;
use App\Models\IzinKeluar;
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
            ->where('current_approval_level', $level);

        if ($type === 'produksi') {
            $query->whereHas('user', fn($q) => $q->whereRaw('LOWER(work_location) = ?', ['produksi']));
        } else {
            $query->whereHas('user', fn($q) => $q->where(function($sq) {
                $sq->whereRaw('LOWER(work_location) != ?', ['produksi'])
                   ->orWhereNull('work_location');
            }));
        }

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
        $status = $request->input('status', 'pending');
        $produksiSupervisor = $this->getSubmissions($request, 'produksi', 1, $status);
        $scheduledLembur = ScheduledLembur::whereHas('user', function($q) {
                $q->whereRaw('LOWER(work_location) = ?', ['produksi']);
            })
            ->where('status', 'pending')
            ->where('current_approval_level', 1)
            ->with('user')
            ->orderBy('tanggal_lembur', 'asc')
            ->get();

        $izinKeluar = IzinKeluar::whereHas('user', function($q) {
                $q->whereRaw('LOWER(work_location) = ?', ['produksi']);
            })
            ->where('status_izin', 'selesai')
            ->where('status_approval', 'pending')
            ->where('current_approval_level', 1)
            ->with('user')
            ->orderBy('waktu_keluar', 'asc')
            ->get();

        return view('admin.absensi.approval.supervisor', [
            'freelanceYuli' => $produksiSupervisor,
            'submissions'   => $produksiSupervisor,
            'approverName'  => 'Supervisor',
            'approverRole'  => 'Level 1 (Produksi)',
            'scheduledLembur' => $scheduledLembur,
            'izinKeluar' => $izinKeluar,
            'currentStatus' => $status,
        ]);
    }

    public function manager(Request $request)
    {
        $status = $request->input('status', 'pending');
        $produksiManager = $this->getSubmissions($request, 'produksi', 2, $status);
        $officeManager   = $this->getSubmissions($request, 'office', 1, $status);
        $scheduledLembur = ScheduledLembur::where(function($query) {
                // Produksi Level 2
                $query->whereHas('user', function($q) {
                    $q->whereRaw('LOWER(work_location) = ?', ['produksi']);
                })->where('current_approval_level', 2);
            })->orWhere(function($query) {
                // Office Level 1
                $query->whereHas('user', function($q) {
                    $q->whereRaw('LOWER(work_location) != ?', ['produksi'])
                      ->orWhereNull('work_location');
                })->where('current_approval_level', 1);
            })
            ->where('status', 'pending')
            ->with('user')
            ->orderBy('tanggal_lembur', 'asc')
            ->get();

        $izinKeluar = IzinKeluar::where(function($outer) {
                $outer->where(function($query) {
                    // Produksi Level 2
                    $query->whereHas('user', function($q) {
                        $q->whereRaw('LOWER(work_location) = ?', ['produksi']);
                    })->where('current_approval_level', 2);
                })->orWhere(function($query) {
                    // Office Level 1
                    $query->whereHas('user', function($q) {
                        $q->whereRaw('LOWER(work_location) != ?', ['produksi'])
                          ->orWhereNull('work_location');
                    })->where('current_approval_level', 1);
                });
            })
            ->where('status_izin', 'selesai')
            ->where('status_approval', 'pending')
            ->with('user')
            ->orderBy('waktu_keluar', 'asc')
            ->get();

        return view('admin.absensi.approval.manager', [
            'freelanceManager' => $produksiManager,
            'organikManager'   => $officeManager,
            'izinKeluar' => $izinKeluar,
            'approverName'     => 'Manager',
            'approverRole'     => 'Level 2 Approval',
            'scheduledLembur' => $scheduledLembur,
            'currentStatus'    => $status, // ✅ Tambahin ini biar View tau lagi liat status apa
        ]);
    }

    public function hrga(Request $request)
    {
        $status = $request->input('status', 'pending');
        $produksiHRGA = $this->getSubmissions($request, 'produksi', 3, $status);
        $officeHRGA   = $this->getSubmissions($request, 'office', 2, $status);
        $scheduledLembur = ScheduledLembur::where(function($query) {
                // Produksi Level 3
                $query->whereHas('user', function($q) {
                    $q->whereRaw('LOWER(work_location) = ?', ['produksi']);
                })->where('current_approval_level', 3);
            })->orWhere(function($query) {
                // Office Level 2
                $query->whereHas('user', function($q) {
                    $q->whereRaw('LOWER(work_location) != ?', ['produksi'])
                      ->orWhereNull('work_location');
                })->where('current_approval_level', 2);
            })
            ->where('status', 'pending')
            ->with('user')
            ->orderBy('tanggal_lembur', 'asc')
            ->get();

        $izinKeluar = IzinKeluar::where(function($outer) {
                $outer->where(function($query) {
                    // Produksi Level 3
                    $query->whereHas('user', function($q) {
                        $q->whereRaw('LOWER(work_location) = ?', ['produksi']);
                    })->where('current_approval_level', 3);
                })->orWhere(function($query) {
                    // Office Level 2
                    $query->whereHas('user', function($q) {
                        $q->whereRaw('LOWER(work_location) != ?', ['produksi'])
                          ->orWhereNull('work_location');
                    })->where('current_approval_level', 2);
                });
            })
            ->where('status_izin', 'selesai')
            ->where('status_approval', 'pending')
            ->with('user')
            ->orderBy('waktu_keluar', 'asc')
            ->get();

        return view('admin.absensi.approval.hrga', [
            'freelanceHRGA' => $produksiHRGA,
            'organikHRGA'   => $officeHRGA,
            'approverName'  => 'HRGA',
            'approverRole'  => 'Final Approval',
            'scheduledLembur' => $scheduledLembur,
            'izinKeluar' => $izinKeluar,
            'currentStatus' => $status,
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

        // ✅ ACTIVITY LOG
        // Log penolakan absensi
        
        // ✅ RESET LEMBUR (karena lembur ditolak)
        'overtime_minutes' => 0,
        'overtime_pay'     => 0,

        // ✅ TETEP KASIH GAJI POKOK (kalo bukan izin/sakit)
        'base_salary'   => $baseSalary,
        'late_penalty'  => $latePenalty,
        'final_salary'  => $finalSalary, // ✅ Gaji pokok - potongan (tanpa lembur)
    ]);

    \App\Models\ActivityLog::log('Reject Pengajuan', "Pengajuan ID: {$absensi->id} dari {$absensi->user->name}", "Alasan: {$request->catatan_admin}");

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


if ($user && $absensi->parent_id === null && $absensi->hari_potong_cuti > 0) {
    $hariCuti = $absensi->hari_potong_cuti;
    
    $user->update([
        'sisa_cuti' => max(0, $user->sisa_cuti - $hariCuti),
        'total_cuti_diambil' => $user->total_cuti_diambil + $hariCuti,
    ]);
    Log::info('✅ [CUTI] Berhasil potong saldo saat approve', [
        'user' => $user->name,
        'hari_potong' => $hariCuti,
        'hari_unpaid' => $absensi->hari_unpaid,
        'sisa_cuti_sekarang' => $user->fresh()->sisa_cuti,
    ]);
} else {
    Log::info('⏭️ [CUTI] Skip potong — bukan parent atau tidak ada hari yang dipotong.');
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

                        \App\Models\ActivityLog::log('Approve Pengajuan Final', "Pengajuan ID: {$absensi->id} dari {$absensi->user->name}", "Persetujuan final oleh: {$currentApprover}");
                    });

                } else {

                    // Detect kategori
                    $idKaryawan = $absensi->user->id_karyawan ?? '';
                    $kategori = strtolower($absensi->user->employment_type ?? 'organik');
                    if (strpos($idKaryawan, 'CS-AMB') === 0) $kategori = 'borongan';
                    elseif (strpos($idKaryawan, 'MG-AMB') === 0) $kategori = 'magang';
                    elseif (strpos($idKaryawan, 'AMB') === 0) $kategori = 'freelance';

                    // Pastikan base_salary & late_penalty ada
                    if ($absensi->base_salary === null) {
                        $salaryData = Absensi::calculateSalary(
                            $absensi->late_minutes ?? 0, 
                            $absensi->status, 
                            $absensi->tipe,
                            false,
                            $absensi->check_in_at,
                            $absensi->check_out_at,
                            $kategori
                        );
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

                            \App\Models\ActivityLog::log('Approve Pengajuan Final', "Pengajuan ID: {$absensi->id} dari {$absensi->user->name} (Gaji Dihitung)", "Persetujuan final oleh: {$currentApprover}");
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

                \App\Models\ActivityLog::log('Approve Pengajuan (Bukan Final)', "Pengajuan ID: {$absensi->id} dari {$absensi->user->name}", "Naik level ke: " . ($currentLevel + 1));
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

    public function handleScheduledLemburAction(Request $request, $id, $action)
    {
        $record = ScheduledLembur::with('user')->findOrFail($id);

        if ($action === 'approve') {
            $userBranch = strtolower($record->user->work_location ?? 'office');
            $maxLevel = ($userBranch === 'produksi') ? 3 : 2;

            if ($record->current_approval_level < $maxLevel) {
                // Naik level
                $record->increment('current_approval_level');
                \App\Models\ActivityLog::log('Approve Jadwal Lembur (Bukan Final)', "Jadwal ID: {$record->id} dari {$record->user->name}", "Naik level");
                return back()->with('success', 'Lembur terjadwal berhasil di-approve dan lanjut ke level berikutnya.');
            } else {
                // Final Approve
                $record->update(['status' => 'approved']);
                
                $tanggal = \Carbon\Carbon::parse($record->tanggal_lembur)->translatedFormat('d F Y');
                \App\Models\Notification::create([
                    'user_id'     => $record->user_id,
                    'title'       => 'Lembur Terjadwal Disetujui ✅',
                    'message'     => "Lembur terjadwal kamu pada $tanggal telah disetujui sepenuhnya.",
                    'type'        => 'scheduled_lembur_approved',
                    'target_page' => '/jadwal_lembur',
                    'target_id'   => $record->id,
                ]);
                
                \App\Models\ActivityLog::log('Approve Jadwal Lembur Final', "Jadwal ID: {$record->id} dari {$record->user->name}", "Persetujuan Final");
                return back()->with('success', 'Lembur terjadwal berhasil disetujui sepenuhnya.');
            }
        }

        if ($action === 'reject') {
            $request->validate(['catatan_admin' => 'required|min:5']);
            $record->update(['status' => 'rejected']);
            $tanggal = \Carbon\Carbon::parse($record->tanggal_lembur)->translatedFormat('d F Y');
            \App\Models\Notification::create([
                'user_id'     => $record->user_id,
                'title'       => 'Lembur Terjadwal Ditolak ❌',
                'message'     => "Lembur terjadwal kamu pada $tanggal ditolak. Alasan: {$request->catatan_admin}",
                'type'        => 'scheduled_lembur_rejected',
                'target_page' => '/jadwal_lembur',
                'target_id'   => $record->id,
            ]);
            
            \App\Models\ActivityLog::log('Reject Jadwal Lembur', "Jadwal ID: {$record->id} dari {$record->user->name}", "Alasan: {$request->catatan_admin}");
            return back()->with('success', 'Lembur terjadwal berhasil ditolak.');
        }

        return back()->with('error', 'Aksi tidak valid.');
    }
    public function handleIzinKeluarAction(Request $request, $id, $action)
    {
        $record = IzinKeluar::with('user')->findOrFail($id);

        if ($action === 'approve') {
            $userBranch = strtolower($record->user->work_location ?? 'office');
            $maxLevel = ($userBranch === 'produksi') ? 3 : 2;

            if ($record->current_approval_level < $maxLevel) {
                // Naik level, belum final
                $record->increment('current_approval_level');
                \App\Models\ActivityLog::log('Approve Izin Keluar (Bukan Final)', "Izin ID: {$record->id} dari {$record->user->name}", "Naik level");
                return back()->with('success', 'Izin Keluar berhasil di-approve dan lanjut ke level berikutnya.');
            } else {
                // Final approve
                $record->update([
                    'status_approval' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                \App\Models\Notification::create([
                    'user_id'     => $record->user_id,
                    'title'       => 'Izin Keluar Disetujui ✅',
                    'message'     => 'Izin Keluar kamu telah disetujui sepenuhnya, gaji hari ini tetap dibayar penuh.',
                    'type'        => 'izin_keluar_approved',
                    'target_page' => '/izin-keluar',
                    'target_id'   => $record->id,
                ]);

                \App\Models\ActivityLog::log('Approve Izin Keluar Final', "Izin ID: {$record->id} dari {$record->user->name}", "Persetujuan Final");
                return back()->with('success', 'Izin Keluar berhasil disetujui sepenuhnya.');
            }
        }

        if ($action === 'reject') {
            $request->validate(['catatan_admin' => 'required|min:5']);

            $record->update([
                'status_approval' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Potong gaji pokok sehari penuh (overtime tetap aman)
            $absensi = Absensi::where('user_id', $record->user_id)
                ->whereDate('check_in_at', \Carbon\Carbon::parse($record->waktu_keluar)->toDateString())
                ->first();

            if ($absensi) {
                $absensi->update([
                    'base_salary' => 0,
                    'late_penalty' => 0,
                    'final_salary' => 0,
                ]);
            }

            \App\Models\Notification::create([
                'user_id'     => $record->user_id,
                'title'       => 'Izin Keluar Ditolak ❌',
                'message'     => "Izin Keluar kamu ditolak. Gaji hari ini tidak dibayar. Alasan: {$request->catatan_admin}",
                'type'        => 'izin_keluar_rejected',
                'target_page' => '/izin-keluar',
                'target_id'   => $record->id,
            ]);

            \App\Models\ActivityLog::log('Reject Izin Keluar', "Izin ID: {$record->id} dari {$record->user->name}", "Alasan: {$request->catatan_admin}, gaji hari itu dinolkan");
            return back()->with('success', 'Izin Keluar berhasil ditolak, gaji hari itu tidak dibayar.');
        }

        return back()->with('error', 'Aksi tidak valid.');
    }

    public function handleBulkAction(Request $request)
    {
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return back()->with('error', 'Tidak ada data yang dipilih.');
        }

        $count = 0;
        foreach ($ids as $id) {
            $absensi = Absensi::find($id);
            if ($absensi && $absensi->status_approval === 'pending') {
                // Gunakan logic yang sama dengan handleAction
                // Untuk mempermudah, kita panggil logic approval di sini
                // Kita asumsikan action adalah 'approve' karena tombolnya 'Approve Terpilih'
                
                $this->approveSingleRecord($absensi);
                $count++;
            }
        }

        return back()->with('success', "Berhasil menyetujui {$count} pengajuan sekaligus.");
    }

    private function approveSingleRecord(Absensi $absensi)
    {
        $userTipe = $absensi->user->work_location ?? 'office';
        $currentLevel = $absensi->current_approval_level ?? 1;
        $workflowMap = $this->workflowMap[$userTipe] ?? $this->workflowMap['office'];
        $currentApprover = $workflowMap[$currentLevel] ?? 'Unknown';
        $maxLevel = count($workflowMap);

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

        $workflowStatus[$workflowKey] = 'approved';
        $submissionType = $absensi->tipe ?? 'absensi';
        $targetPageMap = [
            'lembur'  => '/lembur_detail',
            'sakit'   => '/sakit_detail',
            'izin'    => '/izin_detail',
            'absensi' => '/absensi_detail',
        ];
        $targetPage = $targetPageMap[$submissionType] ?? '/absensi';

        if ($currentLevel >= $maxLevel) {
            // FINAL APPROVAL
            $isLeaveType = in_array(strtolower($absensi->status ?? ''), ['izin', 'sakit']) ||
                           in_array(strtolower($absensi->tipe ?? ''), ['izin', 'sakit']);

            if ($isLeaveType) {
                DB::transaction(function () use ($absensi, $workflowStatus, $currentApprover, $submissionType, $targetPage) {
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
                    if ($user && $absensi->parent_id === null && $absensi->hari_potong_cuti > 0) { 
                        $hariCuti = $absensi->hari_potong_cuti;
                        $user->update([
                            'sisa_cuti' => $user->sisa_cuti - $hariCuti,
                            'total_cuti_diambil' => $user->total_cuti_diambil + $hariCuti,
                        ]);
                        Log::info('✅ [CUTI] Berhasil potong saldo (bulk approval)', [
                            'user' => $user->name,
                            'submission_type' => $absensi->submission_type,
                            'hari' => $hariCuti
                        ]);
                    }

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
                    }

                    Notification::create([
                        'user_id' => $absensi->user_id,
                        'title' => "Pengajuan " . ucfirst($submissionType) . " Disetujui ✅",
                        'message' => "Pengajuan kamu telah disetujui penuh oleh $currentApprover.",
                        'type' => "{$submissionType}_approved",
                        'target_page' => $targetPage,
                        'target_id' => $absensi->id,
                    ]);

                    \App\Models\ActivityLog::log('Approve Pengajuan Final (Bulk)', "Pengajuan ID: {$absensi->id}", "Persetujuan final oleh: {$currentApprover}");
                });
            } else {
                // HADIR/LEMBUR
                $idKaryawan = $absensi->user->id_karyawan ?? '';
                $kategori = strtolower($absensi->user->employment_type ?? 'organik');
                if (strpos($idKaryawan, 'CS-AMB') === 0) $kategori = 'borongan';
                elseif (strpos($idKaryawan, 'MG-AMB') === 0) $kategori = 'magang';
                elseif (strpos($idKaryawan, 'AMB') === 0) $kategori = 'freelance';

                if ($absensi->base_salary === null) {
                    $salaryData = Absensi::calculateSalary($absensi->late_minutes ?? 0, $absensi->status, $absensi->tipe, false, $absensi->check_in_at, $absensi->check_out_at, $kategori);
                    $absensi->base_salary = $salaryData['base_salary'];
                    $absensi->late_penalty = $salaryData['late_penalty'];
                    $absensi->save();
                    $absensi->refresh();
                }

                $overtimePay = 0;
                $overtimeMinutes = 0;
                if (strtolower($absensi->tipe ?? '') === 'lembur' && $absensi->lembur_start && $absensi->lembur_end) {
                    $overtimeData = Absensi::calculateOvertimeFromInput($absensi->lembur_start, $absensi->lembur_end, (bool) $absensi->lembur_rest);
                    $overtimeMinutes = $overtimeData['minutes'];
                    $overtimePay = $overtimeData['pay'];
                }

                $gajiPokok = $absensi->base_salary ?? 0;
                $potongan  = $absensi->late_penalty ?? 0;
                $newFinalSalary = ($gajiPokok - $potongan) + $overtimePay;

                DB::transaction(function () use ($absensi, $workflowStatus, $overtimeMinutes, $overtimePay, $newFinalSalary, $currentApprover, $submissionType, $targetPage) {
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
                        $checkInDate = \Carbon\Carbon::parse($absensi->check_in_at)->format('Y-m-d');
                        $updateData['check_in_at']  = $checkInDate . ' 08:00:00';
                        $updateData['late_minutes'] = 0;
                        $updateData['rounded_late_minutes'] = 0;
                        $updateData['late_penalty'] = 0;
                        $updateData['tipe']         = null;
                        $updateData['final_salary'] = $absensi->base_salary ?? $newFinalSalary;
                    }

                    $absensi->update($updateData);

                    Notification::create([
                        'user_id' => $absensi->user_id,
                        'title' => "Pengajuan " . ucfirst($submissionType) . " Disetujui ✅",
                        'message' => "Pengajuan kamu telah disetujui penuh oleh $currentApprover.",
                        'type' => "{$submissionType}_approved",
                        'target_page' => $targetPage,
                        'target_id' => $absensi->id,
                    ]);

                    \App\Models\ActivityLog::log('Approve Pengajuan Final (Bulk)', "Pengajuan ID: {$absensi->id}", "Persetujuan final oleh: {$currentApprover}");
                });
            }
        } else {
            // BUKAN FINAL
            $absensi->update([
                'current_approval_level' => $currentLevel + 1,
                'workflow_status' => $workflowStatus,
            ]);

            if ($absensi->children()->exists()) {
                $absensi->children()->update([
                    'current_approval_level' => $currentLevel + 1,
                    'workflow_status' => $workflowStatus,
                ]);
            }

            \App\Models\ActivityLog::log('Approve Pengajuan (Bulk)', "Pengajuan ID: {$absensi->id}", "Naik level ke: " . ($currentLevel + 1));
        }
    }
}
