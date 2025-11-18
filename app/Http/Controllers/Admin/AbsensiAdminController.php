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
use App\Exports\SlipGajiExport;

class AbsensiAdminController extends Controller
{
    public function index(Request $request)
    {
        return $this->indexByEmploymentType($request, null);
    }

    public function indexOrganik(Request $request)
    {
        return $this->indexByEmploymentType($request, 'organik');
    }

    public function indexFreelance(Request $request)
    {
        return $this->indexByEmploymentType($request, 'freelance');
    }

    /**
     * 🔥 V5: Helper Dashboard dengan 4 KATEGORI 🔥
     */
    private function indexByEmploymentType(Request $request, ?string $type)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

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

        $pendingApprovals = collect([]);
        $today = Carbon::today();
        $users = User::where($userFilter)->get();

        // 🔥 ARRAY BARU: 4 Kategori
        $dailyStatuses = [];
        $dailyStatusesOrganik = [];
        $dailyStatusesFreelance = [];
        $dailyStatusesBorongan = []; // 🆕
        $dailyStatusesMagang = [];   // 🆕

        foreach ($users as $user) {
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

            // 🔥 PISAHKAN BERDASARKAN KATEGORI
            $kategori = $this->detectKategori($user);

            if ($kategori === 'organik') {
                $dailyStatusesOrganik[] = $dailyData;
            } elseif ($kategori === 'freelance') {
                $dailyStatusesFreelance[] = $dailyData;
            } elseif ($kategori === 'borongan') {
                $dailyStatusesBorongan[] = $dailyData; // 🆕
            } elseif ($kategori === 'magang') {
                $dailyStatusesMagang[] = $dailyData;   // 🆕
            }
        }

        // 🔥 FILTER PENCARIAN (4 Kategori)
        $searchOrganik = $request->input('search_organik');
        $searchFreelance = $request->input('search_freelance');
        $searchBorongan = $request->input('search_borongan');   // 🆕
        $searchMagang = $request->input('search_magang');       // 🆕

        if ($searchOrganik) {
            $dailyStatusesOrganik = array_filter($dailyStatusesOrganik, function($daily) use ($searchOrganik) {
                return stripos($daily['user']->name, $searchOrganik) !== false;
            });
        }

        if ($searchFreelance) {
            $dailyStatusesFreelance = array_filter($dailyStatusesFreelance, function($daily) use ($searchFreelance) {
                return stripos($daily['user']->name, $searchFreelance) !== false;
            });
        }

        // 🆕 FILTER BORONGAN
        if ($searchBorongan) {
            $dailyStatusesBorongan = array_filter($dailyStatusesBorongan, function($daily) use ($searchBorongan) {
                return stripos($daily['user']->name, $searchBorongan) !== false;
            });
        }

        // 🆕 FILTER MAGANG
        if ($searchMagang) {
            $dailyStatusesMagang = array_filter($dailyStatusesMagang, function($daily) use ($searchMagang) {
                return stripos($daily['user']->name, $searchMagang) !== false;
            });
        }

        $absensiQueryFilter = function (Builder $query) use ($userFilter) {
            $query->whereHas('user', $userFilter);
        };

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

