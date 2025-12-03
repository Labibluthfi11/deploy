{{-- Ini adalah file HTML yang bakal jadi Excel --}}
<table>
    {{-- Loop per Karyawan --}}
    @foreach($users as $user)
        @php
            // Ambil data absensi si user ini
            $userAbsensi = $absensiData->where('user_id', $user->id);

            // 🔥 CEK ADA GA TANGGAL YANG KOSONG
            $hasMissingDate = false;
            foreach ($allDates as $date) {
                $absensiOnDate = $userAbsensi->first(function($item) use ($date) {
                    return \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                });

                if (!$absensiOnDate) {
                    $hasMissingDate = true;
                    break;
                }
            }

            // Tentuin warna nama user
            $userNameColor = $hasMissingDate ? '#FF0000' : '#000000';
            $userNameWeight = $hasMissingDate ? 'bold' : 'normal';
        @endphp

        {{-- Header Karyawan --}}
        <thead>
            <tr>
                <td colspan="14" style="font-weight: bold; font-size: 16px; text-align: center; height: 30px; vertical-align: middle; background-color: #BDD7EE;">
                    REKAP ABSENSI KARYAWAN
                </td>
            </tr>
            <tr>
                <td colspan="7" style="font-weight: {{ $userNameWeight }}; text-align: left; color: {{ $userNameColor }};">Nama: {{ $user->name }}</td>
                <td colspan="7" style="font-weight: bold; text-align: left;">ID: {{ $user->id_karyawan }}</td>
            </tr>
            <tr>
                <td colspan="14" style="font-weight: bold; text-align: left;">Periode: {{ $periodeStr }}</td>
            </tr>
            <tr>
                <td colspan="14"></td> {{-- Spasi --}}
            </tr>

            {{-- Header Tabel --}}
            <tr style="background-color: #4F46E5; color: #FFFFFF;">
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 50px;">No</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">Tanggal</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 80px;">Check-in</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 80px;">Check-out</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 80px;">Status</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 80px;">Tipe</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Telat</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Menit Lembur</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">Gaji Lembur</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">Gaji Pokok</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">Potongan</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">Gaji Bersih</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px; background-color: #C6E0B4;">TOTAL GAJI</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Approval</th>
            </tr>
        </thead>

        {{-- Body Tabel --}}
        <tbody>
            @php
                $totalGajiAll = 0;
                $no = 1;
            @endphp

            {{-- 🔥 LOOP BERDASARKAN TANGGAL (BUKAN ABSENSI) --}}
            @foreach($allDates as $date)
                @php
                    // Cari absensi di tanggal ini
                    $item = $userAbsensi->first(function($absen) use ($date) {
                        return \Carbon\Carbon::parse($absen->check_in_at)->isSameDay($date);
                    });

                    // Tentuin style buat baris kosong
                    $rowStyle = '';
                    $cellStyle = 'text-align: center; border: 1px solid #000000;';

                    if (!$item) {
                        // 🔥 BARIS KOSONG = MERAH + BOLD
                        $rowStyle = 'background-color: #FFE6E6;'; // Light red background
                        $cellStyle = 'text-align: center; border: 1px solid #000000; color: #FF0000; font-weight: bold;';
                    }
                @endphp

                @if($item)
                    {{-- BARIS NORMAL (ADA DATA) --}}
                    @php
                        $totalGajiHari = ($item->base_salary + $item->overtime_pay) - $item->late_penalty;
                        $totalGajiAll += $totalGajiHari;
                    @endphp
                    <tr>
                        <td style="text-align: center; border: 1px solid #000000;">{{ $no++ }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ \Carbon\Carbon::parse($item->check_in_at)->translatedFormat('d M Y') }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ \Carbon\Carbon::parse($item->check_in_at)->format('H:i') }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ $item->check_out_at ? \Carbon\Carbon::parse($item->check_out_at)->format('H:i') : '-' }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ ucfirst($item->status) }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ ucfirst($item->tipe ?? '-') }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ $item->late_minutes }} Menit</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ $item->overtime_minutes }} Menit</td>
                        <td style="text-align: right; border: 1px solid #000000;">Rp {{ number_format($item->overtime_pay, 0, ',', '.') }}</td>
                        <td style="text-align: right; border: 1px solid #000000;">Rp {{ number_format($item->base_salary, 0, ',', '.') }}</td>
                        <td style="text-align: right; border: 1px solid #000000;">Rp {{ number_format($item->late_penalty, 0, ',', '.') }}</td>
                        <td style="text-align: right; border: 1px solid #000000;">Rp {{ number_format($item->final_salary, 0, ',', '.') }}</td>
                        <td style="text-align: right; border: 1px solid #000000; background-color: #E2EFDA; font-weight: bold;">Rp {{ number_format($totalGajiHari, 0, ',', '.') }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ ucfirst($item->status_approval) }}</td>
                    </tr>
                @else
                    {{-- 🔥 BARIS KOSONG (GA ABSEN) = MERAH + BOLD --}}
                    <tr style="{{ $rowStyle }}">
                        <td style="{{ $cellStyle }}">{{ $no++ }}</td>
                        <td style="{{ $cellStyle }}">{{ $date->translatedFormat('d M Y') }}</td>
                        <td style="{{ $cellStyle }}">-</td>
                        <td style="{{ $cellStyle }}">-</td>
                        <td style="{{ $cellStyle }}">-</td>
                        <td style="{{ $cellStyle }}">-</td>
                        <td style="{{ $cellStyle }}">0 Menit</td>
                        <td style="{{ $cellStyle }}">0 Menit</td>
                        <td style="text-align: right; border: 1px solid #000000; color: #FF0000; font-weight: bold;">Rp 0</td>
                        <td style="text-align: right; border: 1px solid #000000; color: #FF0000; font-weight: bold;">Rp 0</td>
                        <td style="text-align: right; border: 1px solid #000000; color: #FF0000; font-weight: bold;">Rp 0</td>
                        <td style="text-align: right; border: 1px solid #000000; color: #FF0000; font-weight: bold;">Rp 0</td>
                        <td style="text-align: right; border: 1px solid #000000; background-color: #FFE6E6; color: #FF0000; font-weight: bold;">Rp 0</td>
                        <td style="{{ $cellStyle }}">-</td>
                    </tr>
                @endif
            @endforeach

            {{-- 🔥 BARIS TOTAL PER USER (KOLOM SPESIFIK) --}}
            @php
                $totalTelat = $userAbsensi->sum('late_minutes');
                $totalMenitLembur = $userAbsensi->sum('overtime_minutes');
                $totalGajiLembur = $userAbsensi->sum('overtime_pay');
                $totalGajiPokok = $userAbsensi->sum('base_salary');
                $totalPotongan = $userAbsensi->sum('late_penalty');
                $totalGajiBersih = $userAbsensi->sum('final_salary');
                // $totalGajiAll sudah dihitung di atas
            @endphp
            <tr style="background-color: #F0F0F0;">
                <td colspan="6" style="text-align: right; font-weight: bold; border: 1px solid #000000; padding-right: 5px;">TOTAL:</td>
                <td style="text-align: center; font-weight: bold; border: 1px solid #000000;">{{ $totalTelat }} Menit</td>
                <td style="text-align: center; font-weight: bold; border: 1px solid #000000;">{{ $totalMenitLembur }} Menit</td>
                <td style="text-align: right; font-weight: bold; border: 1px solid #000000; padding-right: 5px;">Rp {{ number_format($totalGajiLembur, 0, ',', '.') }}</td>
                <td style="text-align: right; font-weight: bold; border: 1px solid #000000; padding-right: 5px;">Rp {{ number_format($totalGajiPokok, 0, ',', '.') }}</td>
                <td style="text-align: right; font-weight: bold; border: 1px solid #000000; padding-right: 5px;">Rp {{ number_format($totalPotongan, 0, ',', '.') }}</td>
                <td style="text-align: right; font-weight: bold; border: 1px solid #000000; padding-right: 5px;">Rp {{ number_format($totalGajiBersih, 0, ',', '.') }}</td>
                <td style="text-align: right; font-weight: bold; border: 1px solid #000000; background-color: #E2EFDA; padding-right: 5px;">Rp {{ number_format($totalGajiAll, 0, ',', '.') }}</td>
                <td style="border: 1px solid #000000;"></td>
            </tr>

            {{-- Baris Total per Karyawan (TOTAL DITERIMA) --}}
            <tr>
                <td colspan="11" style="text-align: right; font-weight: bold; border: 1px solid #000000;">TOTAL DITERIMA:</td>
                <td colspan="2" style="text-align: right; font-weight: bold; border: 1px solid #000000; background-color: #C6E0B4; font-size: 12px;">
                    Rp {{ number_format($totalGajiAll, 0, ',', '.') }}
                </td>
                <td style="border: 1px solid #000000;"></td>
            </tr>

            {{-- Jarak Antar Karyawan --}}
            <tr><td colspan="14"></td></tr>
            <tr><td colspan="14"></td></tr>
        </tbody>
    @endforeach

    {{-- 🔥 GRAND TOTAL - KOLOM NYAMBUNG SAMA TABEL USER! --}}
    <tbody>
        <tr><td colspan="14"></td></tr>

        {{-- BARIS 1: Header Biru + 3 Label (KOLOM SEJAJAR!) --}}
        <tr>
            {{-- Header Biru: Kolom 1-8 --}}
            <td colspan="8" rowspan="2" style="font-weight: bold; font-size: 16px; text-align: center; background-color: #00B0F0; color: #FFFFFF; border: 1px solid #000000; vertical-align: middle;">
                TOTAL KESELURUHAN {{ $categoryLabel }}
            </td>

            {{-- Total Gaji Lembur: Kolom 9 (sejajar "Gaji Lembur") --}}
            <td style="font-weight: bold; text-align: center; border: 1px solid #000000; background-color: #FFF2CC;">
                Total Gaji Lembur
            </td>

            {{-- Total Gaji Pokok: Kolom 10 (sejajar "Gaji Pokok") --}}
            <td style="font-weight: bold; text-align: center; border: 1px solid #000000; background-color: #E2EFDA;">
                Total Gaji Pokok
            </td>

            {{-- Total Gaji: Kolom 11 (sejajar "Potongan") --}}
            <td style="font-weight: bold; text-align: center; border: 1px solid #000000; background-color: #C6E0B4;">
                Total Gaji
            </td>

            {{-- Kolom 12: Kosong --}}
            <td style="border: 1px solid #000000;"></td>

            {{-- Kolom 13: Kosong --}}
            <td style="border: 1px solid #000000;"></td>

            {{-- Kolom 14: Kosong --}}
            <td style="border: 1px solid #000000;"></td>
        </tr>

        {{-- BARIS 2: Angka (KOLOM SEJAJAR!) --}}
        <tr>
            {{-- Angka Gaji Lembur: Kolom 9 --}}
            <td style="font-weight: bold; text-align: right; border: 1px solid #000000; background-color: #FFF2CC; padding-right: 5px;">
                Rp {{ number_format($grandTotalGajiLembur, 0, ',', '.') }}
            </td>

            {{-- Angka Gaji Pokok: Kolom 10 --}}
            <td style="font-weight: bold; text-align: right; border: 1px solid #000000; background-color: #E2EFDA; padding-right: 5px;">
                Rp {{ number_format($grandTotalGajiPokok, 0, ',', '.') }}
            </td>

            {{-- Angka Total Gaji: Kolom 11 (FONT SIZE 11px) --}}
            <td style="font-weight: bold; text-align: right; border: 1px solid #000000; background-color: #C6E0B4; padding-right: 5px; font-size: 11px;">
                Rp {{ number_format($grandTotalGajiBersih, 0, ',', '.') }}
            </td>

            {{-- Kolom 12: Kosong --}}
            <td style="border: 1px solid #000000;"></td>

            {{-- Kolom 13: Kosong --}}
            <td style="border: 1px solid #000000;"></td>

            {{-- Kolom 14: Kosong --}}
            <td style="border: 1px solid #000000;"></td>
        </tr>
    </tbody>
</table>
