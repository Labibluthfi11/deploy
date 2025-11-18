<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class SlipGajiExport implements WithEvents, ShouldAutoSize
{
    protected $user;
    protected $stats;
    protected $periode;

    public function __construct(User $user, array $stats, string $periode)
    {
        $this->user = $user;
        $this->stats = $stats;
        $this->periode = $periode;
    }

    /**
     * Fungsi Helper Terbilang Manual
     */
    private function terbilang($nilai) {
        $nilai = abs($nilai);
        $huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        $temp = "";
        if ($nilai < 12) {
            $temp = " ". $huruf[$nilai];
        } else if ($nilai <20) {
            $temp = $this->terbilang($nilai - 10). " belas";
        } else if ($nilai < 100) {
            $temp = $this->terbilang($nilai/10)." puluh". $this->terbilang($nilai % 10);
        } else if ($nilai < 200) {
            $temp = " seratus" . $this->terbilang($nilai - 100);
        } else if ($nilai < 1000) {
            $temp = $this->terbilang($nilai/100) . " ratus" . $this->terbilang($nilai % 100);
        } else if ($nilai < 2000) {
            $temp = " seribu" . $this->terbilang($nilai - 1000);
        } else if ($nilai < 1000000) {
            $temp = $this->terbilang($nilai/1000) . " ribu" . $this->terbilang($nilai % 1000);
        } else if ($nilai < 1000000000) {
            $temp = $this->terbilang($nilai/1000000) . " juta" . $this->terbilang($nilai % 1000000);
        }
        return $temp;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // --- SETUP STYLE ---
                $headerBlue = 'BDD7EE'; // Biru Muda Header
                $tableHeaderBlue = 'DEEBF7'; // Biru Sangat Muda untuk Header Tabel
                $summaryBlue = 'BDD7EE'; // Biru Summary
                $grayRow = 'F2F2F2'; // Abu-abu untuk baris selang-seling (opsional)

                // --- LEBAR KOLOM (Biar Mirip Contoh) ---
                $sheet->getColumnDimension('A')->setWidth(25);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(25); // Spasi Tengah
                $sheet->getColumnDimension('D')->setWidth(25);
                $sheet->getColumnDimension('E')->setWidth(20);

                // --- 1. HEADER PERUSAHAAN ---
                // Baris 1-4
                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', 'PT. ANSEL MUDA BERKARYA');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->mergeCells('A2:E2');
                $sheet->setCellValue('A2', 'JL. Wibawa Mukti II km. 4 Nomor 57 Rt. 06/rw 04 Kelurahan Jati Sari,');
                $sheet->getStyle('A2')->getFont()->setSize(10);

                $sheet->mergeCells('A3:E3');
                $sheet->setCellValue('A3', 'Kecamatan Jati Asih, Kota Bekasi');
                $sheet->getStyle('A3')->getFont()->setSize(10);

                // Garis Bawah Header
                $sheet->getStyle('A4:E4')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);


                // --- 2. DATA KARYAWAN (Kotak Abu/Biru) ---
                // Baris 6
                $sheet->setCellValue('A6', 'DATA KARYAWAN');
                $sheet->getStyle('A6')->getFont()->setBold(true);

                // Tabel Info Karyawan (Baris 7-9)
                $sheet->setCellValue('A7', 'Nomor Induk Pegawai');
                $sheet->setCellValue('B7', $this->user->id_karyawan ?? '-');

                $sheet->setCellValue('D7', 'Periode Penggajian');
                $sheet->setCellValue('E7', $this->periode);

                $sheet->setCellValue('A8', 'Nama Karyawan');
                $sheet->setCellValue('B8', strtoupper($this->user->name));

                $sheet->setCellValue('D8', 'Status Pegawai');
                $sheet->setCellValue('E8', ucfirst($this->user->employment_type));

                $sheet->setCellValue('A9', 'Jabatan');
                $sheet->setCellValue('B9', ucfirst($this->user->role ?? 'Staff')); // Asumsi ada kolom role

                $sheet->setCellValue('D9', 'Tanggal Cetak');
                $sheet->setCellValue('E9', date('d M Y'));

                // Styling Info Karyawan (Background Biru Tipis)
                $sheet->getStyle('A7:B9')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($tableHeaderBlue);
                $sheet->getStyle('D7:E9')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($tableHeaderBlue);


                // --- 3. RINCIAN GAJI (Dua Kolom Kiri Kanan) ---
                // Header Tabel (Baris 12)
                $sheet->setCellValue('A12', 'PENGHASILAN');
                $sheet->setCellValue('D12', 'POTONGAN');
                $sheet->getStyle('A12')->getFont()->setBold(true);
                $sheet->getStyle('D12')->getFont()->setBold(true);

                // Isi Tabel Penghasilan (Kiri)
                $row = 13;

                $sheet->setCellValue('A'.$row, 'Gaji Pokok (Harian)');
                $sheet->setCellValue('B'.$row, $this->stats['total_gaji_pokok']);
                $row++;

                $sheet->setCellValue('A'.$row, 'Lembur (Overtime)');
                $sheet->setCellValue('B'.$row, $this->stats['total_gaji_lembur']);
                $row++;

                // Isi Tabel Potongan (Kanan) - Mulai lagi dari baris 13
                $rowPot = 13;
                $sheet->setCellValue('D'.$rowPot, 'Potongan Keterlambatan');
                $sheet->setCellValue('E'.$rowPot, $this->stats['total_potongan']);
                $rowPot++;

                // ... Tambah potongan lain kalo ada ...


                // --- 4. TOTAL ---
                $totalRow = 18; // Kasih jarak dikit

                // Subtotal Penghasilan
                $sheet->setCellValue('A'.$totalRow, 'TOTAL PENGHASILAN');
                $sheet->setCellValue('B'.$totalRow, $this->stats['total_gaji_pokok'] + $this->stats['total_gaji_lembur']);
                $sheet->getStyle('A'.$totalRow.':B'.$totalRow)->getFont()->setBold(true);
                $sheet->getStyle('A'.$totalRow.':B'.$totalRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($summaryBlue);

                // Subtotal Potongan
                $sheet->setCellValue('D'.$totalRow, 'TOTAL POTONGAN');
                $sheet->setCellValue('E'.$totalRow, $this->stats['total_potongan']);
                $sheet->getStyle('D'.$totalRow.':E'.$totalRow)->getFont()->setBold(true);
                $sheet->getStyle('D'.$totalRow.':E'.$totalRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($summaryBlue);

                // --- 5. GAJI BERSIH & TERBILANG ---
                $netRow = 20;
                $sheet->mergeCells('A'.$netRow.':D'.$netRow);
                $sheet->setCellValue('A'.$netRow, 'PENGHASILAN BERSIH (TAKE HOME PAY)');
                $sheet->setCellValue('E'.$netRow, $this->stats['total_gaji_bersih']);

                // Style Gaji Bersih (Mirip contoh)
                $sheet->getStyle('A'.$netRow.':E'.$netRow)->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A'.$netRow.':E'.$netRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($headerBlue);
                $sheet->getStyle('A'.$netRow.':E'.$netRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Terbilang
                $terbilangRow = 21;
                $textTerbilang = trim($this->terbilang($this->stats['total_gaji_bersih'])) . ' Rupiah';
                $sheet->mergeCells('A'.$terbilangRow.':E'.$terbilangRow);
                $sheet->setCellValue('A'.$terbilangRow, 'Terbilang: # ' . ucwords($textTerbilang) . ' #');
                $sheet->getStyle('A'.$terbilangRow)->getFont()->setItalic(true);
                $sheet->getStyle('A'.$terbilangRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);


                // --- 6. FOOTER & TTD ---
                $footerRow = 25;
                $sheet->mergeCells('A'.$footerRow.':E'.$footerRow);
                $sheet->setCellValue('A'.$footerRow, '“Keep Up The Good Work”');
                $sheet->getStyle('A'.$footerRow)->getFont()->setItalic(true)->setBold(true)->setName('Times New Roman');
                $sheet->getStyle('A'.$footerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $ttdRow = 27;
                $sheet->mergeCells('C'.$ttdRow.':E'.$ttdRow);
                $sheet->setCellValue('C'.$ttdRow, 'Bekasi, ' . date('d F Y'));
                $sheet->getStyle('C'.$ttdRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $rowHR = $ttdRow + 2;
                $sheet->mergeCells('C'.$rowHR.':E'.$rowHR);
                $sheet->setCellValue('C'.$rowHR, 'HRGA Division');
                $sheet->getStyle('C'.$rowHR)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setBold(true);

                $rowPT = $rowHR + 1;
                $sheet->mergeCells('C'.$rowPT.':E'.$rowPT);
                $sheet->setCellValue('C'.$rowPT, 'PT. ANSEL MUDA BERKARYA');
                $sheet->getStyle('C'.$rowPT)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setBold(true);


                // --- FORMATTING GENERAL ---
                // Format Rupiah
                $currencyFormat = '"Rp" #,##0';
                $sheet->getStyle('B13:B15')->getNumberFormat()->setFormatCode($currencyFormat); // Penghasilan
                $sheet->getStyle('E13:E15')->getNumberFormat()->setFormatCode($currencyFormat); // Potongan
                $sheet->getStyle('B'.$totalRow)->getNumberFormat()->setFormatCode($currencyFormat); // Total Penghasilan
                $sheet->getStyle('E'.$totalRow)->getNumberFormat()->setFormatCode($currencyFormat); // Total Potongan
                $sheet->getStyle('E'.$netRow)->getNumberFormat()->setFormatCode($currencyFormat);   // Gaji Bersih

                // Borders untuk Tabel Penghasilan & Potongan
                $styleBorder = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];
                $sheet->getStyle('A12:B'.$totalRow)->applyFromArray($styleBorder);
                $sheet->getStyle('D12:E'.$totalRow)->applyFromArray($styleBorder);

            },
        ];
    }
}