        $comparison = null;
        if (!$type) {
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

        $grafikBulanan = [];
        for ($m = 1; $m <= 12; $m++) {
            $grafikBulanan[$m] = Absensi::whereYear('check_in_at', $year)
                ->whereMonth('check_in_at', $m)
                ->where('status_approval', 'approved')
                ->where($absensiQueryFilter)
                ->count();
        }

        usort($dailyStatuses, fn($a, $b) => $a['user']->name <=> $b['user']->name);
        usort($dailyStatusesOrganik, fn($a, $b) => $a['user']->name <=> $b['user']->name);
        usort($dailyStatusesFreelance, fn($a, $b) => $a['user']->name <=> $b['user']->name);
        usort($dailyStatusesBorongan, fn($a, $b) => $a['user']->name <=> $b['user']->name); // 🆕
        usort($dailyStatusesMagang, fn($a, $b) => $a['user']->name <=> $b['user']->name);   // 🆕

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
            'dailyStatusesBorongan',  // 🆕
            'dailyStatusesMagang',    // 🆕
            'totalHadir',
            'totalIzin',
            'totalSakit',
            'totalLembur',
            'dashboardTitle',
            'comparison'
        ))->with('currentStatus', $type ?? 'semua');
    }

    public function show(Request $request, User $user)
    {
        $filterType = $request->input('filter_type', 'all');
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $week = $request->input('week', 1);

        $query = Absensi::where('user_id', $user->id);

        if ($filterType === 'yearly') {
            $query->whereYear('check_in_at', $year);
        } elseif ($filterType === 'monthly') {
            $query->whereYear('check_in_at', $year)
                  ->whereMonth('check_in_at', $month);
        } elseif ($filterType === 'weekly') {
            $firstMonday = \Carbon\Carbon::create($year, $month, 1)->startOfMonth()->next(\Carbon\Carbon::MONDAY);
            if ($firstMonday->month != $month) {
                $firstMonday = \Carbon\Carbon::create($year, $month, 1);
            }
            $startDate = (clone $firstMonday)->addWeeks($week - 1)->startOfWeek();
            $endDate = (clone $startDate)->endOfWeek();

            $query->whereBetween('check_in_at', [$startDate, $endDate]);
        }

        $absensi = $query->orderBy('check_in_at', 'desc')->get();
        $approvedAbsensi = $absensi->where('status_approval', 'approved');

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

    public function recap(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $range = $request->input('range', 'monthly');
        $week = $request->input('week', null);

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

            $totalGaji = $absensiUser->sum('final_salary') ?? 0;
            $totalGajiLembur = $absensiUser->sum('overtime_pay') ?? 0;
            $totalMenitLembur = $absensiUser->sum('overtime_minutes');
            $totalPotongan = $absensiUser->sum('late_penalty') ?? 0;
            $kategori = $this->detectKategori($user);

            $recapData[] = [
                'user' => $user,
                'kategori' => $kategori,
                'total_hadir' => $absensiUser->where('status', 'hadir')->count(),
                'total_izin' => $absensiUser->where('status', 'izin')->count(),
                'total_sakit' => $absensiUser->where('status', 'sakit')->count(),
                'total_lembur' => $absensiUser->where('tipe', 'lembur')->count(),
                'total_telat' => $absensiUser->where('late_minutes', '>', 0)->count(),
                'total_menit_telat' => $absensiUser->sum('late_minutes'),
                'total_menit_lembur' => $totalMenitLembur,
                'total_gaji_lembur' => $totalGajiLembur,
                'total_gaji' => $totalGaji,
                'total_potongan' => $totalPotongan,
            ];
        }

        return view('admin.absensi.recap', compact(
            'recapData', 'month', 'year', 'range', 'week'
        ))->with('selectedMonth', $month)
          ->with('selectedYear', $year);
    }

    public function exportRecap(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $type = $request->input('type', 'all');
        $range = $request->input('range', 'monthly');
        $week = $request->input('week', null);

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

            $totalGaji = $absensiUser->sum('final_salary') ?? 0;
            $totalGajiLembur = $absensiUser->sum('overtime_pay') ?? 0;
            $totalMenitLembur = $absensiUser->sum('overtime_minutes');
            $totalPotongan = $absensiUser->sum('late_penalty') ?? 0;
            $kategori = $this->detectKategori($user);

            $recapData[] = [
                'user' => $user,
                'kategori' => $kategori,
                'total_hadir' => $absensiUser->where('status', 'hadir')->count(),
                'total_izin' => $absensiUser->where('status', 'izin')->count(),
                'total_sakit' => $absensiUser->where('status', 'sakit')->count(),
                'total_lembur' => $absensiUser->where('tipe', 'lembur')->count(),
                'total_telat' => $absensiUser->where('late_minutes', '>', 0)->count(),
                'total_menit_lembur' => $totalMenitLembur,
                'total_gaji_lembur' => $totalGajiLembur,
                'total_menit_telat' => $absensiUser->sum('late_minutes'),
                'total_gaji' => $totalGaji,
                'total_potongan' => $totalPotongan,
                'total_absensi' => $absensiUser->count(),
            ];
        }

        $filenameSuffix = $range === 'weekly' && $week
            ? "Minggu_{$week}"
            : Carbon::createFromFormat('!m', $month)->format('M');

        $typeLabel = match($type) {
            'organik' => 'Organik',
            'freelance' => 'Freelance',
            'borongan' => 'Borongan',
            'magang' => 'Magang',
            default => 'All'
        };

        $filename = "Rekap_Absensi_{$typeLabel}_{$filenameSuffix}_{$year}.xlsx";

        return Excel::download(
            new AbsensiRekapExport($recapData, $month, $year, $type, $range, $week),
            $filename
        );
    }

    /**
     * 🔥 HELPER: DETEKSI KATEGORI BERDASARKAN PREFIX ID 🔥
     */
    private function detectKategori(User $user): string
    {
        $idKaryawan = $user->id_karyawan ?? '';

        if (str_starts_with($idKaryawan, 'CS-AMB')) {
            return 'borongan';
        }

        if (str_starts_with($idKaryawan, 'MG-AMB')) {
            return 'magang';
        }

        return $user->employment_type ?? 'organik';
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

    public function exportSlipGaji(Request $request, User $user)
    {
        $filterType = $request->input('filter_type', 'all');
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $week = $request->input('week', 1);

        $query = Absensi::where('user_id', $user->id);

        if ($filterType === 'yearly') {
            $query->whereYear('check_in_at', $year);
        } elseif ($filterType === 'monthly') {
            $query->whereYear('check_in_at', $year)
                  ->whereMonth('check_in_at', $month);
        } elseif ($filterType === 'weekly') {
            $firstMonday = \Carbon\Carbon::create($year, $month, 1)->startOfMonth()->next(\Carbon\Carbon::MONDAY);
            if ($firstMonday->month != $month) {
                $firstMonday = \Carbon\Carbon::create($year, $month, 1);
            }
            $startDate = (clone $firstMonday)->addWeeks($week - 1)->startOfWeek();
            $endDate = (clone $startDate)->endOfWeek();

            $query->whereBetween('check_in_at', [$startDate, $endDate]);
        }

        $approvedAbsensi = $query->where('status_approval', 'approved')->get();

        $absensiStats = [
            'total_hadir' => $approvedAbsensi->where('status', 'hadir')->count(),
            'total_gaji_pokok' => $approvedAbsensi->sum('base_salary'),
            'total_potongan' => $approvedAbsensi->sum('late_penalty'),
            'total_gaji_lembur' => $approvedAbsensi->sum('overtime_pay'),
            'total_gaji_bersih' => $approvedAbsensi->sum('final_salary'),
        ];

        $periode = match ($filterType) {
            'monthly' => "Bulan " . \Carbon\Carbon::createFromFormat('!m', $month)->translatedFormat('F') . " {$year}",
            'weekly' => "Minggu ke-{$week}, " . \Carbon\Carbon::createFromFormat('!m', $month)->translatedFormat('F') . " {$year}",
            'yearly' => "Tahun {$year}",
            default => "Semua Data",
        };

        $fileName = "Slip_Gaji_{$user->name}_{$month}_{$year}.xlsx";

        return Excel::download(
            new SlipGajiExport($user, $absensiStats, $periode),
            $fileName
        );
    }
}
