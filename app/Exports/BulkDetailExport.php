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
            ->where('status_approval', 'approved')
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
        $grandTotalGajiBersih = 0;

        foreach ($users as $user) {
            $userAbsensi = $absensiData->where('user_id', $user->id);

            $grandTotalGajiPokok += $userAbsensi->sum('base_salary');
            $grandTotalGajiLembur += $userAbsensi->sum('overtime_pay');
            $grandTotalGajiBersih += $userAbsensi->sum('final_salary');
        }

        $periodeStr = Carbon::parse($this->startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($this->endDate)->translatedFormat('d M Y');

        return view('exports.bulk_detail', [
            'users' => $users,
            'absensiData' => $absensiData,
            'periodeStr' => $periodeStr,
            'allDates' => $allDates, // 🔥 KIRIM LIST TANGGAL
            'grandTotalGajiPokok' => $grandTotalGajiPokok,
            'grandTotalGajiLembur' => $grandTotalGajiLembur,
            'grandTotalGajiBersih' => $grandTotalGajiBersih,
            'categoryLabel' => $categoryLabel,
        ]);
    }

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

    public function title(): string
    {
        return 'Rekap Detail Massal';
    }
}
