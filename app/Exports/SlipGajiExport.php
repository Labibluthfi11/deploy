<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SlipGajiExport implements WithEvents, ShouldAutoSize
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

        // HITUNG TERBILANG DI SINI (BIAR AMAN)
        $gajiBersih = $stats['total_gaji_bersih'] ?? 0;
        $this->terbilangString = $this->penyebut($gajiBersih) . ' Rupiah';
    }

    /**
     * Fungsi Penyebut Angka (Manual & Aman)
     */
    private function penyebut($nilai) {
        $nilai = abs($nilai);
        $huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        $temp = "";
        if ($nilai < 12) {
            $temp = " ". $huruf[$nilai];
        } else if ($nilai <20) {
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
        }
        return $temp;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // VARIABEL WARNA (BIAR GANTENG)
                $blueHeader = 'BDD7EE'; // Biru Muda
                $greyTotal  = 'D9D9D9'; // Abu-abu
                $greenNet   = 'C6E0B4'; // Hijau Muda (Gaji Bersih)

                // AMBIL DATA
                $gajiPokok = $this->stats['total_gaji_pokok'] ?? 0;
                $gajiLembur = $this->stats['total_gaji_lembur'] ?? 0;
                $potongan = $this->stats['total_potongan'] ?? 0;
                $gajiBersih = $this->stats['total_gaji_bersih'] ?? 0;

                // SET LEBAR KOLOM MANUAL (BIAR RAPI)
                $sheet->getColumnDimension('A')->setWidth(30);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(5); // Spasi
                $sheet->getColumnDimension('D')->setWidth(30);
                $sheet->getColumnDimension('E')->setWidth(20);

                // --- HEADER ---
                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', 'PT. ANSEL MUDA BERKARYA');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A2:E2');
                $sheet->setCellValue('A2', 'SLIP GAJI KARYAWAN');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // --- DATA KARYAWAN ---
                $row = 4;
                $sheet->setCellValue('A'.$row, 'DATA KARYAWAN');
                $sheet->getStyle('A'.$row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A'.$row, 'Nama Karyawan');
                $sheet->setCellValue('B'.$row, ': ' . $this->user->name);
                $sheet->setCellValue('D'.$row, 'Periode Penggajian');
                $sheet->setCellValue('E'.$row, ': ' . $this->periode);
                $row++;

                $sheet->setCellValue('A'.$row, 'Nomor Induk Pegawai');
                $sheet->setCellValue('B'.$row, ': ' . ($this->user->id_karyawan ?? '-'));
                $sheet->setCellValue('D'.$row, 'Tipe Karyawan');
                $sheet->setCellValue('E'.$row, ': ' . ucfirst($this->user->employment_type));
                $row += 2; // Spasi

                // --- TABEL GAJI (HEADER) ---
                $sheet->setCellValue('A'.$row, 'PENGHASILAN');
                $sheet->setCellValue('D'.$row, 'POTONGAN');
                $sheet->getStyle('A'.$row)->getFont()->setBold(true);
                $sheet->getStyle('D'.$row)->getFont()->setBold(true);
                // Border Header Tabel
                $sheet->getStyle('A'.$row.':B'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('D'.$row.':E'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;

                // --- ISI TABEL ---
                $startRow = $row;
                // Kiri (Penghasilan)
                $sheet->setCellValue('A'.$row, 'Upah Harian (Total)');
                $sheet->setCellValue('B'.$row, $gajiPokok);
                $sheet->setCellValue('D'.$row, 'Potongan Keterlambatan');
                $sheet->setCellValue('E'.$row, $potongan);
                $row++;

                $sheet->setCellValue('A'.$row, 'Upah Lembur (Total)');
                $sheet->setCellValue('B'.$row, $gajiLembur);
                // Kanan (Potongan) - Kosongin baris kedua kalo gak ada potongan lain
                $sheet->setCellValue('D'.$row, '');
                $sheet->setCellValue('E'.$row, '');
                $row++;

                // Border Isi Tabel
                $sheet->getStyle('A'.$startRow.':B'.($row-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('D'.$startRow.':E'.($row-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // --- TOTAL ---
                $row++; // Spasi
                $sheet->setCellValue('A'.$row, 'TOTAL PENGHASILAN');
                $sheet->setCellValue('B'.$row, $gajiPokok + $gajiLembur);
                $sheet->setCellValue('D'.$row, 'TOTAL POTONGAN');
                $sheet->setCellValue('E'.$row, $potongan);

                // Style Total (Abu-abu)
                $sheet->getStyle('A'.$row.':E'.$row)->getFont()->setBold(true);
                $sheet->getStyle('A'.$row.':E'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($greyTotal);
                $sheet->getStyle('A'.$row.':E'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // --- GAJI BERSIH ---
                $row += 2;
                $sheet->mergeCells('A'.$row.':D'.$row);
                $sheet->setCellValue('A'.$row, 'PENGHASILAN BERSIH (TAKE HOME PAY)');
                $sheet->setCellValue('E'.$row, $gajiBersih);

                // Style Gaji Bersih (Hijau)
                $sheet->getStyle('A'.$row.':E'.$row)->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A'.$row.':E'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($greenNet);
                $sheet->getStyle('A'.$row.':E'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // --- TERBILANG ---
                $row++;
                $sheet->mergeCells('A'.$row.':E'.$row);
                $sheet->setCellValue('A'.$row, 'Terbilang: ' . ucwords($this->terbilangString));
                $sheet->getStyle('A'.$row)->getFont()->setItalic(true)->setBold(true);
                $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // --- FOOTER & TTD ---
                $row += 3;
                $sheet->mergeCells('A'.$row.':E'.$row);
                $sheet->setCellValue('A'.$row, '“Keep Up The Good Work”');
                $sheet->getStyle('A'.$row)->getFont()->setItalic(true)->setBold(true)->setName('Times New Roman');
                $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $row += 2;
                $sheet->mergeCells('D'.$row.':E'.$row);
                $sheet->setCellValue('D'.$row, 'HRGA Division');
                $sheet->getStyle('D'.$row)->getFont()->setBold(true);
                $sheet->getStyle('D'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $row++;
                $sheet->mergeCells('D'.$row.':E'.$row);
                $sheet->setCellValue('D'.$row, 'PT. ANSEL MUDA BERKARYA');
                $sheet->getStyle('D'.$row)->getFont()->setBold(true);
                $sheet->getStyle('D'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // --- FORMAT RUPIAH ---
                $rupiahFormat = '"Rp" #,##0';
                // Format kolom B (Penghasilan) dan E (Potongan) dari baris awal tabel sampe bawah
                $sheet->getStyle('B10:B50')->getNumberFormat()->setFormatCode($rupiahFormat);
                $sheet->getStyle('E10:E50')->getNumberFormat()->setFormatCode($rupiahFormat);
            },
        ];
    }
}
