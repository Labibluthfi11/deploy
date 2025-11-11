<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AbsensiRekapExport;
use App\Exports\AbsensiUserExport;

class AbsensiAdminController extends Controller
{
    /**
     * Dashboard Absensi Semua Karyawan
     */
    public function index(Request $request)
    {
        return $this->indexByEmploymentType($request, null);
    }

    /**
     * Dashboard Absensi Karyawan Organik
     */
    public function indexOrganik(Request $request)
    {
        return $this->indexByEmploymentType($request, 'organik');
    }

    /**
     * Dashboard Absensi Karyawan Freelance
     */
    public function indexFreelance(Request $request)
    {
        return $this->indexByEmploymentType($request, 'freelance');
    }

    /**
     * Helper Dashboard berdasarkan employment_type
     */
    private function indexByEmploymentType(Request $request, ?string $type)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        // Filter user sesuai employment_type (organik/freelance/semua)
        $userFilter = function (Builder $query) use ($type) {
            if ($type) {
                $query->where('employment_type', $type);
            }
        };

        $dashboardTitle = match ($type) {
            'organik'   => 'Dashboard Absensi Karyawan Organik',
            'freelance' => 'Dashboard Absensi Karyawan Freelance',
            default     => 'Dashboard Absensi Semua Karyawan',
        };

        // --- Pending Approvals
        $pendingApprovals = collect([]);
        /*
            CATATAN PENTING:
            Data 'pendingApprovals' kini TIDAK diambil secara lengkap di Controller ini
            karena sudah dipindahkan ke ApprovalController untuk workflow bertahap.
        */

        // --- Status Harian
        $today = Carbon::today();
        $users = User::where($userFilter)->get();
        $dailyStatuses = [];
        $dailyStatusesOrganik = [];
        $dailyStatusesFreelance = [];

        foreach ($users as $user) {
            // Absensi yang statusnya sudah Approved
            $absensiTodayApproved = Absensi::where('user_id', $user->id)
                ->whereDate('check_in_at', $today)
                ->where('status_approval', 'approved')
                ->first();

            $statusHariIni = 'Belum Absen';
            $checkInTime = $checkOutTime = $fotoCheckIn = $fotoCheckOut = null;
            $lateMinutes = 0;

            if ($absensiTodayApproved) {
                if ($absensiTodayApproved->status === 'hadir') {
                    $statusHariIni = 'Hadir';
                    $checkInTime = $absensiTodayApproved->check_in_at;
                    $checkOutTime = $absensiTodayApproved->check_out_at;
                    $fotoCheckIn = $absensiTodayApproved->foto_masuk;
                    $fotoCheckOut = $absensiTodayApproved->foto_pulang;
                    $lateMinutes = $absensiTodayApproved->late_minutes ?? 0;
                } else {
                    $statusHariIni = ucfirst($absensiTodayApproved->status);
                    if ($absensiTodayApproved->tipe) {
                        $statusHariIni .= ' (' . ucfirst($absensiTodayApproved->tipe) . ')';
                    }
                    $fotoCheckIn = $absensiTodayApproved->foto_masuk;
                }
            } else {
                // Absensi yang statusnya Pending (menunggu approval bertahap)
                $pendingAbsensiToday = Absensi::where('user_id', $user->id)
                    ->whereDate('check_in_at', $today)
                    ->where('status_approval', 'pending')
                    ->first();

                if ($pendingAbsensiToday) {
                    $statusHariIni = ucfirst($pendingAbsensiToday->status);
                    if ($pendingAbsensiToday->tipe) {
                        $statusHariIni .= ' (' . ucfirst($pendingAbsensiToday->tipe) . ')';
                    }
                    $statusHariIni .= ' (Pending Lvl: ' . $pendingAbsensiToday->current_approval_level . ')';
                    $checkInTime = $pendingAbsensiToday->check_in_at;
                    $fotoCheckIn = $pendingAbsensiToday->foto_masuk;
                    $lateMinutes = $pendingAbsensiToday->late_minutes ?? 0;
                }
            }

            $dailyData = [
                'user' => $user,
                'status' => $statusHariIni,
                'check_in_time' => $checkInTime,
                'check_out_time' => $checkOutTime,
                'foto_check_in' => $fotoCheckIn,
                'foto_check_out' => $fotoCheckOut,
                'late_minutes' => $lateMinutes,
            ];

            $dailyStatuses[] = $dailyData;

            // Pisahkan berdasarkan employment_type untuk halaman "Semua"
            if ($user->employment_type === 'organik') {
                $dailyStatusesOrganik[] = $dailyData;
            } elseif ($user->employment_type === 'freelance') {
                $dailyStatusesFreelance[] = $dailyData;
            }
        }

