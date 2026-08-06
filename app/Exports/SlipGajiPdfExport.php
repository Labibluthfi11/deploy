<?php

namespace App\Exports;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class SlipGajiPdfExport
{
    protected $user;
    protected $stats;
    protected $periode;
    protected $terbilangString;

    public function __construct(User $user, array $stats, string $periode)
    {
        $this->user = $user;
        $this->stats = $stats;
        $this->periode = $periode;
        $gajiBersih = $stats['total_gaji_bersih'] ?? 0;
        $this->terbilangString = $this->penyebut($gajiBersih) . ' Rupiah';
    }

    private function penyebut($nilai) {
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

    public function generate()
    {
        $gajiPokok = $this->stats['total_gaji_pokok'] ?? 0;
        $gajiLembur = $this->stats['total_gaji_lembur'] ?? 0;
        $potongan = $this->stats['total_potongan'] ?? 0;
        $gajiBersih = $this->stats['total_gaji_bersih'] ?? 0;
        $totalMenit = $this->stats['total_menit_lembur'] ?? 0;
        $durasiLembur = floor($totalMenit / 60) . " Jam " . ($totalMenit % 60) . " Menit";

        $data = [
            'user' => $this->user,
            'periode' => $this->periode,
            'gajiPokok' => $gajiPokok,
            'gajiLembur' => $gajiLembur,
            'potongan' => $potongan,
            'adjustment' => $this->stats['total_adjustment'] ?? 0,
            'gajiBersih' => $gajiBersih,
            'durasiLembur' => $durasiLembur,
            'terbilang' => ucwords($this->terbilangString),
        ];

        $pdf = Pdf::loadView('exports.slip-gaji-pdf', $data)
            ->setPaper('a4', 'portrait');

        return $pdf;
    }
}
