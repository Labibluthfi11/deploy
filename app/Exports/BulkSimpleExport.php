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
            ->whereNull('parent_id')
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

        // 🔥 CHUNK TANGGAL JADI 11 HARI PER ROW
        $dateChunks = array_chunk($allDates, 11);

        return view('exports.bulk_simple', [
            'users' => $users,
            'absensiData' => $absensiData,
            'allDates' => $allDates,
            'dateChunks' => $dateChunks,
            'periodeStr' => $periodeStr,
            'categoryLabel' => $categoryLabel,
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
        return 'Absensi Simple';
    }
}