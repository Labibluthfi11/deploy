<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class AbsensiUserExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $absensi;
    protected $user;
    protected $filterType;
    protected $month;
    protected $year;
    protected $week;

    public function __construct($absensi, $user, $filterType = 'all', $month = null, $year = null, $week = null)
    {
        $this->absensi = $absensi;
        $this->user = $user;
        $this->filterType = $filterType;
        $this->month = $month;
        $this->year = $year;
        $this->week = $week;
    }

    public function collection()
    {
        $data = collect();
        $no = 1;

        foreach ($this->absensi as $item) {
            $data->push([
                'no' => $no++,
                'tanggal' => Carbon::parse($item->check_in_at)->format('d M Y'),
                'check_in' => Carbon::parse($item->check_in_at)->format('H:i'),
                'check_out' => $item->check_out_at ? Carbon::parse($item->check_out_at)->format('H:i') : '-',
                'status' => ucfirst($item->status ?? '-'),
                'tipe' => ucfirst($item->tipe ?? '-'),
                'telat' => ($item->late_minutes ?? 0) . ' Menit',
                'menit_lembur' => ($item->overtime_minutes ?? 0) . ' Menit',
                'gaji_lembur' => 'Rp ' . number_format($item->overtime_pay ?? 0, 0, ',', '.'),
                'gaji_pokok' => 'Rp ' . number_format($item->base_salary ?? 0, 0, ',', '.'),
                'potongan' => 'Rp ' . number_format($item->late_penalty ?? 0, 0, ',', '.'),
                'gaji_bersih' => 'Rp ' . number_format($item->final_salary ?? 0, 0, ',', '.'),
                'approval' => ucfirst($item->status_approval ?? '-'),
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Check-in',
            'Check-out',
            'Status',
            'Tipe',
            'Telat',
            'Menit Lembur',
            'Gaji Lembur',
            'Gaji Pokok',
            'Potongan',
            'Gaji Bersih',
            'Approval',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // 1. TAMBAH 3 BARIS BARU di atas, bukan 2
        $sheet->insertNewRowBefore(1, 3);

        // 2. Siapin data periode
        Carbon::setLocale('id'); // Biar jadi "November" bukan "November"

        // Ambil tgl awal & akhir DARI DATA yang dikirim
        $firstDate = $this->absensi->isNotEmpty() ? Carbon::parse($this->absensi->min('check_in_at'))->format('d M Y') : null;
        $lastDate = $this->absensi->isNotEmpty() ? Carbon::parse($this->absensi->max('check_in_at'))->format('d M Y') : null;
        $dateRange = "";

        if ($firstDate && $lastDate) {
            // Kalo data ada, bikin string Tgl Awal s/d Tgl Akhir
            $dateRange = "($firstDate s/d $lastDate)";
        }

        // 3. Bikin string periode utamanya
        $periode = match ($this->filterType) {
            'monthly' => "Bulan " . Carbon::createFromFormat('!m', $this->month)->translatedFormat('F') . " {$this->year}",
            'weekly' => "Minggu ke-{$this->week}, " . Carbon::createFromFormat('!m', $this->month)->translatedFormat('F') . " {$this->year}",
            'yearly' => "Tahun {$this->year}",
            default => "Semua Data",
        };

        // 4. Set sel-sel baru (SESUAI REQUEST LO)
        $sheet->setCellValue('A1', "Rekap Absensi Karyawan");
        $sheet->setCellValue('A2', "Nama: {$this->user->name}  (ID: {$this->user->id_karyawan})"); // <-- ID KARYAWAN DI SINI
        $sheet->setCellValue('A3', "Periode: {$periode} {$dateRange}"); // <-- RANGE TANGGAL DI SINI

        // 5. Merge sel-sel header baru
        $sheet->mergeCells('A1:M1');
        $sheet->mergeCells('A2:M2');
        $sheet->mergeCells('A3:M3');

        // 6. Style header baru (A1 sampai A3)
        $sheet->getStyle('A1:M3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        // Bikin A2 & A3 lebih kecil
        $sheet->getStyle('A2:M3')->applyFromArray([
            'font' => ['bold' => false, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // 7. Geser style header tabel (yang biru) ke BARIS 4
        $sheet->getStyle('A4:M4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // 8. Geser style data tabel (border) mulai dari BARIS 5
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A5:M{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 15,
            'C' => 10,
            'D' => 10,
            'E' => 12,
            'F' => 12,
            'G' => 15,
            'H' => 15,
            'I' => 18,
            'J' => 18,
            'K' => 15,
            'L' => 18,
            'M' => 12,
        ];
    }

    public function title(): string
    {
        return "Absensi_{$this->user->name}";
    }
}
