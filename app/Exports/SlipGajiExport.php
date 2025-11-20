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

        $gajiBersih = $stats['total_gaji_bersih'] ?? 0;
        $this->terbilangString = $this->penyebut($gajiBersih) . ' Rupiah';
    }

    private function penyebut($nilai) {
        $nilai = abs($nilai);
        $huruf = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
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

                // 🎨 WARNA
                $blueHeader = 'BDD7EE';
                $yellowTerbilang = 'FFFF00';
                $greenBersih = 'C6E0B4';

                // 📊 DATA
                $jumlahHariKerja = $this->stats['total_hadir'] ?? 0;
                $gajiPokok = $this->stats['total_gaji_pokok'] ?? 0;
                $gajiLembur = $this->stats['total_gaji_lembur'] ?? 0;
                $potongan = $this->stats['total_potongan'] ?? 0;
                $gajiBersih = $this->stats['total_gaji_bersih'] ?? 0;
                $totalMenitLembur = $this->stats['total_menit_lembur'] ?? 0;
                $jumlahJamLembur = floor($totalMenitLembur / 60);

                // 📐 SET LEBAR KOLOM
                $sheet->getColumnDimension('A')->setWidth(25);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(25);
                $sheet->getColumnDimension('D')->setWidth(18);

                $row = 1;

                // ═══════════════════════════════════════
                // 📌 HEADER
                // ═══════════════════════════════════════
                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->setCellValue("A{$row}", 'PT. ANSEL MUDA BERKARYA');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $row++;

                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->setCellValue("A{$row}", 'SLIP GAJI KARYAWAN');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $row += 2;

                // ═══════════════════════════════════════
                // 📌 DATA KARYAWAN
                // ═══════════════════════════════════════
                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->setCellValue("A{$row}", 'DATA KARYAWAN');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $blueHeader]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $row++;

                $sheet->setCellValue("A{$row}", 'Nama Karyawan');
                $sheet->setCellValue("B{$row}", ': ' . $this->user->name);
                $sheet->setCellValue("C{$row}", 'Periode Penggajian');
                $sheet->setCellValue("D{$row}", ': ' . $this->periode);
                $sheet->getStyle("A{$row}:D{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;

                $sheet->setCellValue("A{$row}", 'Nomor Induk Pegawai');
                $sheet->setCellValue("B{$row}", ': ' . ($this->user->id_karyawan ?? '-'));
                $sheet->setCellValue("C{$row}", 'Tipe Karyawan');
                $sheet->setCellValue("D{$row}", ': ' . ucfirst($this->user->employment_type));
                $sheet->getStyle("A{$row}:D{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row += 2;

                // ═══════════════════════════════════════
                // 📌 TABEL PENGHASILAN (KIRI) & POTONGAN (KANAN) - PISAH!
                // ═══════════════════════════════════════

                // HEADER
                $sheet->setCellValue("A{$row}", 'PENGHASILAN');
                $sheet->setCellValue("C{$row}", 'POTONGAN');
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $blueHeader]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->getStyle("C{$row}:D{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $blueHeader]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $row++;

                // Baris 1
                $sheet->setCellValue("A{$row}", 'Upah Harian (Total)');
                $sheet->setCellValue("B{$row}", $gajiPokok);
                $sheet->setCellValue("C{$row}", 'Potongan Keterlambatan');
                $sheet->setCellValue("D{$row}", $potongan);
                $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("C{$row}:D{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;

                // Baris 2
                $sheet->setCellValue("A{$row}", 'Upah Lembur (Total)');
                $sheet->setCellValue("B{$row}", $gajiLembur);
                $sheet->setCellValue("C{$row}", ''); // Kosong
                $sheet->setCellValue("D{$row}", '');
                $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("C{$row}:D{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row += 2;

                // ═══════════════════════════════════════
                // 📌 TOTAL
                // ═══════════════════════════════════════
                $sheet->setCellValue("A{$row}", 'TOTAL PENGHASILAN');
                $sheet->setCellValue("B{$row}", $gajiPokok + $gajiLembur);
                $sheet->setCellValue("C{$row}", 'TOTAL POTONGAN');
                $sheet->setCellValue("D{$row}", $potongan);
                $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $row += 2;

                // ═══════════════════════════════════════
                // 📌 GAJI BERSIH
                // ═══════════════════════════════════════
                $sheet->mergeCells("A{$row}:C{$row}");
                $sheet->setCellValue("A{$row}", 'PENGHASILAN BERSIH (TAKE HOME PAY)');
                $sheet->setCellValue("D{$row}", $gajiBersih);
                $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $greenBersih]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $row++;

                // ═══════════════════════════════════════
                // 📌 TERBILANG
                // ═══════════════════════════════════════
                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->setCellValue("A{$row}", 'Terbilang: ' . ucwords($this->terbilangString));
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $yellowTerbilang]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $row += 3;

                // ═══════════════════════════════════════
                // 🆕 JUMLAH HARI & JAM LEMBUR (MASUK TABEL!)
                // ═══════════════════════════════════════
                $sheet->setCellValue("A{$row}", 'Jumlah Hari Kerja');
                $sheet->setCellValue("B{$row}", ': ' . $jumlahHariKerja . ' Hari');
                $sheet->setCellValue("C{$row}", 'Jumlah Jam Lembur');
                $sheet->setCellValue("D{$row}", ': ' . $jumlahJamLembur . ' Jam');
                $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("C{$row}:D{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row += 3;

                // ═══════════════════════════════════════
                // 📌 FOOTER
                // ═══════════════════════════════════════
                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->setCellValue("A{$row}", '"Keep Up The Good Work"');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['italic' => true, 'bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $row += 3;

                $sheet->mergeCells("C{$row}:D{$row}");
                $sheet->setCellValue("C{$row}", 'HRGA Division');
                $sheet->getStyle("C{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $row++;

                $sheet->mergeCells("C{$row}:D{$row}");
                $sheet->setCellValue("C{$row}", 'PT. ANSEL MUDA BERKARYA');
                $sheet->getStyle("C{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ═══════════════════════════════════════
                // 💰 FORMAT RUPIAH
                // ═══════════════════════════════════════
                $rupiahFormat = '"Rp "#,##0';
                $sheet->getStyle('B1:B100')->getNumberFormat()->setFormatCode($rupiahFormat);
                $sheet->getStyle('D1:D100')->getNumberFormat()->setFormatCode($rupiahFormat);
            },
        ];
    }
}
