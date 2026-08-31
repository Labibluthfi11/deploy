<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use ZipArchive;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AbsensiRekapExport;
use App\Exports\AbsensiUserExport;
use App\Exports\SlipGajiExport;
use App\Exports\SlipGajiPdfExport;
use App\Exports\BulkDetailExport;
use App\Exports\BulkSimpleExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class AbsensiAdminController extends Controller
{
    public function storeLemburManual(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'lembur_start' => 'required|date_format:H:i',
            'lembur_end' => 'required|date_format:H:i',
            'istirahat' => 'nullable|boolean',
            'file_bukti' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'keterangan' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($request->user_id);
        $tanggal = Carbon::parse($request->tanggal)->toDateString();
        $startTime = Carbon::parse($tanggal . ' ' . $request->lembur_start);
        $endTime = Carbon::parse($tanggal . ' ' . $request->lembur_end);

        // Cari absensi di tanggal tersebut yang statusnya approved
        $absensi = Absensi::where('user_id', $user->id)
            ->whereDate('check_in_at', $tanggal)
            ->where('status_approval', 'approved')
            ->first();

        if (!$absensi) {
            return back()->with('error', 'Tidak ada data absensi approved untuk tanggal tersebut.');
        }

        // Logic Lembur: Rate 1x (fix), istirahat 30m jika dipilih
        $hasRest = $request->boolean('istirahat');
        $minutes = abs($endTime->diffInMinutes($startTime));
        $finalMinutes = $hasRest ? max(0, $minutes - 30) : $minutes;
        
        // Asumsi HOURLY_SALARY adalah 1x rate
        $overtimePay = ($finalMinutes / 60) * Absensi::HOURLY_SALARY;

        $updateData = [
            'tipe' => 'lembur',
            'lembur_start' => $startTime,
            'lembur_end' => $endTime,
            'lembur_rest' => $hasRest ? 1 : 0,
            'lembur_keterangan' => $request->keterangan,
            'overtime_minutes' => $finalMinutes,
            'overtime_pay' => $overtimePay,
            'final_salary' => ($absensi->final_salary ?? 0) + $overtimePay,
        ];

        if ($request->hasFile('file_bukti')) {
            $path = $request->file('file_bukti')->store('absensi/bukti', 'public');
            $updateData['file_bukti'] = $path;
        }

        $absensi->update($updateData);

        ActivityLog::log('Manual Overtime', "Admin: " . Auth::user()->name, "Input lembur manual untuk User: {$user->name} tanggal: {$tanggal}. Durasi: {$finalMinutes}m");

        return back()->with('success', 'Lembur manual berhasil ditambahkan.');
    }

    public function showManualEntryPage()
    {
        $users = User::whereNotIn('role', ['super_admin', 'admin', 'manager', 'supervisor', 'hrga', 'pkl'])->get();
        return view('admin.absensi.koreksi', compact('users'));
    }

    public function storeIzinSakitManual(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'tipe_izin' => 'required|string', // e.g., 'sakit', 'cuti_tahunan', 'izin_biasa'
            'file_bukti' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'keterangan' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($request->user_id);
        $tanggal = Carbon::parse($request->tanggal);
        
        // Tentukan status berdasarkan tipe izin
        $status = ($request->tipe_izin === 'sakit') ? 'sakit' : 'izin';

        DB::beginTransaction();
        try {
            // Potong cuti jika tipe izin termasuk dalam kategori yang memotong
            if (in_array($request->tipe_izin, User::cutiYangMemotong())) {
                if ($user->sisa_cuti <= 0) {
                    return back()->with('error', 'Sisa cuti karyawan tidak mencukupi.');
                }
                $user->decrement('sisa_cuti');
                $user->increment('total_cuti_diambil');
            }

            $createData = [
                'user_id' => $user->id,
                'check_in_at' => $tanggal->startOfDay(),
                'check_out_at' => $tanggal->endOfDay(),
                'status' => $status,
                'submission_type' => $request->tipe_izin,
                'status_approval' => 'approved',
                'approved_at' => now(),
                'keterangan_izin_sakit' => 'Manual Entry: ' . $request->keterangan,
                'final_salary' => 0, // Izin/Sakit manual biasanya tidak dibayar
                'is_mocked' => false,
            ];

            if ($request->hasFile('file_bukti')) {
                $path = $request->file('file_bukti')->store('absensi/bukti', 'public');
                $createData['file_bukti'] = $path;
            }

            Absensi::create($createData);

            ActivityLog::log('Manual Izin/Sakit', "Admin: " . Auth::user()->name, "Input manual untuk User: {$user->name} tanggal: {$tanggal->toDateString()}. Tipe: {$request->tipe_izin}");

            DB::commit();
            return back()->with('success', 'Izin/Sakit manual berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menambahkan data: ' . $e->getMessage());
        }
    }

    public function storeManual(Request $request)
    {
        $request->validate([
            'user_id'    => 'required|exists:users,id',
            'tanggal'    => 'required|date',
            'jam_masuk'  => 'required|date_format:H:i',
            'jam_pulang' => 'required|date_format:H:i',
            'foto_masuk' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'foto_pulang' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'keterangan' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($request->user_id);
        $tanggal = Carbon::parse($request->tanggal);
        $checkIn = Carbon::parse($tanggal->format('Y-m-d') . ' ' . $request->jam_masuk);
        $checkOut = Carbon::parse($tanggal->format('Y-m-d') . ' ' . $request->jam_pulang);

        // Hitung Gaji
        $lateMinutes = 0; // Admin yang input, asumsikan telat 0 atau bisa ditambah logic lain
        $isWeekend = Absensi::isWeekend($checkIn);
        
        $salaryData = Absensi::calculateSalary(
            $lateMinutes,
            'hadir',
            'normal',
            $isWeekend,
            $checkIn,
            $checkOut,
            $user->getKategoriAbsensiAttribute()
        );

        $createData = [
            'user_id' => $user->id,
            'check_in_at' => $checkIn,
            'check_out_at' => $checkOut,
            'status' => 'hadir',
            'tipe' => 'normal',
            'status_approval' => 'approved',
            'approved_at' => now(),
            'keterangan_izin_sakit' => 'Manual Entry: ' . $request->keterangan,
            'base_salary' => $salaryData['base_salary'],
            'late_penalty' => $salaryData['late_penalty'],
            'final_salary' => $salaryData['final_salary'],
            'is_mocked' => false,
        ];

        if ($request->hasFile('foto_masuk')) {
            $path = $request->file('foto_masuk')->store('absensi/foto', 'public');
            $createData['foto_masuk'] = $path;
        }
        
        if ($request->hasFile('foto_pulang')) {
            $path = $request->file('foto_pulang')->store('absensi/foto', 'public');
            $createData['foto_pulang'] = $path;
        }

        $absensi = Absensi::create($createData);

        ActivityLog::log('Manual Entry', "Admin: " . Auth::user()->name, "Input manual untuk User: {$user->name} tanggal: {$tanggal->toDateString()}");

        return back()->with('success', 'Absensi manual berhasil ditambahkan.');
    }
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
            $query->whereNotIn('role', ['supervisor', 'manager', 'hrga', 'admin', 'super_admin', 'pkl']);
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
        $dailyStatusesBorongan = [];
        $dailyStatusesMagang = [];

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
                $dailyStatusesBorongan[] = $dailyData;
            } elseif ($kategori === 'magang') {
                $dailyStatusesMagang[] = $dailyData;
            }
        }

        // 🔥 FILTER PENCARIAN (4 Kategori)
        $searchOrganik = $request->input('search_organik');
        $searchFreelance = $request->input('search_freelance');
        $searchBorongan = $request->input('search_borongan');
        $searchMagang = $request->input('search_magang');

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

        if ($searchBorongan) {
            $dailyStatusesBorongan = array_filter($dailyStatusesBorongan, function($daily) use ($searchBorongan) {
                return stripos($daily['user']->name, $searchBorongan) !== false;
            });
        }

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
        usort($dailyStatusesBorongan, fn($a, $b) => $a['user']->name <=> $b['user']->name);
        usort($dailyStatusesMagang, fn($a, $b) => $a['user']->name <=> $b['user']->name);

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
            'dailyStatusesBorongan',
            'dailyStatusesMagang',
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

        // Query dasar
        $query = Absensi::where('user_id', $user->id);

        // --- LOGIKA FILTER ---
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

        } elseif ($filterType === 'custom') {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            if ($startDate && $endDate) {
                $query->whereBetween('check_in_at', [
                    \Carbon\Carbon::parse($startDate)->startOfDay(),
                    \Carbon\Carbon::parse($endDate)->endOfDay()
                ]);
            }
        }

        // 🔥 AMBIL DATA BIASA (JANGAN UBAH QUERY)
        $absensi = $query->orderBy('check_in_at', 'desc')->get();

        // 🔥 FORCE REFRESH DATA APPROVED (Re-fetch dari DB)
        $absensi = $absensi->map(function($item) {
            if ($item->status_approval === 'approved') {
                // Force reload dari database (bypass cache)
                return Absensi::find($item->id);
            }
            return $item;
        });

        // Filter data yang approved untuk statistik
        // ✅ AMBIL SEMUA DATA (APPROVED + REJECTED YANG PUNYA GAJI)
        $approvedAbsensi = $absensi->whereIn('status_approval', ['approved', 'rejected']);
        // Hitung statistik berdasarkan data yang sudah difilter
            $absensiStats = [
            'hadir' => $approvedAbsensi->where('status', 'hadir')->count(),
            'telat' => $approvedAbsensi->where('late_minutes', '>', 0)->count(),
            'izin' => $approvedAbsensi->where('status', 'izin')->where('status_approval', 'approved')->count(),
            'sakit' => $approvedAbsensi->where('status', 'sakit')->where('status_approval', 'approved')->count(),
            'lembur' => $approvedAbsensi->where('tipe', 'lembur')->where('status_approval', 'approved')->count(),
            'total_absensi' => $approvedAbsensi->count(),
            'total_gaji_pokok' => $approvedAbsensi->sum('base_salary'),
            'total_potongan' => $approvedAbsensi->sum('late_penalty'),
            'total_gaji_lembur' => $approvedAbsensi->where('status_approval', 'approved')->where('tipe', 'lembur')->sum('overtime_pay'),
            'total_gaji_bersih' => $approvedAbsensi->sum('final_salary'),
            'total_menit_lembur' => $approvedAbsensi->where('status_approval', 'approved')->where('tipe', 'lembur')->sum('overtime_minutes'),
        ];

        // Variabel dummy untuk mingguan (biar view gak error)
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

    // Logika tanggal (sama kayak sebelumnya)
    if ($range === 'custom') {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfMonth();

    } elseif ($range === 'weekly' && $week) {
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

    $users = User::whereNotIn('role', ['super_admin', 'admin', 'manager', 'supervisor', 'hrga', 'pkl'])->get();
    $recapData = [];
    $izinKeluarMonthStart = Carbon::create($year, $month, 1)->startOfMonth();
    $izinKeluarMonthEnd = Carbon::create($year, $month, 1)->endOfMonth();

    foreach ($users as $user) {
        // ✅ AMBIL SEMUA DATA (APPROVED + REJECTED)
        $absensiUser = Absensi::where('user_id', $user->id)
            ->whereBetween('check_in_at', [$startDate, $endDate])
            ->whereIn('status_approval', ['approved', 'rejected'])
            ->get();

        // 🔥 FORCE REFRESH
        $absensiUser = $absensiUser->map(function($item) {
            return Absensi::find($item->id);
        });

        // ✅ HITUNG TOTAL HARI KERJA (5 hari kerja/minggu, sampai hari ini/akhir periode)
        $totalHariKerja = 0;
        $tempDate = $startDate->copy();
        $today = Carbon::now();
        
        // Batasi sampai hari ini atau akhir range jika range-nya sudah lewat
        $limitDate = $today->lt($endDate) ? $today : $endDate;

        while ($tempDate->lte($limitDate)) {
            // Cek Senin-Jumat (1-5) dan bukan hari libur
            if ($tempDate->dayOfWeek >= Carbon::MONDAY && $tempDate->dayOfWeek <= Carbon::FRIDAY && !\App\Models\Holiday::isHoliday($tempDate)) {
                $totalHariKerja++;
            }
            $tempDate->addDay();
        }

        // ✅ HITUNG GAJI (Semua yang punya final_salary)
        $totalGaji = $absensiUser->sum('final_salary') ?? 0;
        $totalGajiLembur = $absensiUser->where('status_approval', 'approved')->where('tipe', 'lembur')->sum('overtime_pay') ?? 0;
        $totalMenitLembur = $absensiUser->where('status_approval', 'approved')->where('tipe', 'lembur')->sum('overtime_minutes');
        $totalPotongan = $absensiUser->sum('late_penalty') ?? 0;
        $kategori = $this->detectKategori($user);

        $izinKeluarUser = \App\Models\IzinKeluar::where('user_id', $user->id)
            ->whereBetween('waktu_keluar', [$izinKeluarMonthStart, $izinKeluarMonthEnd])
            ->get();

        $totalHadir = $absensiUser->where('status', 'hadir')->count();
        $totalIzin = $absensiUser->where('status', 'izin')->where('status_approval', 'approved')->count();
        $totalSakit = $absensiUser->where('status', 'sakit')->where('status_approval', 'approved')->count();
        $totalKurang8Jam = $absensiUser->where('is_kurang_8_jam', true)->count(); // 🆕 TAMBAH INI
        
        // Alfa = Total Hari Kerja - (Hadir + Izin + Sakit)
        $totalAlfa = max(0, $totalHariKerja - ($totalHadir + $totalIzin + $totalSakit));

        $recapData[] = [
            'user' => $user,
            'kategori' => $kategori,
            'total_hadir' => $totalHadir,
            'total_izin' => $totalIzin,
            'total_sakit' => $totalSakit,
            'total_kurang_8_jam' => $totalKurang8Jam, // 🆕 TAMBAH INI
            'total_alfa' => $totalAlfa, // ✅ BARU
            'total_cuti_tahunan' => $absensiUser->where('status', 'izin')->where('status_approval', 'approved')->where('submission_type', 'cuti_tahunan')->count(),
            'total_cuti_spesial' => $absensiUser->where('status', 'izin')->where('status_approval', 'approved')->filter(fn($item) => !empty($item->submission_type) && $item->submission_type !== 'cuti_tahunan')->count(),
            'total_sakit' => $totalSakit,
            'total_cuti' => $absensiUser->where('status', 'izin')->filter(fn($item) => str_starts_with($item->submission_type ?? '', 'cuti'))->where('status_approval', 'approved')->count(),
            'total_lembur' => $absensiUser->where('tipe', 'lembur')->where('status_approval', 'approved')->count(),
            'total_telat' => $absensiUser->where('late_minutes', '>', 0)->count(),
            'total_menit_telat' => $absensiUser->sum('late_minutes'),
            'total_menit_lembur' => $totalMenitLembur,
            'total_gaji_lembur' => $totalGajiLembur,
            'total_gaji' => $totalGaji,
            'total_potongan' => $totalPotongan,
            'total_izin_keluar' => $izinKeluarUser->count(),
            'total_izin_keluar_ditolak' => $izinKeluarUser->where('status_approval', 'rejected')->count(),
        ];
    }

    return view('admin.absensi.recap', compact(
        'recapData', 'month', 'year', 'range', 'week',
        'startDate', 'endDate'
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

        // 🔥 LOGIKA BARU (SAMA KAYAK DI recap())
        if ($range === 'custom') {
            $startDate = $request->input('start_date')
                ? Carbon::parse($request->input('start_date'))->startOfDay()
                : Carbon::now()->startOfMonth();

            $endDate = $request->input('end_date')
                ? Carbon::parse($request->input('end_date'))->endOfDay()
                : Carbon::now()->endOfMonth();

        } elseif ($range === 'weekly' && $week) {
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

        $users = User::whereNotIn('role', ['super_admin', 'admin', 'manager', 'supervisor', 'hrga', 'pkl'])->get();
        $recapData = [];
        $izinKeluarMonthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $izinKeluarMonthEnd = Carbon::create($year, $month, 1)->endOfMonth();

        foreach ($users as $user) {
            // 🔥 QUERY DENGAN PENDING
            $absensiUser = Absensi::where('user_id', $user->id)
                ->whereBetween('check_in_at', [$startDate, $endDate])
                ->whereIn('status_approval', ['approved', 'rejected', 'pending'])
                ->get();

            // 🔥 HITUNG GAJI HANYA YANG APPROVED
            $totalGaji = $absensiUser->where('status_approval', 'approved')->sum('final_salary') ?? 0;
            $totalGajiLembur = $absensiUser->where('status_approval', 'approved')->where('tipe', 'lembur')->sum('overtime_pay') ?? 0;
            $totalMenitLembur = $absensiUser->where('status_approval', 'approved')->where('tipe', 'lembur')->sum('overtime_minutes');
            
            // 🔥 TOTAL POTONGAN TETAP HITUNG SEMUA (KARNA SUDAH FINAL)
            $totalPotongan = $absensiUser->sum('late_penalty') ?? 0;
            
            $kategori = $this->detectKategori($user);

            $izinKeluarUser = \App\Models\IzinKeluar::where('user_id', $user->id)
                ->whereBetween('waktu_keluar', [$izinKeluarMonthStart, $izinKeluarMonthEnd])
                ->get();

            $recapData[] = [
                'user' => $user,
                'kategori' => $kategori,
                'total_hadir' => $absensiUser->where('status', 'hadir')->where('status_approval', 'approved')->count(),
                'total_izin' => $absensiUser->where('status', 'izin')->where('status_approval', 'approved')->count(),
                'total_cuti_tahunan' => $absensiUser->where('status', 'izin')->where('status_approval', 'approved')->where('submission_type', 'cuti_tahunan')->count(),
                'total_cuti_spesial' => $absensiUser->where('status', 'izin')->where('status_approval', 'approved')->filter(fn($item) => !empty($item->submission_type) && $item->submission_type !== 'cuti_tahunan')->count(),
                'total_sakit' => $absensiUser->where('status', 'sakit')->where('status_approval', 'approved')->count(),
                'total_lembur' => $absensiUser->where('tipe', 'lembur')->where('status_approval', 'approved')->count(),
                'total_lembur_pending' => $absensiUser->where('tipe', 'lembur')->where('status_approval', 'pending')->count(),
                'total_lembur_ditolak' => $absensiUser->where('tipe', 'lembur')->where('status_approval', 'rejected')->count(),
                'total_telat' => $absensiUser->where('late_minutes', '>', 0)->count(),
                'total_menit_lembur' => $totalMenitLembur,
                'total_gaji_lembur' => $totalGajiLembur,
                'total_menit_telat' => $absensiUser->sum('late_minutes'),
                'total_gaji' => $totalGaji,
                'total_potongan' => $totalPotongan,
                'total_izin_keluar' => $izinKeluarUser->count(),
                'total_izin_keluar_ditolak' => $izinKeluarUser->where('status_approval', 'rejected')->count(),
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
        } elseif ($request->filter_type === 'custom') {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            if ($startDate && $endDate) {
                $query->whereBetween('check_in_at', [
                    \Carbon\Carbon::parse($startDate)->startOfDay(),
                    \Carbon\Carbon::parse($endDate)->endOfDay(),
                ]);
            }
        }

        $absensi = $query->get();

        // 🔥 FORCE REFRESH DATA APPROVED
        $absensi = $absensi->map(function($item) {
            if ($item->status_approval === 'approved') {
                return Absensi::find($item->id);
            }
            return $item;
        });

        $fileName = "Absensi_{$user->name}_" . now()->format('Ymd_His') . ".xlsx";

        return Excel::download(
            new AbsensiUserExport($absensi, $user, $request->filter_type, $request->month, $request->year, $request->week),
            $fileName
        );
    }

    public function exportSlipGaji(Request $request, User $user)
    {
        $filterType = $request->input('filter_type', 'all');

        // Query dasar
        $query = Absensi::where('user_id', $user->id);
        $periodeLabel = "Semua Data";

        // Logic Filter
        if ($filterType === 'monthly') {
            $year = $request->input('year', now()->year);
            $month = $request->input('month', now()->month);

            $query->whereYear('check_in_at', $year)
                  ->whereMonth('check_in_at', $month);

            $periodeLabel = \Carbon\Carbon::createFromFormat('!m', $month)->translatedFormat('F') . " {$year}";

        } elseif ($filterType === 'custom') {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            if ($startDate && $endDate) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();

            $query->whereBetween('check_in_at', [$start, $end]);

            $periodeLabel = \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') . " - " . \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y');
        }
    }

    // Ambil data yang DI-APPROVE AJA
    $approvedAbsensi = $query->whereIn('status_approval', ['approved', 'rejected'])->get();

    $approvedAbsensi = $approvedAbsensi->map(function($item) {
        return Absensi::find($item->id);
    });

    $absensiStats = [
        'total_hadir' => $approvedAbsensi->where('status', 'hadir')->count(),
        'total_gaji_pokok' => $approvedAbsensi->sum('base_salary'),
        'total_potongan' => $approvedAbsensi->sum('late_penalty'),
        'total_gaji_lembur' => $approvedAbsensi->where('status_approval', 'approved')->where('tipe', 'lembur')->sum('overtime_pay'),
        'total_gaji_bersih' => $approvedAbsensi->sum('final_salary'),
        'total_menit_lembur' => $approvedAbsensi->where('status_approval', 'approved')->where('tipe', 'lembur')->sum('overtime_minutes'),
    ];

    // Nama File
    $fileName = "Slip_Gaji_{$user->name}_{$filterType}_" . date('Ymd_His') . ".xlsx";

    return Excel::download(
        new SlipGajiExport($user, $absensiStats, $periodeLabel),
        $fileName
    );
}

// INI FUNGSI BARU BUAT NANGANIN CHECKBOX
public function bulkExportDetail(Request $request)
{
    $request->validate([
        'user_ids'   => 'required|array|min:1',
        'user_ids.*' => 'exists:users,id',
        'start_date' => 'required|date_format:Y-m-d H:i:s',
        'end_date'   => 'required|date_format:Y-m-d H:i:s',
    ]);

    $userIds = $request->input('user_ids');
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    $fileName = "Rekap_Detail_Massal_" . date('Ymd_His') . ".xlsx";

    return Excel::download(
        new BulkDetailExport($userIds, $startDate, $endDate),
        $fileName
    );
}


public function exportSlipGajiPdf(Request $request, $id)
{
    // ✅ Manual query user
    $user = User::findOrFail($id);

    $filterType = $request->input('filter_type', 'all');
    $query = Absensi::where('user_id', $user->id);
    $periodeLabel = "Semua Data";

    if ($filterType === 'monthly') {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $query->whereYear('check_in_at', $year)
              ->whereMonth('check_in_at', $month);

        $periodeLabel = \Carbon\Carbon::createFromFormat('!m', $month)->translatedFormat('F') . " {$year}";

    } elseif ($filterType === 'custom') {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && $endDate) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();

            $query->whereBetween('check_in_at', [$start, $end]);

            $periodeLabel = \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') . " - " . \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y');
        }
    }

   $approvedAbsensi = $query->whereIn('status_approval', ['approved', 'rejected'])->get();

