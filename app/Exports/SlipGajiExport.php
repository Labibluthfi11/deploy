<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class SlipGajiExport implements WithEvents
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
        }
        return $temp;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // --- FONT GLOBAL: TIMES NEW ROMAN ---
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman');

                // 1. SETUP HALAMAN A4
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setRight(0.5);
                $sheet->getPageMargins()->setLeft(0.5);
                $sheet->getPageMargins()->setBottom(0.5);

                // 2. LEBAR KOLOM
                $sheet->getColumnDimension('A')->setWidth(25);
                $sheet->getColumnDimension('B')->setWidth(25);
                $sheet->getColumnDimension('C')->setWidth(2); // Spasi Kecil
                $sheet->getColumnDimension('D')->setWidth(25);
                $sheet->getColumnDimension('E')->setWidth(28);

                // 3. VARIABEL WARNA & STYLE
                $bgHeader = 'BDD7EE';
                $bgTotal  = 'D9D9D9';
                $bgNet    = 'C6E0B4';
                $bgYellow = 'FFFF00';

                // Style Kotak Full (Grid)
                $styleGrid = [
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ];

                // Style Outline Saja (Opsional)
                $styleOutline = [
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]]
                ];

                // DATA
                $gajiPokok = $this->stats['total_gaji_pokok'] ?? 0;
                $gajiLembur = $this->stats['total_gaji_lembur'] ?? 0;
                $adjustment = $this->stats['total_adjustment'] ?? 0;
                $potongan = $this->stats['total_potongan'] ?? 0;
                $gajiBersih = $this->stats['total_gaji_bersih'] ?? 0;
                $totalMenit = $this->stats['total_menit_lembur'] ?? 0;
                $durasiLembur = floor($totalMenit / 60) . " Jam " . ($totalMenit % 60) . " Menit";

                $row = 1;

                // ================= HEADER PT =================
                $logoPath = public_path('images/logo.png');
                if (file_exists($logoPath)) {
                    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $drawing->setName('Logo');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(100);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(10);
                    $drawing->setOffsetY(-20);
                    $drawing->setWorksheet($sheet);
                }

                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", 'PT. ANSEL MUDA BERKARYA');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgHeader]]
                ]);
                $row++;

                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", 'SLIP GAJI KARYAWAN');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgHeader]]
                ]);
                $row += 2;

                // ================= DATA KARYAWAN =================
                $rowStartData = $row;

                // --- HEADER KIRI "DATA KARYAWAN" ---
                $sheet->mergeCells("A{$row}:B{$row}");
                $sheet->setCellValue("A{$row}", "DATA KARYAWAN");
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($styleGrid); // Kotak
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);

                // 🔥 KANAN KOSONG (D-E) -> JANGAN DI APA-APAIN (NO BORDER) 🔥

                $row++;

                // --- ISI DATA (KIRI & KANAN) ---
                $dataBodyStart = $row;

                // Baris 1
                $sheet->setCellValue("A{$row}", "Nama Karyawan");
                $sheet->setCellValue("B{$row}", ": " . $this->user->name);
                $sheet->setCellValue("D{$row}", "Periode Penggajian");
                $sheet->setCellValue("E{$row}", ": " . $this->periode);
                $row++;

                // Baris 2
                $sheet->setCellValue("A{$row}", "Nomor Induk Pegawai");
                $sheet->setCellValue("B{$row}", ": " . ($this->user->id_karyawan ?? '-'));
                $sheet->setCellValue("D{$row}", "Tipe Karyawan");
                $sheet->setCellValue("E{$row}", ": " . ucfirst($this->user->employment_type));
                $dataBodyEnd = $row;

                // STYLE: BOLD SEMUA & KOTAK TERPISAH
                $sheet->getStyle("A{$dataBodyStart}:B{$dataBodyEnd}")->getFont()->setBold(true);
                $sheet->getStyle("D{$dataBodyStart}:E{$dataBodyEnd}")->getFont()->setBold(true);

                // Kotak Kiri
                $sheet->getStyle("A{$dataBodyStart}:B{$dataBodyEnd}")->applyFromArray($styleGrid);
                // Kotak Kanan
                $sheet->getStyle("D{$dataBodyStart}:E{$dataBodyEnd}")->applyFromArray($styleGrid);

                $row++;
                $row++; // Spasi

                // ================= TABEL PENGHASILAN (FULL GRID) =================
                $tableStart = $row;

                // HEADER
                $sheet->setCellValue("A{$row}", "PENGHASILAN");
                $sheet->setCellValue("B{$row}", "");
                $sheet->setCellValue("D{$row}", "POTONGAN");
                $sheet->setCellValue("E{$row}", "");

                $styleHeaderTabel = [
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgHeader]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ];
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($styleHeaderTabel);
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray($styleHeaderTabel);
                $row++;

                // ISI TABEL
                $rowIsi = $row;

                $sheet->setCellValue("A{$row}", "Upah Harian (Total)");
                $sheet->setCellValue("B{$row}", $gajiPokok);
                $sheet->setCellValue("D{$row}", "Potongan Keterlambatan");
                $sheet->setCellValue("E{$row}", $potongan);
                $row++;

                $sheet->setCellValue("A{$row}", "Upah Lembur (Total)");
                $sheet->setCellValue("B{$row}", $gajiLembur);
                $sheet->setCellValue("D{$row}", "Penyesuaian Gaji (Adj)");
                $sheet->setCellValue("E{$row}", $adjustment);
                $row++;

                $sheet->setCellValue("A{$row}", "Jumlah Jam Lembur");
                $sheet->setCellValue("B{$row}", ": " . $durasiLembur);
                $sheet->setCellValue("D{$row}", "");
                $sheet->setCellValue("E{$row}", "");

                // 🔥 FULL GRID (GARIS-GARIS DI DALAM) 🔥
                $sheet->getStyle("A{$rowIsi}:B{$row}")->applyFromArray($styleGrid);
                $sheet->getStyle("D{$rowIsi}:E{$row}")->applyFromArray($styleGrid);

                $row++;

                // ================= TOTAL (ABU-ABU & GRID) =================
                $sheet->setCellValue("A{$row}", "TOTAL PENGHASILAN");
                $sheet->setCellValue("B{$row}", $gajiPokok + $gajiLembur + ($adjustment > 0 ? $adjustment : 0));
                $sheet->setCellValue("D{$row}", "TOTAL POTONGAN");
                $sheet->setCellValue("E{$row}", $potongan + ($adjustment < 0 ? abs($adjustment) : 0));

                $styleTotal = [
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgTotal]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ];
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($styleTotal);
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray($styleTotal);

                $row += 2;

                // ================= GAJI BERSIH (HIJAU) =================
                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->setCellValue("A{$row}", "PENGHASILAN BERSIH (TAKE HOME PAY)");
                $sheet->setCellValue("E{$row}", $gajiBersih);

                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgNet]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                ]);
                $row++;

                // ================= TERBILANG (KUNING) =================
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", "Terbilang: " . ucwords($this->terbilangString));
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgYellow]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);

                $row += 3;

                // ================= FOOTER (CENTER TOTAL A-E) =================
                // 🔥 CENTER: Merge A sampe E biar persis di tengah 🔥

                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", '"Keep Up The Good Work"');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true, 'name' => 'Times New Roman'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);
                $row += 2;

                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", "HRGA Division");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);
                $row++;

                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", "PT. ANSEL MUDA BERKARYA");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);

                // FORMAT RUPIAH
                $sheet->getStyle('B1:B100')->getNumberFormat()->setFormatCode('"Rp "#,##0');
                $sheet->getStyle('E1:E100')->getNumberFormat()->setFormatCode('"Rp "#,##0');

            },
        ];
    }
}