        // --- Statistik Bulanan
        $absensiQueryFilter = function (Builder $query) use ($userFilter) {
            $query->whereHas('user', $userFilter);
        };

        // Semua query statistik harus tetap mengambil yang status_approval-nya 'approved' saja
        $totalHadir = Absensi::where('status_approval', 'approved')
            ->where('status', 'hadir')
            ->whereYear('check_in_at', $year)
            ->whereMonth('check_in_at', $month)
            ->where($absensiQueryFilter)
            ->count();

        $totalIzin = Absensi::where('status_approval', 'approved')
            ->where('status', 'izin')
            ->whereYear('check_in_at', $year)
            ->whereMonth('check_in_at', $month)
            ->where($absensiQueryFilter)
            ->count();

        $totalSakit = Absensi::where('status_approval', 'approved')
            ->where('status', 'sakit')
            ->whereYear('check_in_at', $year)
            ->whereMonth('check_in_at', $month)
            ->where($absensiQueryFilter)
            ->count();

        $totalLembur = Absensi::where('status_approval', 'approved')
            ->where('tipe', 'lembur')
            ->whereYear('check_in_at', $year)
            ->whereMonth('check_in_at', $month)
            ->where($absensiQueryFilter)
            ->count();

        // --- Perbandingan Organik vs Freelance (hanya untuk halaman "semua")
        $comparison = null;
        if (!$type) { // Hanya untuk halaman "semua"
            $comparison = [
                'organik' => [
                    'hadir' => Absensi::whereHas('user', fn($q) => $q->where('employment_type', 'organik'))
                        ->where('status_approval', 'approved')
                        ->where('status', 'hadir')
                        ->whereYear('check_in_at', $year)
                        ->whereMonth('check_in_at', $month)
                        ->count(),
                    'izin' => Absensi::whereHas('user', fn($q) => $q->where('employment_type', 'organik'))
                        ->where('status_approval', 'approved')
                        ->where('status', 'izin')
                        ->whereYear('check_in_at', $year)
                        ->whereMonth('check_in_at', $month)
                        ->count(),
                    'sakit' => Absensi::whereHas('user', fn($q) => $q->where('employment_type', 'organik'))
                        ->where('status_approval', 'approved')
                        ->where('status', 'sakit')
                        ->whereYear('check_in_at', $year)
                        ->whereMonth('check_in_at', $month)
                        ->count(),
                    'lembur' => Absensi::whereHas('user', fn($q) => $q->where('employment_type', 'organik'))
                        ->where('status_approval', 'approved')
                        ->where('tipe', 'lembur')
                        ->whereYear('check_in_at', $year)
                        ->whereMonth('check_in_at', $month)
                        ->count(),
                ],
                'freelance' => [
                    'hadir' => Absensi::whereHas('user', fn($q) => $q->where('employment_type', 'freelance'))
                        ->where('status_approval', 'approved')
                        ->where('status', 'hadir')
                        ->whereYear('check_in_at', $year)
                        ->whereMonth('check_in_at', $month)
                        ->count(),
                    'izin' => Absensi::whereHas('user', fn($q) => $q->where('employment_type', 'freelance'))
                        ->where('status_approval', 'approved')
                        ->where('status', 'izin')
                        ->whereYear('check_in_at', $year)
                        ->whereMonth('check_in_at', $month)
                        ->count(),
                    'sakit' => Absensi::whereHas('user', fn($q) => $q->where('employment_type', 'freelance'))
                        ->where('status_approval', 'approved')
                        ->where('status', 'sakit')
                        ->whereYear('check_in_at', $year)
                        ->whereMonth('check_in_at', $month)
                        ->count(),
                    'lembur' => Absensi::whereHas('user', fn($q) => $q->where('employment_type', 'freelance'))
                        ->where('status_approval', 'approved')
                        ->where('tipe', 'lembur')
                        ->whereYear('check_in_at', $year)
                        ->whereMonth('check_in_at', $month)
                        ->count(),
                ],
            ];
        }