$approvedAbsensi = $approvedAbsensi->map(function($item) {
    return Absensi::find($item->id);
});

$absensiStats = [
    'total_hadir' => $approvedAbsensi->where('status', 'hadir')->count(),
    'total_gaji_pokok' => $approvedAbsensi->sum('base_salary'),
    'total_potongan' => $approvedAbsensi->sum('late_penalty'),
    'total_gaji_lembur' => $approvedAbsensi->where('status_approval', 'approved')->where('tipe', 'lembur')->sum('overtime_pay'),
    'total_gaji_bersih' => $approvedAbsensi->sum('final_salary'),
    'total_menit_lembur' => $approvedAbsensi->where('status_approval', 'approved')->where('tipe', 'lembur')->sum('overtime_minutes'),
];

    $exporter = new SlipGajiPdfExport($user, $absensiStats, $periodeLabel);
    $pdf = $exporter->generate();

    $fileName = "Slip_Gaji_{$user->name}_{$filterType}_" . date('Ymd_His') . ".pdf";

    return $pdf->download($fileName);
}

public function bulkExportPdf(Request $request)
{
    // 1. Validasi
    $request->validate([
        'user_ids'   => 'required|array|min:1',
        'start_date' => 'required',
        'end_date'   => 'required',
    ]);

    $userIds = $request->input('user_ids');
    $startDate = Carbon::parse($request->input('start_date'));
    $endDate = Carbon::parse($request->input('end_date'));

    // 2. Siapkan ZIP
    $zipName = 'Slip_Gaji_Bulk_' . date('Ymd_His') . '.zip';
    $zipPath = storage_path('app/' . $zipName);

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return back()->with('error', 'Gagal membuat file ZIP!');
    }

    ini_set('max_execution_time', 300);
    ini_set('memory_limit', '512M');

    // 3. Loop User
    foreach ($userIds as $userId) {
        $user = User::find($userId);
        if (!$user) continue;

        // Ambil data absensi
        $approvedAbsensi = Absensi::where('user_id', $user->id)
            ->whereBetween('check_in_at', [$startDate, $endDate])
            ->where('status_approval', 'approved')
            ->get();

        $periodeStr = $startDate->translatedFormat('d M Y') . ' - ' . $endDate->translatedFormat('d M Y');

        // 🔥 HITUNG GAJI BERSIH + TERBILANG
        $gajiBersih = $approvedAbsensi->sum('final_salary');
        $terbilangString = $this->penyebut($gajiBersih) . ' Rupiah';

        // 👇 DATA YANG DIKIRIM KE BLADE (UPDATE LENGKAP) 👇
        $data = [
            'user'             => $user,
            'periode'          => $periodeStr,
            'periodeLabel'     => $periodeStr,
            'absensi'          => $approvedAbsensi,

            // === VARIABEL KEUANGAN ===
            'gajiPokok'        => $approvedAbsensi->sum('base_salary'),
            'totalGajiPokok'   => $approvedAbsensi->sum('base_salary'), // Alias

            'totalPotongan'    => $approvedAbsensi->sum('late_penalty'),
            'potongan'         => $approvedAbsensi->sum('late_penalty'), // Alias

            'totalLembur'      => $approvedAbsensi->sum('overtime_pay'), // Uang lembur
            'gajiLembur'       => $approvedAbsensi->sum('overtime_pay'), // Alias
            'totalGajiLembur'  => $approvedAbsensi->sum('overtime_pay'), // Alias

            'totalGaji'        => $gajiBersih,
            'gajiBersih'       => $gajiBersih, // Alias
            'totalGajiBersih'  => $gajiBersih, // Alias

            // === VARIABEL KEHADIRAN & WAKTU ===
            'totalHadir'       => $approvedAbsensi->where('status', 'hadir')->count(),
            'totalSakit'       => $approvedAbsensi->where('status', 'sakit')->count(),
            'totalIzin'        => $approvedAbsensi->where('status', 'izin')->count(),

            'totalMenitLembur' => $approvedAbsensi->sum('overtime_minutes'),
            'durasiLembur'     => $approvedAbsensi->sum('overtime_minutes'), // 👈 INI YANG TADI ERROR

            // 🔥 TAMBAH INI - YANG PALING PENTING! 🔥
            'terbilang'        => ucwords($terbilangString), // 👈 INI YANG KURANG!

            // Stats array (Cadangan)
            'stats'            => [
                'total_hadir'        => $approvedAbsensi->where('status', 'hadir')->count(),
                'total_gaji_pokok'   => $approvedAbsensi->sum('base_salary'),
                'total_potongan'     => $approvedAbsensi->sum('late_penalty'),
                'total_gaji_lembur'  => $approvedAbsensi->sum('overtime_pay'),
                'total_gaji_bersih'  => $gajiBersih,
                'total_menit_lembur' => $approvedAbsensi->sum('overtime_minutes'),
            ],
        ];

        try {
            $pdf = Pdf::loadView('exports.slip-gaji-pdf', $data);

            $content = $pdf->output();

            $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $user->name);
            $fileName = "Slip_Gaji_{$cleanName}.pdf";

            $zip->addFromString($fileName, $content);

        } catch (\Exception $e) {
            // Kalo error lagi, baca pesannya
            Log::error("Error generating PDF for user {$user->name}: " . $e->getMessage());
            continue; // Skip user ini, lanjut ke user berikutnya
        }
    }

    $zip->close();

    return response()->download($zipPath)->deleteFileAfterSend(true);
}


