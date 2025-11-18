<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Absensi;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

// INI ADALAH "KOKI UTAMA" YANG BIKIN BANYAK SHEET
class BulkUserDetailExport implements WithMultipleSheets
{
    use Exportable;

    protected $userIds;
    protected $startDate;
    protected $endDate;
    protected $users;

    public function __construct(array $userIds, string $startDate, string $endDate)
    {
        $this->userIds = $userIds;
        $this->startDate = Carbon::parse($startDate)->startOfDay();
        $this->endDate = Carbon::parse($endDate)->endOfDay();

        // Kita ambil data user-nya dulu biar gampang
        $this->users = User::whereIn('id', $this->userIds)->orderBy('name')->get();
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        // Siapin label periode
        $periodeLabel = $this->startDate->format('d M Y') . " - " . $this->endDate->format('d M Y');

        // Bikin 1 sheet per karyawan
        foreach ($this->users as $user) {

            // 1. Ambil data absensi detail si user di range tanggal itu
            $absensiData = Absensi::where('user_id', $user->id)
                ->whereBetween('check_in_at', [$this->startDate, $this->endDate])
                ->where('status_approval', 'approved') // Kita ambil yg approved aja
                ->orderBy('check_in_at', 'asc')
                ->get();

            // 2. PANGGIL "RESEP" LAMA LO (AbsensiUserExport) BUAT BIKIN 1 SHEET
            // Ini kerennya, kita "manggil" resep yang udah lo punya
            $sheets[] = new AbsensiUserExport(
                $absensiData,
                $user,
                'custom', // Kita bilang ini filter 'custom'
                null, // $month (gak perlu)
                null, // $year (gak perlu)
                null  // $week (gak perlu)
            );
        }

        return $sheets;
    }
}
