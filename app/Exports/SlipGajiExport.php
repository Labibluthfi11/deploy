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

                // 1. SETUP HALAMAN A4
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setRight(0.5);
                $sheet->getPageMargins()->setLeft(0.5);
                $sheet->getPageMargins()->setBottom(0.5);

                // 2. LEBAR KOLOM (Setting Manual biar Rapi)
                $sheet->getColumnDimension('A')->setWidth(25); // Label Kiri
                $sheet->getColumnDimension('B')->setWidth(25); // Isi Kiri
                $sheet->getColumnDimension('C')->setWidth(2);  // Spasi Tengah (Kecil aja)
                $sheet->getColumnDimension('D')->setWidth(25); // Label Kanan
                $sheet->getColumnDimension('E')->setWidth(30); // Isi Kanan (Lebar buat Tanggal)

                // 3. WARNA
                $bgHeader = 'BDD7EE';
                $bgTotal = 'D9D9D9';
                $bgNet = 'C6E0B4';
                $bgYellow = 'FFFF00';

                // DATA
                $gajiPokok = $this->stats['total_gaji_pokok'] ?? 0;
                $gajiLembur = $this->stats['total_gaji_lembur'] ?? 0;
                $potongan = $this->stats['total_potongan'] ?? 0;
                $gajiBersih = $this->stats['total_gaji_bersih'] ?? 0;
                $totalMenit = $this->stats['total_menit_lembur'] ?? 0;
                $durasiLembur = floor($totalMenit / 60) . " Jam " . ($totalMenit % 60) . " Menit";

                $row = 1;

                // ==========================================
                // HEADER (NO BORDER)
                // ==========================================
                // Logo
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

                // Judul
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
                $row += 2; // Spasi

                // ==========================================
                // DATA KARYAWAN (KOTAK TERPISAH KIRI & KANAN)
                // ==========================================

                // --- KIRI: DATA KARYAWAN ---
                $rowKiriStart = $row;
                $sheet->mergeCells("A{$row}:B{$row}");
                $sheet->setCellValue("A{$row}", "DATA KARYAWAN");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                // Garis bawah judul kecil
                $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
                $row++;

                $sheet->setCellValue("A{$row}", "Nama Karyawan");
                $sheet->setCellValue("B{$row}", ": " . $this->user->name);
                $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true); // BOLD SEMUA
                $row++;

                $sheet->setCellValue("A{$row}", "Nomor Induk Pegawai");
                $sheet->setCellValue("B{$row}", ": " . ($this->user->id_karyawan ?? '-'));
                $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true); // BOLD SEMUA

                // BIKIN KOTAK KIRI (OUTLINE)
                $sheet->getStyle("A{$rowKiriStart}:B{$row}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);


                // --- KANAN: PERIODE (SEJAJAR) ---
                // Kita reset $row ke posisi awal biar sejajar
                $rowKananStart = $rowKiriStart;
                $rowKananEnd = $row; // Samain tinggi bawahnya

                // Header Kanan (Kosongin aja tapi kasih border atas biar rapi, atau isi label lain)
                // Biar persis gambar 1d9562.png (Kotak kanan atasnya kosong/garis doang)
                $sheet->mergeCells("D{$rowKananStart}:E{$rowKananStart}");
                $sheet->getStyle("D{$rowKananStart}:E{$rowKananStart}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

                $rowTemp = $rowKananStart + 1;

                $sheet->setCellValue("D{$rowTemp}", "Periode Penggajian");
                $sheet->setCellValue("E{$rowTemp}", ": " . $this->periode);
                $sheet->getStyle("D{$rowTemp}:E{$rowTemp}")->getFont()->setBold(true); // BOLD SEMUA
                $rowTemp++;

                $sheet->setCellValue("D{$rowTemp}", "Tipe Karyawan");
                $sheet->setCellValue("E{$rowTemp}", ": " . ucfirst($this->user->employment_type));
                $sheet->getStyle("D{$rowTemp}:E{$rowTemp}")->getFont()->setBold(true); // BOLD SEMUA

                // BIKIN KOTAK KANAN (OUTLINE)
                $sheet->getStyle("D{$rowKananStart}:E{$rowKananEnd}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

                $row++; // Pindah baris baru buat tabel gaji
                $row++; // Spasi

                // ==========================================
                // TABEL GAJI (KOTAK TERPISAH KIRI & KANAN)
                // ==========================================
                $tableStart = $row;

                // HEADER
                $sheet->setCellValue("A{$row}", "PENGHASILAN");
                $sheet->setCellValue("B{$row}", ""); // Merge visual
                $sheet->setCellValue("D{$row}", "POTONGAN");
                $sheet->setCellValue("E{$row}", "");

                // Style Header Biru & Border
                $styleHeader = [
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgHeader]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ];
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($styleHeader);
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray($styleHeader);
                $row++;

                // ISI TABEL
                $contentStart = $row;

                // Baris 1
                $sheet->setCellValue("A{$row}", "Upah Harian (Total)");
                $sheet->setCellValue("B{$row}", $gajiPokok);
                $sheet->setCellValue("D{$row}", "Potongan Keterlambatan");
                $sheet->setCellValue("E{$row}", $potongan);
                $row++;

                // Baris 2
                $sheet->setCellValue("A{$row}", "Upah Lembur (Total)");
                $sheet->setCellValue("B{$row}", $gajiLembur);
                $sheet->setCellValue("D{$row}", "");
                $sheet->setCellValue("E{$row}", "");
                $row++;

                // Baris 3
                $sheet->setCellValue("A{$row}", "Jumlah Jam Lembur");
                $sheet->setCellValue("B{$row}", ": " . $durasiLembur);
                $sheet->setCellValue("D{$row}", "");
                $sheet->setCellValue("E{$row}", "");

                // BORDER ISI (KIRI & KANAN PISAH)
                // Kotak Kiri
                $sheet->getStyle("A{$contentStart}:B{$row}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                // Garis tengah pemisah label/value kiri
                $sheet->getStyle("A{$contentStart}:A{$row}")->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);

                // Kotak Kanan
                $sheet->getStyle("D{$contentStart}:E{$row}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                // Garis tengah pemisah label/value kanan
                $sheet->getStyle("D{$contentStart}:D{$row}")->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);

                $row++;

                // ==========================================
                // TOTAL (ABU-ABU)
                // ==========================================
                $sheet->setCellValue("A{$row}", "TOTAL PENGHASILAN");
                $sheet->setCellValue("B{$row}", $gajiPokok + $gajiLembur);
                $sheet->setCellValue("D{$row}", "TOTAL POTONGAN");
                $sheet->setCellValue("E{$row}", $potongan);

                $styleTotal = [
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgTotal]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ];
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($styleTotal);
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray($styleTotal);

                $row += 2; // Spasi

                // ==========================================
                // GAJI BERSIH (HIJAU) & TERBILANG (KUNING)
                // ==========================================

                // Gaji Bersih
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

                // Terbilang
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", "Terbilang: " . ucwords($this->terbilangString));
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgYellow]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);

                $row += 3; // Jarak ke Footer

                // ==========================================
                // FOOTER (CENTER TOTAL)
                // ==========================================
                // Merge A-E biar beneran center di halaman A4

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
                $formatRupiah = '"Rp "#,##0';
                $sheet->getStyle('B1:B100')->getNumberFormat()->setFormatCode($formatRupiah);
                $sheet->getStyle('E1:E100')->getNumberFormat()->setFormatCode($formatRupiah);

            },
        ];
    }
}
