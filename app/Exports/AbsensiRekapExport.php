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

class AbsensiRekapExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $recapData;
    protected $month;
    protected $year;
    protected $type;
    protected $range;
    protected $week;

    public function __construct($recapData, $month, $year, $type = 'all', $range = 'monthly', $week = null)
    {
        $this->recapData = $recapData;
        $this->month = $month;
        $this->year = $year;
        $this->type = $type;
        $this->range = $range;
        $this->week = $week;
    }

    public function collection()
    {
        $data = collect();
        $no = 1;

        foreach ($this->recapData as $recap) {
            $kategori = $recap['kategori'] ?? 'organik';

            if ($this->type !== 'all' && $kategori !== $this->type) {
                continue;
            }

            // ✅ RUMUS 4 KOLOM SAKTI: Kolom 1 + Kolom 2 - Kolom 3 = Kolom 4
            $data->push([
                'no' => $no++,
                'nama' => $recap['user']->name,
                'id_karyawan' => $recap['user']->id_karyawan,
                'departemen' => $recap['user']->departemen,
                'tipe_karyawan' => ucfirst($kategori),
                'hadir' => $recap['total_hadir'],
                'izin' => $recap['total_izin'],
                'sakit' => $recap['total_sakit'],
                'lembur' => $recap['total_lembur'],
                'telat' => $recap['total_telat'],
                'gaji_pokok' => 'Rp ' . number_format($recap['total_gaji_pokok'] ?? 0, 0, ',', '.'), // Kolom 1
                'gaji_lembur' => 'Rp ' . number_format($recap['total_gaji_lembur'] ?? 0, 0, ',', '.'), // Kolom 2
                'total_potongan' => 'Rp ' . number_format($recap['total_potongan'] ?? 0, 0, ',', '.'), // Kolom 3
                'keterangan' => $recap['keterangan_adj'] ?: '-',
                'total_diterima' => 'Rp ' . number_format($recap['total_gaji'] ?? 0, 0, ',', '.'), // Kolom 4 (Pasti Balance)
                'total_absensi' => $recap['total_absensi'] ?? 0,
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Karyawan',
            'ID Karyawan',
            'Departemen',
            'Kategori',
            'Hadir',
            'Izin',
            'Sakit',
            'Lembur',
            'Telat (x)',
            'Gaji Pokok (Inc. Bonus)',
            'Gaji Lembur',
            'Total Potongan',
            'Keterangan (Rincian)',
            'TOTAL DITERIMA (PAS)',
            'Total Absensi',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $monthName = Carbon::createFromFormat('!m', $this->month)->translatedFormat('F');
        $typeLabel = match($this->type) {
            'organik' => 'Karyawan Organik',
            'freelance' => 'Karyawan Freelance',
            'borongan' => 'Karyawan Borongan',
            'magang' => 'Karyawan Magang',
            default => 'Semua Karyawan'
        };

        $periode = $this->range === 'weekly' && $this->week
            ? "Minggu ke-{$this->week}, {$monthName} {$this->year}"
            : "{$monthName} {$this->year}";

        $sheet->insertNewRowBefore(1, 2);
        $sheet->setCellValue('A1', "REKAP ABSENSI - {$typeLabel}");
        $sheet->setCellValue('A2', "Periode: {$periode}");

        $sheet->mergeCells('A1:P1');
        $sheet->mergeCells('A2:P2');

        $sheet->getStyle('A1:P2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A3:P3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A4:P{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle("F4:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("P4:P{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Highlight TOTAL DITERIMA biar gampang liatnya
        $sheet->getStyle("O4:O{$lastRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
        ]);

        $sheet->getStyle("K4:O{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // No
            'B' => 25,  // Nama
            'C' => 15,  // ID
            'D' => 20,  // Dept
            'E' => 15,  // Kategori
            'F' => 8,   // Hadir
            'G' => 8,   // Izin
            'H' => 8,   // Sakit
            'I' => 10,  // Lembur
            'J' => 10,  // Telat
            'K' => 22,  // Gaji Pokok
            'L' => 18,  // Gaji Lembur
            'M' => 18,  // Total Potongan
            'N' => 45,  // Keterangan
            'O' => 22,  // Total Diterima
            'P' => 15,  // Total Absensi
        ];
    }

    public function title(): string
    {
        $monthName = Carbon::createFromFormat('!m', $this->month)->format('M');
        $suffix = $this->range === 'weekly' && $this->week ? "W{$this->week}" : $monthName;
        return "Rekap_{$suffix}_{$this->year}";
    }
}
