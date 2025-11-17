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
            // 🔥 FILTER BERDASARKAN KATEGORI YANG DIPILIH 🔥
            $kategori = $recap['kategori'] ?? 'organik';

            if ($this->type !== 'all' && $kategori !== $this->type) {
                continue;
            }

            $data->push([
                'no' => $no++,
                'nama' => $recap['user']->name,
                'id_karyawan' => $recap['user']->id_karyawan,
                'departemen' => $recap['user']->departemen,
                'tipe_karyawan' => ucfirst($kategori), // 🔥 GANTI DARI employment_type JADI kategori
                'hadir' => $recap['total_hadir'],
                'izin' => $recap['total_izin'],
                'sakit' => $recap['total_sakit'],
                'lembur' => $recap['total_lembur'],
                'telat' => $recap['total_telat'],
                'total_potongan' => 'Rp ' . number_format($recap['total_potongan'] ?? 0, 0, ',', '.'),
                'total_menit_lembur' => ($recap['total_menit_lembur'] ?? 0) . ' Menit',
                'total_gaji_lembur' => 'Rp ' . number_format($recap['total_gaji_lembur'] ?? 0, 0, ',', '.'),
                'total_gaji' => 'Rp ' . number_format($recap['total_gaji'] ?? 0, 0, ',', '.'),
                'total' => $recap['total_absensi'] ?? 0,
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
            'Kategori', // 🔥 GANTI DARI "Tipe Karyawan" JADI "Kategori"
            'Hadir',
            'Izin',
            'Sakit',
            'Lembur',
            'Telat (x)',
            'Total Potongan',
            'Total Menit Lembur',
            'Total Gaji Lembur',
            'Total Gaji',
            'Total Absensi',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $monthName = Carbon::createFromFormat('!m', $this->month)->translatedFormat('F');

        // 🔥 LABEL KATEGORI UNTUK HEADER EXCEL 🔥
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

        // Header judul
        $sheet->insertNewRowBefore(1, 2);
        $sheet->setCellValue('A1', "REKAP ABSENSI - {$typeLabel}");
        $sheet->setCellValue('A2', "Periode: {$periode}");

        // 🔥 SEKARANG PUNYA 15 KOLOM (A-O) 🔥
        $sheet->mergeCells('A1:O1');
        $sheet->mergeCells('A2:O2');

        $sheet->getStyle('A1:O2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Style header
        $sheet->getStyle('A3:O3')->applyFromArray([
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

        // Style isi data
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A4:O{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Style kolom angka (F-J dan O) CENTER
        $sheet->getStyle("F4:J{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("O4:O{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Style kolom gaji (K-N) ALIGN LEFT
        $sheet->getStyle("K4:N{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,  // No
            'B' => 25, // Nama
            'C' => 15, // ID
            'D' => 20, // Dept
            'E' => 15, // Kategori
            'F' => 10, // Hadir
            'G' => 10, // Izin
            'H' => 10, // Sakit
            'I' => 10, // Lembur
            'J' => 10, // Telat
            'K' => 18, // Total Potongan
            'L' => 18, // Total Menit Lembur
            'M' => 20, // Total Gaji Lembur
            'N' => 20, // Total Gaji
            'O' => 15, // Total Absensi
        ];
    }

    public function title(): string
    {
        $monthName = Carbon::createFromFormat('!m', $this->month)->format('M');
        $suffix = $this->range === 'weekly' && $this->week ? "W{$this->week}" : $monthName;
        return "Rekap_{$suffix}_{$this->year}";
    }
}
