<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Absensi;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;

class BulkDetailExport implements FromView, ShouldAutoSize, WithTitle
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

        $absensiData = Absensi::whereIn('user_id', $this->userIds)
            ->whereBetween('check_in_at', [$this->startDate, $this->endDate])
            ->whereIn('status_approval', ['approved', 'rejected', 'pending'])
            ->orderBy('check_in_at', 'asc')
            ->get();

        $categories = [];
        foreach ($users as $user) {
            $kategori = $this->detectKategori($user);
            $categories[] = $kategori;
        }

        $uniqueCategories = array_unique($categories);

        if (count($uniqueCategories) === 1) {
            $singleCategory = $uniqueCategories[0];
            $categoryLabel = match($singleCategory) {
                'organik' => 'KARYAWAN ORGANIK',
                'freelance' => 'KARYAWAN FREELANCE',
                'borongan' => 'KARYAWAN BORONGAN',
                'magang' => 'KARYAWAN MAGANG',
                default => 'SEMUA KARYAWAN'
            };
        } else {
            $categoryLabel = 'SEMUA KARYAWAN';
        }

        // 🔥 GENERATE SEMUA TANGGAL DI RANGE
        $allDates = [];
        $current = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        while ($current <= $end) {
            $allDates[] = $current->copy();
            $current->addDay();
        }

        $grandTotalGajiPokok = 0;
        $grandTotalGajiLembur = 0;
        $grandTotalPotongan = 0;
        $grandTotalGajiBersih = 0;

        foreach ($users as $user) {
            $userAbsensi = $absensiData->where('user_id', $user->id);

            // 🔥 GROUP BY TANGGAL & AMBIL DATA TERBARU (ID Tertinggi) PER HARI
            $latestDailyRecords = $userAbsensi->groupBy(function($absen) {
                return \Carbon\Carbon::parse($absen->check_in_at)->format('Y-m-d');
            })->map(function($dayGroup) {
                return $dayGroup->sortByDesc('id')->first();
            });

            // ✅ PASTIKAN SEMUA KOMPONEN DIBULETIN DI AWAL
            // Gaji pokok hitung dari record yang approved (hadir/lembur)
            $pokokAsli = (float) $latestDailyRecords->where('status_approval', 'approved')->sum('base_salary');
            $bonusPlus = (float) $latestDailyRecords->where('status_approval', 'approved')->where('adjustment_salary', '>', 0)->sum('adjustment_salary');
            $pokokFinal = round($pokokAsli + $bonusPlus);

            // 🔥 Gaji lembur HANYA hitung yang approved
            $lemburFinal = $latestDailyRecords->where('status_approval', 'approved')->map(fn($a) => round($a->overtime_pay ?? 0))->sum();
            
            $dendaTelatIndividual = $latestDailyRecords->map(fn($a) => round($a->late_penalty ?? 0))->sum();
            $adjMinus = round(abs((float) $latestDailyRecords->where('adjustment_salary', '<', 0)->sum('adjustment_salary')));
            $potonganFinal = $dendaTelatIndividual + $adjMinus;

            $grandTotalGajiPokok += $pokokFinal;
            $grandTotalGajiLembur += $lemburFinal;
            $grandTotalPotongan += $potonganFinal;
            
            // 🔥 TOTAL AKHIR PER USER (BALANCE)
            $userTotal = ($pokokFinal + $lemburFinal) - $potonganFinal;
            $grandTotalGajiBersih += $userTotal;
        }

        // CEK APAKAH SEMUA USER ADALAH ORGANIK
        $isOnlyOrganik = count($uniqueCategories) === 1 && $uniqueCategories[0] === 'organik';

        $periodeStr = Carbon::parse($this->startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($this->endDate)->translatedFormat('d M Y');

        return view('exports.bulk_detail', [
            'users' => $users,
            'absensiData' => $absensiData,
            'periodeStr' => $periodeStr,
            'allDates' => $allDates,
            'grandTotalGajiPokok' => $grandTotalGajiPokok,
            'grandTotalGajiLembur' => $grandTotalGajiLembur,
            'grandTotalPotongan' => $grandTotalPotongan,
            'grandTotalGajiBersihRow' => $grandTotalGajiBersihRow,
            'grandTotalGajiBersih' => $grandTotalGajiBersih,
            'categoryLabel' => $categoryLabel,
            'isOnlyOrganik' => $isOnlyOrganik,
        ]);
    }

    private function detectKategori(User $user): string
    {
        return $user->employment_type ?? 'organik';
    }

    public function title(): string
    {
        return 'Rekap Detail Massal';
    }
}