private function penyebut($nilai)
{
    $nilai = abs($nilai);
    $huruf = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
    $temp = "";

    if ($nilai < 12) {
        $temp = " ". $huruf[$nilai];
    } else if ($nilai < 20) {
        $temp = $this->penyebut($nilai - 10). " belas";
    } else if ($nilai < 100) {
        $temp = $this->penyebut($nilai/10)." puluh". $this->penyebut($nilai % 10);
    } else if ($nilai < 200) {
        $temp = " seratus" . $this->penyebut($nilai - 100);
    } else if ($nilai < 1000) {
        $temp = $this->penyebut($nilai/100) . " ratus" . $this->penyebut($nilai % 100);
    } else if ($nilai < 2000) {
        $temp = " seribu" . $this->penyebut($nilai - 1000);
    } else if ($nilai < 1000000) {
        $temp = $this->penyebut($nilai/1000) . " ribu" . $this->penyebut($nilai % 1000);
    } else if ($nilai < 1000000000) {
        $temp = $this->penyebut($nilai/1000000) . " juta" . $this->penyebut($nilai % 1000000);
    } else if ($nilai < 1000000000000) {
        $temp = $this->penyebut($nilai/1000000000) . " milyar" . $this->penyebut(fmod($nilai,1000000000));
    } else if ($nilai < 1000000000000000) {
        $temp = $this->penyebut($nilai/1000000000000) . " trilyun" . $this->penyebut(fmod($nilai,1000000000000));
    }

    return $temp;
}

    public function bulkExportSimple(Request $request)
{
    // 🔥 VALIDASI SIMPLE (ga perlu strict format)
    $request->validate([
        'user_ids'   => 'required|array|min:1',
        'user_ids.*' => 'exists:users,id',
        'start_date' => 'required',
        'end_date'   => 'required',
    ], [
        'user_ids.required' => 'Pilih minimal 1 karyawan untuk export!',
        'user_ids.min' => 'Pilih minimal 1 karyawan untuk export!',
    ]);

    $userIds = $request->input('user_ids');


    try {

        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
    } catch (\Exception $e) {
        Log::error('Simple Export Date Parse Error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Format tanggal tidak valid!');
    }

    $fileName = "Absensi_Simple_" . now()->format('Ymd_His') . ".xlsx";

    try {
        return Excel::download(
            new BulkSimpleExport($userIds, $startDate, $endDate),
            $fileName
        );
    } catch (\Exception $e) {
        Log::error('Simple Export Error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Gagal export: ' . $e->getMessage());
    }
}

