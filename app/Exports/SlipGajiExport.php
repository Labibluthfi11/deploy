<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Nasution\Terbilang\Terbilang; // ⬅️ MANGGIL DUKUN KITA
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Ambil data
                $gajiPokok = $this->stats['total_gaji_pokok'] ?? 0;
                $gajiLembur = $this->stats['total_gaji_lembur'] ?? 0;
                $potongan = $this->stats['total_potongan'] ?? 0;
                $gajiBersih = $this->stats['total_gaji_bersih'] ?? 0;

                // --- HEADER ---
                $sheet->mergeCells('A1:F1');
                $sheet->setCellValue('A1', 'PT. ANSEL MUDA BERKARYA');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A2:F2');
                $sheet->setCellValue('A2', 'SLIP GAJI KARYAWAN');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getRowDimension(2)->setRowHeight(30);


                // --- DATA KARYAWAN ---
                $sheet->setCellValue('A4', 'DATA KARYAWAN');
                $sheet->getStyle('A4')->getFont()->setBold(true);

                $sheet->setCellValue('A5', 'Nama Karyawan');
                $sheet->setCellValue('B5', ': ' . $this->user->name);

                $sheet->setCellValue('A6', 'Nomor Induk Pegawai');
                $sheet->setCellValue('B6', ': ' . $this->user->id_karyawan); // ⬅️ Pake ID Karyawan

                $sheet->setCellValue('D5', 'Periode Penggajian');
                $sheet->setCellValue('E5', ': ' . $this->periode); // ⬅️ Pake Periode

                $sheet->setCellValue('D6', 'Tipe Karyawan');
                $sheet->setCellValue('E6', ': ' . ucfirst($this->user->employment_type));

                // Style Data Karyawan
                $sheet->getStyle('A5:A6')->getFont()->setBold(true);
                $sheet->getStyle('D5:D6')->getFont()->setBold(true);


                // --- PENGHASILAN ---
                $sheet->setCellValue('A8', 'PENGHASILAN');
                $sheet->getStyle('A8')->getFont()->setBold(true);

                $sheet->setCellValue('A9', 'Upah Harian (Total)');
                $sheet->setCellValue('B9', $gajiPokok);

                $sheet->setCellValue('A10', 'Upah Lembur (Total)');
                $sheet->setCellValue('B10', $gajiLembur);

                // --- POTONGAN ---
                $sheet->setCellValue('D8', 'POTONGAN');
                $sheet->getStyle('D8')->getFont()->setBold(true);

                $sheet->setCellValue('D9', 'Potongan Keterlambatan');
                $sheet->setCellValue('E9', $potongan);


                // --- TOTAL ---
                $sheet->mergeCells('A12:B12');
                $sheet->setCellValue('A12', 'TOTAL PENGHASILAN');
                $sheet->setCellValue('C12', $gajiPokok + $gajiLembur);

                $sheet->mergeCells('D12:E12');
                $sheet->setCellValue('D12', 'TOTAL POTONGAN');
                $sheet->setCellValue('F12', $potongan);

                $sheet->getStyle('A12:F12')->getFont()->setBold(true);
                $sheet->getStyle('A12:F12')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9'); // Abu-abu


                // --- PENGHASILAN BERSIH ---
                $sheet->mergeCells('A14:E14');
                $sheet->setCellValue('A14', 'PENGHASILAN BERSIH (TAKE HOME PAY)');
                $sheet->setCellValue('F14', $gajiBersih);
                $sheet->getStyle('A14:F14')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A14:F14')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFC6E0B4'); // Hijau muda


                // --- TERBILANG (PAKE SIHIR) ---
                $terbilang = Terbilang::make($gajiBersih, ' rupiah'); // ⬅️ MANTRA-NYA

                $sheet->mergeCells('A16:F16');
                $sheet->setCellValue('A16', 'Terbilang: ' . ucwords($terbilang));
                $sheet->getStyle('A16')->getFont()->setBold(true)->setItalic(true);
                $sheet->getStyle('A16')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // --- FORMAT RUPIAH ---
                $sheet->getStyle('B9:B10')->getNumberFormat()->setFormatCode('"Rp" #,##0');
                $sheet->getStyle('E9')->getNumberFormat()->setFormatCode('"Rp" #,##0');
                $sheet->getStyle('C12')->getNumberFormat()->setFormatCode('"Rp" #,##0');
                $sheet->getStyle('F12')->getNumberFormat()->setFormatCode('"Rp" #,##0');
                $sheet->getStyle('F14')->getNumberFormat()->setFormatCode('"Rp" #,##0');

                // --- BORDER ---
                $styleArray = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];
                $sheet->getStyle('A8:B10')->applyFromArray($styleArray); // Penghasilan
                $sheet->getStyle('D8:E9')->applyFromArray($styleArray); // Potongan
            },
        ];
    }
}