        // --- Grafik Bulanan PER KATEGORI (for Chart.js Line)
        $grafikBulananOrganik = [];
        $grafikBulananFreelance = [];
        for ($m = 1; $m <= 12; $m++) {
            $grafikBulananOrganik[$m] = Absensi::whereHas('user', fn($q) => $q->where('employment_type', 'organik'))
                ->where('status_approval', 'approved')
                ->whereYear('check_in_at', $year)
                ->whereMonth('check_in_at', $m)
                ->count();

            $grafikBulananFreelance[$m] = Absensi::whereHas('user', fn($q) => $q->where('employment_type', 'freelance'))
                ->where('status_approval', 'approved')
                ->whereYear('check_in_at', $year)
                ->whereMonth('check_in_at', $m)
                ->count();
        }

        // --- Grafik Bulanan (all, jika mau)
        $grafikBulanan = [];
        for ($m = 1; $m <= 12; $m++) {
            $grafikBulanan[$m] = Absensi::whereYear('check_in_at', $year)
                ->whereMonth('check_in_at', $m)
                ->where('status_approval', 'approved')
                ->where($absensiQueryFilter)
                ->count();
        }

        return view('admin.absensi.index', compact(
            'users',
            'month',
            'year',
            'grafikBulanan',
            'grafikBulananOrganik',
            'grafikBulananFreelance',
            'pendingApprovals',
            'dailyStatuses',
            'dailyStatusesOrganik',
            'dailyStatusesFreelance',
            'totalHadir',
            'totalIzin',
            'totalSakit',
            'totalLembur',
            'dashboardTitle',
            'comparison'
        ))->with('currentStatus', $type ?? 'semua');
    }

    /**
     * ❗️❗️ INI METHOD 'SHOW' YANG UDAH DIBENERIN ❗️❗️
     * Detail absensi user
     */
    public function show(Request $request, User $user)
    {
        $filterType = $request->input('filter_type', 'all');
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $week = $request->input('week', 1);

        // Query dasar
        $query = Absensi::where('user_id', $user->id);

        // Apply filter berdasarkan tipe
        if ($filterType === 'yearly') {
            $query->whereYear('check_in_at', $year);
        } elseif ($filterType === 'monthly') {
            $query->whereYear('check_in_at', $year)
                  ->whereMonth('check_in_at', $month);
        } elseif ($filterType === 'weekly') {
            // Hitung tanggal awal dan akhir minggu
            $firstMonday = \Carbon\Carbon::create($year, $month, 1)->startOfMonth()->next(\Carbon\Carbon::MONDAY);
            if ($firstMonday->month != $month) {
                $firstMonday = \Carbon\Carbon::create($year, $month, 1);
            }
            $startDate = (clone $firstMonday)->addWeeks($week - 1)->startOfWeek();
            $endDate = (clone $startDate)->endOfWeek();

            $query->whereBetween('check_in_at', [$startDate, $endDate]);
        }
        // Kalo 'all', gausah di-filter

        // Ambil semua data absensi yang terfilter (termasuk pending/rejected)
        $absensi = $query->orderBy('check_in_at', 'desc')->get();

        // ❗️❗️ INI BAGIAN BARUNYA ❗️❗️
        // Kita filter sekali lagi HANYA yang 'approved' untuk ngitung statistik
        $approvedAbsensi = $absensi->where('status_approval', 'approved');

        // Hitung statistik berdasarkan data yang terfilter (HANYA YANG APPROVED)
        $absensiStats = [
    'hadir' => $approvedAbsensi->where('status', 'hadir')->count(),
    'telat' => $approvedAbsensi->where('late_minutes', '>', 0)->count(),
    'izin' => $approvedAbsensi->where('status', 'izin')->count(),
    'sakit' => $approvedAbsensi->where('status', 'sakit')->count(),
    'lembur' => $approvedAbsensi->where('tipe', 'lembur')->count(),
    'total_absensi' => $approvedAbsensi->count(),
    'total_gaji_pokok' => $approvedAbsensi->sum('base_salary'),
    'total_potongan' => $approvedAbsensi->sum('late_penalty'),
    'total_gaji_lembur' => $approvedAbsensi->sum('overtime_pay'),
    'total_gaji_bersih' => $approvedAbsensi->sum('final_salary'),
];

// Tambahan khusus untuk filter mingguan
$weeklySummary = null;
if ($filterType === 'weekly') {
    $weeklySummary = [
        'hadir' => $approvedAbsensi->where('status', 'hadir')->count(),
        'sakit' => $approvedAbsensi->where('status', 'sakit')->count(),
        'izin' => $approvedAbsensi->where('status', 'izin')->count(),
        'telat' => $approvedAbsensi->where('late_minutes', '>', 0)->count(),
        'lembur' => $approvedAbsensi->where('tipe', 'lembur')->count(),
        'total_menit_telat' => $approvedAbsensi->sum('late_minutes'),
        'total_menit_lembur' => $approvedAbsensi->sum('overtime_minutes'),
        'total_gaji' => $approvedAbsensi->sum('final_salary'),
    ];
}

return view('admin.absensi.user', compact('user', 'absensi', 'absensiStats', 'weeklySummary'));
    }

    /**
     * ✅ FIXED: Rekap bulanan semua user dengan perhitungan lembur yang benar
     */
    public function recap(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $range = $request->input('range', 'monthly');
        $week = $request->input('week', null);

        // Tentukan tanggal awal & akhir
        if ($range === 'weekly' && $week) {
            $firstMonday = Carbon::create($year, $month, 1)->startOfMonth()->next(Carbon::MONDAY);
            if ($firstMonday->month != $month) {
                $firstMonday = Carbon::create($year, $month, 1);
            }
            $startDate = (clone $firstMonday)->addWeeks($week - 1)->startOfWeek();
            $endDate = (clone $startDate)->endOfWeek();
        } else {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        }

        $users = User::all();
        $recapData = [];

        foreach ($users as $user) {
            $absensiUser = Absensi::where('user_id', $user->id)
                ->whereBetween('check_in_at', [$startDate, $endDate])
                ->where('status_approval', 'approved')
                ->get();

            // ✅ PERBAIKAN LOGIKA:
            // final_salary di DB = (base - late_penalty) + overtime_pay (dari ApprovalController)
            // Jadi, total_gaji = sum(final_salary)
            $totalGaji = $absensiUser->sum('final_salary') ?? 0;
            $totalGajiLembur = $absensiUser->sum('overtime_pay') ?? 0;
            $totalMenitLembur = $absensiUser->sum('overtime_minutes');


            $recapData[] = [
                'user' => $user,
                'total_hadir' => $absensiUser->where('status', 'hadir')->count(),
                'total_izin' => $absensiUser->where('status', 'izin')->count(),
                'total_sakit' => $absensiUser->where('status', 'sakit')->count(),
                'total_lembur' => $absensiUser->where('tipe', 'lembur')->count(),
                'total_telat' => $absensiUser->where('late_minutes', '>', 0)->count(),
                'total_menit_telat' => $absensiUser->sum('late_minutes'),
                'total_menit_lembur' => $totalMenitLembur,
                'total_gaji_lembur' => $totalGajiLembur,
                'total_gaji' => $totalGaji, // ✅ FIXED: Ini adalah total bersih
            ];
        }

        return view('admin.absensi.recap', compact(
            'recapData', 'month', 'year', 'range', 'week'
        ))->with('selectedMonth', $month)
          ->with('selectedYear', $year);
    }

    /**
     * ✅ FIXED: Export rekap bulanan ke Excel dengan perhitungan lembur yang benar
     */
    public function exportRecap(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $type = $request->input('type', 'all'); // all, organik, freelance
        $range = $request->input('range', 'monthly'); // 'monthly' atau 'weekly'
        $week = $request->input('week', null);

        // Tentukan tanggal awal & akhir berdasarkan range
        if ($range === 'weekly' && $week) {
            $firstMonday = Carbon::create($year, $month, 1)->startOfMonth()->next(Carbon::MONDAY);

            if ($firstMonday->month != $month) {
                $firstMonday = Carbon::create($year, $month, 1);
            }

            $startDate = (clone $firstMonday)->addWeeks($week - 1)->startOfWeek();
            $endDate = (clone $startDate)->endOfWeek();
        } else {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        }

        // Ambil semua user
        $users = User::all();
        $recapData = [];

        foreach ($users as $user) {
            $absensiUser = Absensi::where('user_id', $user->id)
                ->whereBetween('check_in_at', [$startDate, $endDate])
                ->where('status_approval', 'approved')
                ->get();

            // ✅ PERBAIKAN LOGIKA: Sama kayak di recap()
            $totalGaji = $absensiUser->sum('final_salary') ?? 0;
            $totalGajiLembur = $absensiUser->sum('overtime_pay') ?? 0;
            $totalMenitLembur = $absensiUser->sum('overtime_minutes');

            $recapData[] = [
                'user' => $user,
                'total_hadir' => $absensiUser->where('status', 'hadir')->count(),
                'total_izin' => $absensiUser->where('status', 'izin')->count(),
                'total_sakit' => $absensiUser->where('status', 'sakit')->count(),
                'total_lembur' => $absensiUser->where('tipe', 'lembur')->count(),
                'total_telat' => $absensiUser->where('late_minutes', '>', 0)->count(),
                'total_menit_lembur' => $totalMenitLembur, // 🆕 Data baru
                'total_gaji_lembur' => $totalGajiLembur, // 🆕 Data baru
                'total_menit_telat' => $absensiUser->sum('late_minutes'),
                'total_gaji' => $totalGaji, // ✅ FIXED: Ini adalah total bersih
                'total_absensi' => $absensiUser->count(),
            ];
        }

        // Tentukan nama file berdasarkan range
        $filenameSuffix = $range === 'weekly' && $week
            ? "Minggu_{$week}"
            : Carbon::createFromFormat('!m', $month)->format('M');

        $typeLabel = match($type) {
            'organik' => 'Organik',
            'freelance' => 'Freelance',
            default => 'All'
        };

        $filename = "Rekap_Absensi_{$typeLabel}_{$filenameSuffix}_{$year}.xlsx";

        // Export ke Excel
        return Excel::download(
            new AbsensiRekapExport($recapData, $month, $year, $type, $range, $week),
            $filename
        );
    }

    public function exportUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $query = Absensi::where('user_id', $id);

        if ($request->filter_type === 'monthly') {
            $query->whereMonth('check_in_at', $request->month)
                  ->whereYear('check_in_at', $request->year);
        } elseif ($request->filter_type === 'weekly') {
            $query->whereYear('check_in_at', $request->year)
                  ->whereMonth('check_in_at', $request->month)
                  ->where('week_number', $request->week);
        } elseif ($request->filter_type === 'yearly') {
            $query->whereYear('check_in_at', $request->year);
        }

        $absensi = $query->get();
        $fileName = "Absensi_{$user->name}_" . now()->format('Ymd_His') . ".xlsx";

        return Excel::download(
            new AbsensiUserExport($absensi, $user, $request->filter_type, $request->month, $request->year, $request->week),
            $fileName
        );
    }
}