public function updateCheckIn(Request $request, $id)
{
    Log::info(' updateCheckIn called', [
        'id' => $id,
        'request' => $request->all(),
    ]);

    $request->validate([
        'new_check_in' => 'required|date_format:Y-m-d\TH:i',
    ]);

    $absensi = Absensi::findOrFail($id);
    $inputTime = $request->input('new_check_in');
    $newCheckIn = Carbon::parse(str_replace('T', ' ', $inputTime));

    $jamMasukShift = Carbon::parse($newCheckIn->format('Y-m-d') . ' 08:00:00');

    // Hitung telat
    $lateMinutes = 0;
    if ($newCheckIn->greaterThan($jamMasukShift)) {
        $lateMinutes = $newCheckIn->diffInMinutes($jamMasukShift);
    }

    // Bulatkan ke kelipatan 15 menit
    $roundedLateMinutes = 0;
    if ($lateMinutes > 0) {
        $roundedLateMinutes = ceil($lateMinutes / 15) * 15;
    }

    // Hitung denda
    $user = $absensi->user;
    $baseSalaryPerDay = $user->base_salary_per_day ?? 0;
    $tarifDendaTelat = $baseSalaryPerDay > 0 ? $baseSalaryPerDay / 480 : 0;
    $latePenalty = $roundedLateMinutes * $tarifDendaTelat;

    // Hitung gaji bersih
    $finalSalary = $absensi->base_salary - $latePenalty + ($absensi->overtime_pay ?? 0);

    // Update database
    DB::table('absensis')
        ->where('id', $absensi->id)
        ->update([
            'check_in_at' => $newCheckIn,
            'late_minutes' => $lateMinutes,
            'rounded_late_minutes' => $roundedLateMinutes,
            'late_penalty' => $latePenalty,
            'final_salary' => $finalSalary,
            'updated_at' => now(),
        ]);

    Log::info('✅ Check-in berhasil diubah!', [
        'absensi_id' => $absensi->id,
        'new_check_in' => $newCheckIn,
        'late_minutes' => $lateMinutes,
    ]);

    return back()->with('success', "✅ Check-in berhasil diubah! Telat: {$lateMinutes} menit (Dibulatkan: {$roundedLateMinutes} menit), Denda: Rp " . number_format($latePenalty, 0, ',', '.'));
    }

    public function updateCheckOut(Request $request, $id)
    {
        Log::info('updateCheckOut called', [
            'id' => $id,
            'request' => $request->all(),
        ]);

        $request->validate([
            'new_check_out' => 'required|date_format:Y-m-d\TH:i',
        ]);

        $absensi = Absensi::findOrFail($id);
        $inputTime = $request->input('new_check_out');
        $newCheckOut = Carbon::parse(str_replace('T', ' ', $inputTime));

        DB::table('absensis')
            ->where('id', $absensi->id)
            ->update([
                'check_out_at' => $newCheckOut,
                'updated_at'   => now(),
            ]);

        Log::info('✅ Check-out berhasil diubah!', [
            'absensi_id'    => $absensi->id,
            'new_check_out' => $newCheckOut,
        ]);

        return back()->with('success', '✅ Jam pulang berhasil diubah!');
    }
}
