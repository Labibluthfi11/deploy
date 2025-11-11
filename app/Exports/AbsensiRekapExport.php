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
            if ($this->type !== 'all' && $recap['user']->employment_type !== $this->type) {
                continue;
            }

            $data->push([
                'no' => $no++,
                'nama' => $recap['user']->name,
                'id_karyawan' => $recap['user']->id_karyawan,
                'departemen' => $recap['user']->departemen,
                'tipe_karyawan' => ucfirst($recap['user']->employment_type),
                'hadir' => $recap['total_hadir'],
                'izin' => $recap['total_izin'],
                'sakit' => $recap['total_sakit'],
                'lembur' => $recap['total_lembur'],
                'telat' => $recap['total_telat'],
                // 🆕 DATA BARU KITA SISIPIN DI SINI
                'total_menit_lembur' => ($recap['total_menit_lembur'] ?? 0) . ' Menit',
                'total_gaji_lembur' => 'Rp ' . number_format($recap['total_gaji_lembur'] ?? 0, 0, ',', '.'),
                // ---------------------------------
                'total_gaji' => 'Rp ' . number_format($recap['total_gaji'] ?? 0, 0, ',', '.'),
                'total' => $recap['total_absensi'],
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
            'Tipe Karyawan',
            'Hadir',
            'Izin',
            'Sakit',
            'Lembur',
            'Telat (x)',
            // 🆕 HEADER BARU KITA SISIPIN DI SINI
            'Total Menit Lembur',
            'Total Gaji Lembur',
            // ---------------------------------
            'Total Gaji',
            'Total Absensi',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $monthName = Carbon::createFromFormat('!m', $this->month)->translatedFormat('F');
        $typeLabel = match($this->type) {
            'organik' => 'Karyawan Organik',
            'freelance' => 'Karyawan Freelance',
            default => 'Semua Karyawan'
        };

        $periode = $this->range === 'weekly' && $this->week
            ? "Minggu ke-{$this->week}, {$monthName} {$this->year}"
            : "{$monthName} {$this->year}";

        // Header judul
        $sheet->insertNewRowBefore(1, 2);
        $sheet->setCellValue('A1', "REKAP ABSENSI - {$typeLabel}");
        $sheet->setCellValue('A2', "Periode: {$periode}");

        // 🆕 KITA SEKARANG PUNYA 14 KOLOM (A-N), BUKAN 12 (A-L)
        $sheet->mergeCells('A1:N1'); // 🆕 UBAH DARI L1 JADI N1
        $sheet->mergeCells('A2:N2'); // 🆕 UBAH DARI L2 JADI N2

        $sheet->getStyle('A1:N2')->applyFromArray([ // 🆕 UBAH DARI L2 JADI N2
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Style header
        $sheet->getStyle('A3:N3')->applyFromArray([ // 🆕 UBAH DARI L3 JADI N3
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
        $sheet->getStyle("A4:N{$lastRow}")->applyFromArray([ // 🆕 UBAH DARI L JADI N
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // 🆕 STYLE UNTUK KOLOM ANGKA (F-K dan N) KITA BUAT CENTER
        $sheet->getStyle("F4:K{$lastRow}") // 🆕 Kolom F (Hadir) s/d K (Total Menit Lembur)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("N4:N{$lastRow}") // 🆕 Kolom N (Total Absensi)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 🆕 STYLE KHUSUS UNTUK KOLOM GAJI (L & M) (ALIGN LEFT BIAR RUPIAH RAPI)
        $sheet->getStyle("L4:M{$lastRow}") // 🆕 Kolom L (Gaji Lembur) & M (Total Gaji)
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
            'E' => 15, // Tipe
            'F' => 10, // Hadir
            'G' => 10, // Izin
            'H' => 10, // Sakit
            'I' => 10, // Lembur
            'J' => 10, // Telat
            // 🆕 KOLOM BARU DAN GESER KOLOM LAMA
            'K' => 18, // Total Menit Lembur (BARU)
            'L' => 20, // Total Gaji Lembur (BARU)
            'M' => 20, // Total Gaji (Geser dari K)
            'N' => 15, // Total Absensi (Geser dari L)
        ];
    }

    public function title(): string
    {
        $monthName = Carbon::createFromFormat('!m', $this->month)->format('M');
        $suffix = $this->range === 'weekly' && $this.week ? "W{$this->week}" : $monthName;
        return "Rekap_{$suffix}_{$this->year}";
    }
}
