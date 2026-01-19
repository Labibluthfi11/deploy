<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Absensi;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;

class BulkSimpleExport implements FromView, ShouldAutoSize, WithTitle
{
    protected $userIds;
    protected $startDate;
    protected $endDate;

    public function __construct($userIds, $startDate, $endDate)
    {
        $this->userIds = $userIds;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        $absensiData = Absensi::whereIn('user_id', $this->userIds)
    ->whereBetween('check_in_at', [$this->startDate, $this->endDate])
    ->orderBy('check_in_at', 'asc')
    ->get();

\Log::info('📊 Total Absensi Found:', [
    'count' => $absensiData->count(),
    'users' => $users->pluck('name'),
]);

        // 🔥 PECAH TANGGAL PER BULAN & PER MINGGU (max 15 hari per section)
        $sections = $this->splitDatesByMonth($this->startDate, $this->endDate);

        // Deteksi kategori
        $categories = [];
        foreach ($users as $user) {
            $kategori = $this->detectKategori($user);
            $categories[] = $kategori;
        }

        $uniqueCategories = array_unique($categories);

        if (count($uniqueCategories) === 1) {
            $singleCategory = $uniqueCategories[0];
            $categoryLabel = match($singleCategory) {
                'organik' => 'Karyawan Organik',
                'freelance' => 'Karyawan Freelance',
                'borongan' => 'Karyawan Borongan',
                'magang' => 'Karyawan Magang',
                default => 'Semua Karyawan'
            };
        } else {
            $categoryLabel = 'Semua Karyawan';
        }

        $periodeStr = Carbon::parse($this->startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($this->endDate)->translatedFormat('d M Y');

        return view('exports.bulk_simple', [
            'users' => $users,
            'absensiData' => $absensiData,
            'sections' => $sections, // 🔥 Array of sections (per bulan & minggu)
            'periodeStr' => $periodeStr,
            'categoryLabel' => $categoryLabel,
        ]);
    }

    /**
     * 🔥 PECAH TANGGAL PER BULAN & PER MINGGU (MAX 15 HARI)
     */
    private function splitDatesByMonth($start, $end)
    {
        $sections = [];
        $current = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        $currentMonth = $current->month;
        $weekDates = [];
        $weekNumber = 1;

        while ($current <= $endDate) {
            // Cek kalo ganti bulan
            if ($current->month != $currentMonth) {
                // Simpan minggu terakhir bulan sebelumnya
                if (!empty($weekDates)) {
                    $sections[] = [
                        'month' => Carbon::create(null, $currentMonth)->translatedFormat('F Y'),
                        'week' => $weekNumber,
                        'dates' => $weekDates,
                    ];
                }

                // Reset untuk bulan baru
                $currentMonth = $current->month;
                $weekDates = [];
                $weekNumber = 1;
            }

            // Tambah tanggal ke minggu ini
            $weekDates[] = $current->copy();

            // Kalo udah 15 hari, bikin section baru
            if (count($weekDates) >= 15) {
                $sections[] = [
                    'month' => $current->translatedFormat('F Y'),
                    'week' => $weekNumber,
                    'dates' => $weekDates,
                ];

                $weekDates = [];
                $weekNumber++;
            }

            $current->addDay();
        }

        // Simpan sisa tanggal terakhir
        if (!empty($weekDates)) {
            $sections[] = [
                'month' => Carbon::create(null, $currentMonth)->translatedFormat('F Y'),
                'week' => $weekNumber,
                'dates' => $weekDates,
            ];
        }

        return $sections;
    }

    private function detectKategori(User $user): string
    {
        $idKaryawan = $user->id_karyawan ?? '';

        if (strpos($idKaryawan, 'CS-AMB') === 0) {
            return 'borongan';
        }

        if (strpos($idKaryawan, 'MG-AMB') === 0) {
            return 'magang';
        }

        if (strpos($idKaryawan, 'AMB') === 0) {
            return 'freelance';
        }

        return $user->employment_type ?? 'organik';
    }

    public function title(): string
    {
        return 'Absensi Simple';
    }
}
