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

        // Hitung Terbilang
        $gajiBersih = $stats['total_gaji_bersih'] ?? 0;
        $this->terbilangString = $this->penyebut($gajiBersih) . ' Rupiah';
    }

    // Fungsi Terbilang Manual
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

                // 1. CONFIG HALAMAN (A4 PORTRAIT, FIT 1 HALAMAN)
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setRight(0.5);
                $sheet->getPageMargins()->setLeft(0.5);
                $sheet->getPageMargins()->setBottom(0.5);

                // 2. LEBAR KOLOM (Disesuaikan biar Periode MUAT & Rapi)
                $sheet->getColumnDimension('A')->setWidth(22); // Label Kiri
                $sheet->getColumnDimension('B')->setWidth(25); // Isi Kiri
                $sheet->getColumnDimension('C')->setWidth(3);  // Spasi Tengah
                $sheet->getColumnDimension('D')->setWidth(22); // Label Kanan
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

                // ================= HEADER =================
                // Logo (Opsional)
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

                // Judul PT & Slip (Full Width Merge A-E) - NO BORDER
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

                // ================= DATA KARYAWAN (KOTAK RAPI) =================
                // KITA BIKIN 2 KOTAK TERPISAH (KIRI & KANAN) BIAR RAPI

                // --- KOTAK KIRI (Nama & NIP) ---
                $startRow = $row;
                $sheet->mergeCells("A{$row}:B{$row}");
                $sheet->setCellValue("A{$row}", "DATA KARYAWAN");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;

                // Label & Isi (BOLD SEMUA SESUAI REQUEST)
                $sheet->setCellValue("A{$row}", "Nama Karyawan");
                $sheet->setCellValue("B{$row}", ": " . $this->user->name);
                $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true); // Bold Baris Ini
                $row++;

                $sheet->setCellValue("A{$row}", "Nomor Induk Pegawai");
                $sheet->setCellValue("B{$row}", ": " . ($this->user->id_karyawan ?? '-'));
                $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true); // Bold Baris Ini

                // BORDER KOTAK KIRI
                $sheet->getStyle("A{$startRow}:B{$row}")->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]]
                ]);
                // Garis bawah judul "DATA KARYAWAN"
                $sheet->getStyle("A{$startRow}:B{$startRow}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);


                // --- KOTAK KANAN (Periode & Tipe) ---
                // Mulai dari baris yg sama kayak "Nama Karyawan" (startRow + 1)
                $rightStartRow = $startRow + 1;
                $rightEndRow = $row;

                // Kita bikin kotak kosong dulu buat header kanan (opsional, atau langsung data)
                // Sesuai gambar, kanan gak ada judul "DATA KARYAWAN", langsung isi.
                // Kita kasih border kotak buat isinya aja.

                $sheet->setCellValue("D{$rightStartRow}", "Periode Penggajian");
                $sheet->setCellValue("E{$rightStartRow}", ": " . $this->periode);
                $sheet->getStyle("D{$rightStartRow}:E{$rightStartRow}")->getFont()->setBold(true);

                $sheet->setCellValue("D{$rightEndRow}", "Tipe Karyawan");
                $sheet->setCellValue("E{$rightEndRow}", ": " . ucfirst($this->user->employment_type));
                $sheet->getStyle("D{$rightEndRow}:E{$rightEndRow}")->getFont()->setBold(true);

                // BORDER KOTAK KANAN
                $sheet->getStyle("D{$rightStartRow}:E{$rightEndRow}")->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]]
                ]);

                $row += 2; // Spasi

                // ================= TABEL GAJI (PENGHASILAN & POTONGAN) =================
                $tableHeaderRow = $row;

                // Header
                $sheet->setCellValue("A{$row}", "PENGHASILAN");
                $sheet->setCellValue("B{$row}", ""); // Merge visual
                $sheet->setCellValue("D{$row}", "POTONGAN");
                $sheet->setCellValue("E{$row}", "");

                // Style Header Tabel
                $styleTableHeader = [
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgHeader]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ];
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($styleTableHeader);
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray($styleTableHeader);
                $row++;

                // Isi
                $startContent = $row;

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

                // Baris 3 (Durasi)
                $sheet->setCellValue("A{$row}", "Jumlah Jam Lembur");
                $sheet->setCellValue("B{$row}", ": " . $durasiLembur);
                $sheet->setCellValue("D{$row}", "");
                $sheet->setCellValue("E{$row}", "");

                // Border Isi Tabel (Outline only biar dalemnya bersih kayak gambar)
                $sheet->getStyle("A{$startContent}:B{$row}")->applyFromArray(['borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]]]);
                $sheet->getStyle("D{$startContent}:E{$row}")->applyFromArray(['borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]]]);

                // Border tengah pemisah label & value (opsional, biar rapi)
                $sheet->getStyle("A{$startContent}:A{$row}")->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("D{$startContent}:D{$row}")->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);

                $row++;

                // ================= TOTAL (ABU-ABU) =================
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
                $row += 2;

                // ================= NET PAY & TERBILANG =================
                // Net Pay (Hijau)
                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->setCellValue("A{$row}", "PENGHASILAN BERSIH (TAKE HOME PAY)");
                $sheet->setCellValue("E{$row}", $gajiBersih);
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgNet]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                ]);
                $row++;

                // Terbilang (Kuning)
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", "Terbilang: " . ucwords($this->terbilangString));
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgYellow]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);
                $row += 3;

                // ================= FOOTER (CENTER PAGE) =================
                // Kita merge A sampe E biar beneran center di halaman

                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", '"Keep Up The Good Work"');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true],
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
