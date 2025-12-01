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
        // Ambil user yang dipilih, urutin namanya
        $users = User::whereIn('id', $this->userIds)->orderBy('name')->get();

        // Ambil SEMUA data absensi yang relevan sekaligus
        $absensiData = Absensi::whereIn('user_id', $this->userIds)
            ->whereBetween('check_in_at', [$this->startDate, $this->endDate])
            ->where('status_approval', 'approved')
            ->orderBy('check_in_at', 'asc')
            ->get();

        // 🔥 DETEKSI KATEGORI USER YANG DIPILIH 🔥
        $categories = [];
        foreach ($users as $user) {
            $kategori = $this->detectKategori($user);
            $categories[] = $kategori;
        }

        // Cek apakah semua kategori sama
        $uniqueCategories = array_unique($categories);

        if (count($uniqueCategories) === 1) {
            // Semua user punya kategori yang sama
            $singleCategory = $uniqueCategories[0];
            $categoryLabel = match($singleCategory) {
                'organik' => 'KARYAWAN ORGANIK',
                'freelance' => 'KARYAWAN FREELANCE',
                'borongan' => 'KARYAWAN BORONGAN',
                'magang' => 'KARYAWAN MAGANG',
                default => 'SEMUA KARYAWAN'
            };
        } else {
            // Campur-campur kategori
            $categoryLabel = 'SEMUA KARYAWAN';
        }

        // 🔥 HITUNG TOTAL KESELURUHAN (3 AJA) 🔥
        $grandTotalGajiPokok = 0;
        $grandTotalGajiLembur = 0;
        $grandTotalGajiBersih = 0;

        foreach ($users as $user) {
            $userAbsensi = $absensiData->where('user_id', $user->id);

            $grandTotalGajiPokok += $userAbsensi->sum('base_salary');
            $grandTotalGajiLembur += $userAbsensi->sum('overtime_pay');
            $grandTotalGajiBersih += $userAbsensi->sum('final_salary');
        }

        // Bikin label periode
        $periodeStr = Carbon::parse($this->startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($this->endDate)->translatedFormat('d M Y');

        // Lempar semua data ke Blade (termasuk GRAND TOTAL)
        return view('exports.bulk_detail', [
            'users' => $users,
            'absensiData' => $absensiData,
            'periodeStr' => $periodeStr,

            // 🔥 KIRIM DATA TOTAL KE VIEW 🔥
            'grandTotalGajiPokok' => $grandTotalGajiPokok,
            'grandTotalGajiLembur' => $grandTotalGajiLembur,
            'grandTotalGajiBersih' => $grandTotalGajiBersih,
            'categoryLabel' => $categoryLabel, // 🔥 LABEL KATEGORI DINAMIS
        ]);
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

    public function title(): string
    {
        return 'Rekap Detail Massal';
    }
}
