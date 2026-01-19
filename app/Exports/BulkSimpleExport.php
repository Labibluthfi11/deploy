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
        // Ambil users yang dipilih, urutkan by name
        $users = User::whereIn('id', $this->userIds)->orderBy('name')->get();

        // Generate semua tanggal di range
        $allDates = [];
        $current = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        while ($current <= $end) {
            $allDates[] = $current->copy();
            $current->addDay();
        }

        // Ambil semua absensi di range (approved only)
        $absensiData = Absensi::whereIn('user_id', $this->userIds)
            ->whereBetween('check_in_at', [$this->startDate, $this->endDate])
            ->where('status_approval', 'approved')
            ->orderBy('check_in_at', 'asc')
            ->get();

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

        // 🔥 SPLIT TANGGAL PER 16 HARI & PER BULAN
        $dateGroups = $this->splitDatesByMonth($allDates);

        $periodeStr = Carbon::parse($this->startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($this->endDate)->translatedFormat('d M Y');

        return view('exports.bulk_simple', [
            'users' => $users,
            'absensiData' => $absensiData,
            'dateGroups' => $dateGroups,
            'periodeStr' => $periodeStr,
            'categoryLabel' => $categoryLabel,
        ]);
    }

    /**
     * Split tanggal per bulan, lalu per 16 hari
     */
   private function splitDatesByMonth(array $allDates): array
{
    $grouped = [];

    foreach ($allDates as $date) {
        $key = $date->format('Y-m');

        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'month_label' => strtoupper($date->translatedFormat('F Y')),
                'dates' => []
            ];
        }

        $grouped[$key]['dates'][] = $date;
    }

    $result = [];

    foreach ($grouped as $data) {
        $chunks = array_chunk($data['dates'], 11);

        foreach ($chunks as $chunk) {
            $result[] = [
                'month_label' => $data['month_label'],
                'dates' => $chunk
            ];
        }
    }

    return $result;
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
