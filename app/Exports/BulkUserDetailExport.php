<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Absensi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class BulkDetailExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $userIds;
    protected $startDate;
    protected $endDate;

    public function __construct($userIds, $startDate, $endDate)
    {
        $this->userIds = $userIds;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $data = collect();
        $users = User::whereIn('id', $this->userIds)->orderBy('name')->get();

        // Periode String
        $periodeStr = Carbon::parse($this->startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($this->endDate)->translatedFormat('d M Y');

        foreach ($users as $index => $user) {
            // 1. KASIH JARAK & HEADER PER ORANG (Kecuali orang pertama)
            if ($index > 0) {
                $data->push(['', '', '', '', '', '', '', '', '', '', '', '', '', '']); // Baris Kosong
                $data->push(['', '', '', '', '', '', '', '', '', '', '', '', '', '']); // Baris Kosong lagi biar lega
            }

            // 2. HEADER INFORMASI KARYAWAN (Manual inject ke baris)
            $data->push(['REKAP ABSENSI KARYAWAN', '', '', '', '', '', '', '', '', '', '', '', '', '']);
            $data->push(['Nama: ' . $user->name . ' (ID: ' . $user->id_karyawan . ')', '', '', '', '', '', '', '', '', '', '', '', '', '']);
            $data->push(['Periode: ' . $periodeStr, '', '', '', '', '', '', '', '', '', '', '', '', '']);

            // 3. HEADER TABEL (Manual inject biar muncul tiap orang)
            $data->push([
                'No', 'Tanggal', 'Check-in', 'Check-out', 'Status', 'Tipe',
                'Telat', 'Menit Lembur', 'Gaji Lembur', 'Gaji Pokok',
                'Potongan', 'Gaji Bersih', 'TOTAL GAJI', 'Approval'
            ]);

            // 4. DATA ABSENSI
            $absensi = Absensi::where('user_id', $user->id)
                ->whereBetween('check_in_at', [
                    Carbon::parse($this->startDate)->startOfDay(),
                    Carbon::parse($this->endDate)->endOfDay()
                ])
                ->where('status_approval', 'approved')
                ->orderBy('check_in_at', 'asc')
                ->get();

            $no = 1;
            $totalGajiAll = 0; // Buat hitung grand total si user

            if ($absensi->isEmpty()) {
                $data->push(['Tidak ada data absensi untuk periode ini.', '', '', '', '', '', '', '', '', '', '', '', '', '']);
            } else {
                foreach ($absensi as $item) {
                    // Hitung Total Gaji (Gaji Bersih + Lembur) - Potongan (biasanya udah include di bersih sih, tapi jaga2)
                    // Asumsi: final_salary udah bersih (pokok + lembur - potongan).
                    // Kalo lo mau hitung manual: ($item->base_salary + $item->overtime_pay) - $item->late_penalty

                    // Kita pake final_salary aja karena itu udah "Gaji Bersih"
                    $totalRow = $item->final_salary;
                    $totalGajiAll += $totalRow;

                    $data->push([
                        $no++,
                        Carbon::parse($item->check_in_at)->translatedFormat('d M Y'),
                        Carbon::parse($item->check_in_at)->format('H:i'),
                        $item->check_out_at ? Carbon::parse($item->check_out_at)->format('H:i') : '-',
                        ucfirst($item->status ?? '-'),
                        ucfirst($item->tipe ?? '-'),
                        ($item->late_minutes ?? 0) . ' Menit',
                        ($item->overtime_minutes ?? 0) . ' Menit',
                        $item->overtime_pay, // Raw number biar bisa di-sum excel kalo mau
                        $item->base_salary,
                        $item->late_penalty,
                        $item->final_salary,
                        $totalRow, // 🆕 KOLOM TOTAL GAJI
                        ucfirst($item->status_approval ?? '-')
                    ]);
                }
                // Baris Total Bawah
                $data->push(['', '', '', '', '', '', '', '', '', '', '', 'TOTAL DITERIMA:', $totalGajiAll, '']);
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return []; // Kita kosongin karena headernya udah kita inject manual di loop
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $rows = $sheet->toArray();

        // Loop buat styling baris demi baris
        foreach ($rows as $index => $row) {
            $rowIndex = $index + 1;

            // Cek isi kolom pertama buat nentuin ini baris apa
            if ($row[0] === 'REKAP ABSENSI KARYAWAN') {
                // STYLE JUDUL UTAMA
                $sheet->mergeCells("A{$rowIndex}:N{$rowIndex}");
                $sheet->getStyle("A{$rowIndex}")->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle("A{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            } elseif (str_contains($row[0] ?? '', 'Nama:')) {
                // STYLE NAMA
                $sheet->mergeCells("A{$rowIndex}:N{$rowIndex}");
                $sheet->getStyle("A{$rowIndex}")->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("A{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            } elseif (str_contains($row[0] ?? '', 'Periode:')) {
                // STYLE PERIODE
                $sheet->mergeCells("A{$rowIndex}:N{$rowIndex}");
                $sheet->getStyle("A{$rowIndex}")->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("A{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            } elseif ($row[0] === 'No') {
                // STYLE HEADER TABEL (Biru)
                $sheet->getStyle("A{$rowIndex}:N{$rowIndex}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

            } elseif ($row[11] === 'TOTAL DITERIMA:') {
                // STYLE TOTAL BAWAH
                $sheet->getStyle("L{$rowIndex}:N{$rowIndex}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C6E0B4']], // Hijau Muda
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->getStyle("M{$rowIndex}")->getNumberFormat()->setFormatCode('"Rp" #,##0');

            } elseif (is_numeric($row[0])) {
                // STYLE DATA TABEL (Border biasa & Format Rupiah)
                $sheet->getStyle("A{$rowIndex}:N{$rowIndex}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Format Rupiah untuk kolom I, J, K, L, M
                $sheet->getStyle("I{$rowIndex}:M{$rowIndex}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            }
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,  // No
            'B' => 15, // Tanggal
            'C' => 10, // Checkin
            'D' => 10, // Checkout
            'E' => 10, // Status
            'F' => 10, // Tipe
            'G' => 12, // Telat
            'H' => 12, // Menit Lembur
            'I' => 15, // Gaji Lembur
            'J' => 15, // Gaji Pokok
            'K' => 15, // Potongan
            'L' => 15, // Gaji Bersih
            'M' => 18, // TOTAL GAJI
            'N' => 12, // Approval
        ];
    }
}
