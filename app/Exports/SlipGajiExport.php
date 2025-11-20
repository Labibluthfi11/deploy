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
                $greyTotal = 'D9D9D9';
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
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(2);  // SPACE (NO BORDER!)
                $sheet->getColumnDimension('D')->setWidth(25);
                $sheet->getColumnDimension('E')->setWidth(20);

                $row = 1;

                // ═══════════════════════════════════════
                // 🖼️ INSERT LOGO (KIRI ATAS)
                // ═══════════════════════════════════════
                $logoPath = public_path('images/logo.png');
                if (file_exists($logoPath)) {
                    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $drawing->setName('Logo Ansel');
                    $drawing->setDescription('Logo PT Ansel Muda Berkarya');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(80); // Tinggi logo (pixel)
                    $drawing->setCoordinates('A1'); // Posisi logo
                    $drawing->setOffsetX(10); // Geser ke kanan 10px
                    $drawing->setOffsetY(8); // Geser ke bawah 8px
                    $drawing->setWorksheet($sheet);
                }

                // ═══════════════════════════════════════
                // 📌 HEADER (BIRU + MERGE + CENTER + NO BORDER!)
                // ═══════════════════════════════════════
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", 'PT. ANSEL MUDA BERKARYA');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $blueHeader]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $row++;

                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", 'SLIP GAJI KARYAWAN');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $blueHeader]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $row += 2;

                // ═══════════════════════════════════════
                // 📌 DATA KARYAWAN (PUTIH + ALL BORDERS KECUALI KOLOM C!)
                // ═══════════════════════════════════════
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", 'DATA KARYAWAN');
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $row++;

                // Baris 1: Nama & Periode (BOLD LABEL)
                $sheet->setCellValue("A{$row}", 'Nama Karyawan');
                $sheet->setCellValue("B{$row}", ': ' . $this->user->name);
                $sheet->setCellValue("D{$row}", 'Periode Penggajian');
                $sheet->setCellValue("E{$row}", ': ' . $this->periode);
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("D{$row}:E{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;

                // Baris 2: NIP & Tipe (BOLD LABEL)
                $sheet->setCellValue("A{$row}", 'Nomor Induk Pegawai');
                $sheet->setCellValue("B{$row}", ': ' . ($this->user->id_karyawan ?? '-'));
                $sheet->setCellValue("D{$row}", 'Tipe Karyawan');
                $sheet->setCellValue("E{$row}", ': ' . ucfirst($this->user->employment_type));
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("D{$row}:E{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row += 2;

                // ═══════════════════════════════════════
                // 📌 TABEL PENGHASILAN & POTONGAN (ALL BORDERS KECUALI KOLOM C!)
                // ═══════════════════════════════════════

                // HEADER (BIRU)
                $sheet->setCellValue("A{$row}", 'PENGHASILAN');
                $sheet->setCellValue("D{$row}", 'POTONGAN');
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $blueHeader]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $blueHeader]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $row++;

                // Baris 1: Upah Harian | Potongan Telat
                $sheet->setCellValue("A{$row}", 'Upah Harian (Total)');
                $sheet->setCellValue("B{$row}", $gajiPokok);
                $sheet->setCellValue("D{$row}", 'Potongan Keterlambatan');
                $sheet->setCellValue("E{$row}", $potongan);
                $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("D{$row}:E{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;

                // Baris 2: Upah Lembur | Kosong
                $sheet->setCellValue("A{$row}", 'Upah Lembur (Total)');
                $sheet->setCellValue("B{$row}", $gajiLembur);
                $sheet->setCellValue("D{$row}", '');
                $sheet->setCellValue("E{$row}", '');
                $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("D{$row}:E{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;

                // Baris 3: Jumlah Hari Kerja | Jumlah Jam Lembur
                $sheet->setCellValue("A{$row}", 'Jumlah Hari Kerja');
                $sheet->setCellValue("B{$row}", ': ' . $jumlahHariKerja . ' Hari');
                $sheet->setCellValue("D{$row}", 'Jumlah Jam Lembur');
                $sheet->setCellValue("E{$row}", ': ' . $jumlahJamLembur . ' Jam');
                $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("D{$row}:E{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row += 2;

                // ═══════════════════════════════════════
                // 📌 TOTAL (ABU-ABU + ALL BORDERS KECUALI KOLOM C!)
                // ═══════════════════════════════════════
                $sheet->setCellValue("A{$row}", 'TOTAL PENGHASILAN');
                $sheet->setCellValue("B{$row}", $gajiPokok + $gajiLembur);
                $sheet->setCellValue("D{$row}", 'TOTAL POTONGAN');
                $sheet->setCellValue("E{$row}", $potongan);
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $greyTotal]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $greyTotal]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $row += 2;

                // ═══════════════════════════════════════
                // 📌 GAJI BERSIH (HIJAU + ALL BORDERS!)
                // ═══════════════════════════════════════
                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->setCellValue("A{$row}", 'PENGHASILAN BERSIH (TAKE HOME PAY)');
                $sheet->setCellValue("E{$row}", $gajiBersih);
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $greenBersih]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $row++;

                // ═══════════════════════════════════════
                // 📌 TERBILANG (KUNING + ALL BORDERS!)
                // ═══════════════════════════════════════
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", 'Terbilang: ' . ucwords($this->terbilangString));
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $yellowTerbilang]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $row += 3;

                // ═══════════════════════════════════════
                // 📌 FOOTER (NO BORDER!)
                // ═══════════════════════════════════════
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", '"Keep Up The Good Work"');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['italic' => true, 'bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $row += 3;

                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", 'HRGA Division');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $row++;

                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", 'PT. ANSEL MUDA BERKARYA');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ═══════════════════════════════════════
                // 💰 FORMAT RUPIAH
                // ═══════════════════════════════════════
                $rupiahFormat = '"Rp "#,##0';
                $sheet->getStyle('B1:B100')->getNumberFormat()->setFormatCode($rupiahFormat);
                $sheet->getStyle('E1:E100')->getNumberFormat()->setFormatCode($rupiahFormat);

                // ═══════════════════════════════════════
                // 📄 SET PAGE SETUP BIAR PAS DI 100%!
                // ═══════════════════════════════════════
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
                $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToPage(true);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                // Set margin biar lebih pas
                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setBottom(0.5);
                $sheet->getPageMargins()->setLeft(0.5);
                $sheet->getPageMargins()->setRight(0.5);
            },
        ];
    }
}
