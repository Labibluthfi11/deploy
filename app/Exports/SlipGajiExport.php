<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup; // Penting buat Scale 100%

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

                // --- 1. CONFIG HALAMAN (SCALE 100% BIAR NGEPAS) ---
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);  // Paksa lebar 1 halaman
                $sheet->getPageSetup()->setFitToHeight(0); // Tinggi bebas (biar gak gepeng)

                // Margin (Biar rapi pas diprint)
                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setRight(0.5);
                $sheet->getPageMargins()->setLeft(0.5);
                $sheet->getPageMargins()->setBottom(0.5);

                // --- 2. SETUP LEBAR KOLOM (Biar Proporsional) ---
                $sheet->getColumnDimension('A')->setWidth(25);
                $sheet->getColumnDimension('B')->setWidth(22);
                $sheet->getColumnDimension('C')->setWidth(3);  // Pemisah Tengah
                $sheet->getColumnDimension('D')->setWidth(25);
                $sheet->getColumnDimension('E')->setWidth(22);

                // --- 3. VARIABEL WARNA ---
                $bgHeader   = 'BDD7EE'; // Biru Muda
                $bgTotal    = 'D9D9D9'; // Abu-abu
                $bgNet      = 'C6E0B4'; // Hijau
                $bgTerbilang= 'FFFF00'; // Kuning

                // Data Gaji
                $gajiPokok  = $this->stats['total_gaji_pokok'] ?? 0;
                $gajiLembur = $this->stats['total_gaji_lembur'] ?? 0;
                $potongan   = $this->stats['total_potongan'] ?? 0;
                $gajiBersih = $this->stats['total_gaji_bersih'] ?? 0;
                $totalMenitLembur = $this->stats['total_menit_lembur'] ?? 0;
                $jamLembur  = floor($totalMenitLembur / 60);
                $menitLembur = $totalMenitLembur % 60;
                $durasiLembur = $jamLembur . " Jam " . ($menitLembur > 0 ? $menitLembur . " Menit" : "");

                // ==========================================
                // HEADER PERUSAHAAN (NO BORDER)
                // ==========================================
                $row = 1;

                // Logo (Opsional, kalau ada)
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

                // Judul PT
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", 'PT. ANSEL MUDA BERKARYA');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgHeader]]
                ]);
                $row++;

                // Judul Slip
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", 'SLIP GAJI KARYAWAN');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgHeader]]
                ]);

                // Jarak dikit
                $row += 2;

                // ==========================================
                // DATA KARYAWAN (ALL BORDERS)
                // ==========================================
                $startDataRow = $row;

                // Header "DATA KARYAWAN"
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", "DATA KARYAWAN");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;

                // Isi Data Karyawan
                $sheet->setCellValue("A{$row}", "Nama Karyawan");
                $sheet->setCellValue("B{$row}", ": " . $this->user->name);
                $sheet->setCellValue("D{$row}", "Periode");
                $sheet->setCellValue("E{$row}", ": " . $this->periode);
                $row++;

                $sheet->setCellValue("A{$row}", "NIP");
                $sheet->setCellValue("B{$row}", ": " . ($this->user->id_karyawan ?? '-'));
                $sheet->setCellValue("D{$row}", "Tipe");
                $sheet->setCellValue("E{$row}", ": " . ucfirst($this->user->employment_type));

                // 🔥 Terapkan Border untuk BLOK DATA KARYAWAN (Biar nyambung & rapi)
                $styleBorderThin = [
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ];
                // Border Kiri (A-B)
                $sheet->getStyle("A{$startDataRow}:B{$row}")->applyFromArray($styleBorderThin);
                // Border Kanan (D-E)
                $sheet->getStyle("D{$startDataRow}:E{$row}")->applyFromArray($styleBorderThin);

                $row += 2; // Spasi

                // ==========================================
                // TABEL RINCIAN (ALL BORDERS)
                // ==========================================
                $tableStartRow = $row;

                // Header Tabel
                $sheet->setCellValue("A{$row}", "PENGHASILAN");
                $sheet->setCellValue("D{$row}", "POTONGAN");
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgHeader]]
                ]);
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgHeader]]
                ]);
                $row++;

                // Isi Tabel - Baris 1
                $sheet->setCellValue("A{$row}", "Upah Harian (Total)");
                $sheet->setCellValue("B{$row}", $gajiPokok);
                $sheet->setCellValue("D{$row}", "Potongan Telat");
                $sheet->setCellValue("E{$row}", $potongan);
                $row++;

                // Isi Tabel - Baris 2
                $sheet->setCellValue("A{$row}", "Upah Lembur");
                $sheet->setCellValue("B{$row}", $gajiLembur);
                $sheet->setCellValue("D{$row}", ""); // Kosong
                $sheet->setCellValue("E{$row}", ""); // Kosong
                $row++;

                // Isi Tabel - Baris 3 (Info Tambahan)
                $sheet->setCellValue("A{$row}", "Durasi Lembur");
                $sheet->setCellValue("B{$row}", ": " . $durasiLembur);
                $sheet->setCellValue("D{$row}", "");
                $sheet->setCellValue("E{$row}", "");
                $row++;

                // 🔥 Terapkan Border untuk BLOK RINCIAN (Sekaligus biar gak putus)
                $endTableRow = $row - 1;
                $sheet->getStyle("A{$tableStartRow}:B{$endTableRow}")->applyFromArray($styleBorderThin);
                $sheet->getStyle("D{$tableStartRow}:E{$endTableRow}")->applyFromArray($styleBorderThin);

                // ==========================================
                // TOTAL & BERSIH (ALL BORDERS + WARNA)
                // ==========================================

                // Total Penghasilan & Potongan (Abu-abu)
                $sheet->setCellValue("A{$row}", "TOTAL PENGHASILAN");
                $sheet->setCellValue("B{$row}", $gajiPokok + $gajiLembur);
                $sheet->setCellValue("D{$row}", "TOTAL POTONGAN");
                $sheet->setCellValue("E{$row}", $potongan);

                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgTotal]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgTotal]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);
                $row += 2; // Spasi

                // Gaji Bersih (Hijau)
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

                // Terbilang (Kuning)
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", "Terbilang: " . ucwords($this->terbilangString));
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgTerbilang]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);

                $row += 3; // Jarak ke Footer

                // ==========================================
                // FOOTER QUOTE & TTD
                // ==========================================
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", '"Keep Up The Good Work"');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true, 'name' => 'Times New Roman', 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);

                $row += 2;
                $sheet->mergeCells("D{$row}:E{$row}");
                $sheet->setCellValue("D{$row}", "HRGA Division");
                $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $row++;
                $sheet->mergeCells("D{$row}:E{$row}");
                $sheet->setCellValue("D{$row}", "PT. ANSEL MUDA BERKARYA");
                $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


                // ==========================================
                // FORMAT CURRENCY (RP)
                // ==========================================
                $formatRupiah = '"Rp "#,##0';
                // Cari semua cell yang butuh format rupiah secara spesifik biar aman
                $sheet->getStyle('B1:B100')->getNumberFormat()->setFormatCode($formatRupiah);
                $sheet->getStyle('E1:E100')->getNumberFormat()->setFormatCode($formatRupiah);

            },
        ];
    }
}
