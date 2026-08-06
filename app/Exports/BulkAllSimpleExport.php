<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Absensi;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCharts;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class BulkAllSimpleExport implements FromView, ShouldAutoSize, WithTitle
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
        $users = User::whereIn('id', $this->userIds)->orderBy('name')->get();

        $allDates = [];
        $current = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        while ($current <= $end) {
            $allDates[] = $current->copy();
            $current->addDay();
        }

        $absensiData = Absensi::whereIn('user_id', $this->userIds)
            ->whereBetween('check_in_at', [$this->startDate, $this->endDate])
            ->where('status_approval', 'approved')
            ->orderBy('check_in_at', 'asc')
            ->get();

        $periodeStr = Carbon::parse($this->startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($this->endDate)->translatedFormat('d M Y');

        // CHUNK TANGGAL JADI 11 HARI PER ROW
        $dateChunks = array_chunk($allDates, 11);

        // ==========================================
        // KALKULASI SUMMARY GLOBAL
        // ==========================================
        $summary = [
            'total_hadir' => 0,
            'total_sakit' => 0,
            'total_izin' => 0,
            'total_alpha' => 0,
            'total_lembur' => 0, 
            'breakdown' => []
        ];

        foreach ($users as $user) {
            $kategori = $this->detectKategori($user);
            $kategoriLabel = ucfirst($kategori);

            if (!isset($summary['breakdown'][$kategoriLabel])) {
                $summary['breakdown'][$kategoriLabel] = [
                    'hadir' => 0,
                    'sakit' => 0,
                    'izin' => 0,
                    'alpha' => 0,
                    'lembur' => 0,
                ];
            }

            $userAbsensi = $absensiData->where('user_id', $user->id);

            foreach ($allDates as $date) {
                $absen = $userAbsensi->first(fn($i) => Carbon::parse($i->check_in_at)->isSameDay($date));

                if ($absen) {
                    if (strtolower($absen->status) === 'hadir') {
                        $summary['total_hadir']++;
                        $summary['breakdown'][$kategoriLabel]['hadir']++;
                    }
                    if (strtolower($absen->status) === 'sakit') {
                        $summary['total_sakit']++;
                        $summary['breakdown'][$kategoriLabel]['sakit']++;
                    }
                    if (strtolower($absen->status) === 'izin') {
                        $summary['total_izin']++;
                        $summary['breakdown'][$kategoriLabel]['izin']++;
                    }
                    if (($absen->overtime_minutes ?? 0) > 0) {
                        $summary['total_lembur']++;
                        $summary['breakdown'][$kategoriLabel]['lembur']++;
                    }
                } else {
                    if (!Carbon::parse($date)->isWeekend() && !\App\Models\Holiday::isHoliday($date)) {
                        $summary['total_alpha']++;
                        $summary['breakdown'][$kategoriLabel]['alpha']++;
                    }
                }
            }
        }

        return view('exports.bulk_all_simple', [
            'users' => $users,
            'absensiData' => $absensiData,
            'allDates' => $allDates,
            'dateChunks' => $dateChunks,
            'periodeStr' => $periodeStr,
            'summary' => $summary,
        ]);
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
        return 'Rekap Absensi Semua Karyawan';
    }
}
