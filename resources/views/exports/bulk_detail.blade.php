{{-- Ini adalah file HTML yang bakal jadi Excel --}}
<table>
    {{-- Loop per Karyawan --}}
    @foreach($users as $user)
        @php
            // Ambil data absensi si user ini
            $userAbsensi = $absensiData->where('user_id', $user->id);

            //  CEK ADA GA TANGGAL YANG KOSONG
            $hasMissingDate = false;
            foreach ($allDates as $date) {
                if ($date->dayOfWeek === 0 || $date->dayOfWeek === 6) continue;

                $absensiOnDate = $userAbsensi->first(function($item) use ($date) {
                    return \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                });

                if (!$absensiOnDate) {
                    $hasMissingDate = true;
                    break;
                }
            }

            $userNameColor = $hasMissingDate ? '#FF0000' : '#000000';
            $userNameWeight = $hasMissingDate ? 'bold' : 'normal';
        @endphp

        {{-- Header Karyawan --}}
        <thead>
            <tr>
                <td colspan="{{ $isOnlyOrganik ? '11' : '14' }}" style="font-weight: bold; font-size: 16px; text-align: center; height: 30px; vertical-align: middle; background-color: #BDD7EE;">
                    REKAP ABSENSI KARYAWAN
                </td>
            </tr>
            <tr>
                <td colspan="{{ $isOnlyOrganik ? '5' : '7' }}" style="font-weight: {{ $userNameWeight }}; text-align: left; color: {{ $userNameColor }};">Nama: {{ $user->name }}</td>
                <td colspan="{{ $isOnlyOrganik ? '6' : '7' }}" style="font-weight: bold; text-align: left;">ID: {{ $user->id_karyawan }}</td>
            </tr>
            <tr>
                <td colspan="{{ $isOnlyOrganik ? '11' : '14' }}" style="font-weight: bold; text-align: left;">Periode: {{ $periodeStr }}</td>
            </tr>
            <tr><td colspan="{{ $isOnlyOrganik ? '11' : '14' }}"></td></tr>

            {{-- Header Tabel --}}
            <tr style="background-color: #4F46E5; color: #FFFFFF;">
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 50px;">No</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">Tanggal</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 80px;">In</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 80px;">Out</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Status</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Telat</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Menit Lbr</th>
                @if (!$isOnlyOrganik)
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px; background-color: #E2EFDA; color: #000;">Gaji Pokok</th>
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px; background-color: #FFF2CC; color: #000;">Gaji Lembur</th>
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px; background-color: #FFD9D9; color: #000;">Potongan</th>
                @endif
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 250px;">Keterangan Koreksi</th>
                @if (!$isOnlyOrganik)
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 130px; background-color: #C6E0B4; color: #000;">TOTAL DITERIMA</th>
                @endif
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Approval</th>
            </tr>
        </thead>

        {{-- Body Tabel --}}
        <tbody>
            @php
                $userTotalDiterima = 0;
                $no = 1;
            @endphp

            @foreach($allDates as $date)
                @php
                    // ... (logika item sama, tidak diubah)
                    $item = $userAbsensi->filter(function($absen) use ($date) {
                        return \Carbon\Carbon::parse($absen->check_in_at)->isSameDay($date);
                    })->sortByDesc('id')->first();

                    $isWeekday = $date->dayOfWeek >= 1 && $date->dayOfWeek <= 5;
                    $rowStyle = '';
                    $cellStyle = 'text-align: center; border: 1px solid #000000;';

                    if (!$item && $isWeekday) {
                        $rowStyle = 'background-color: #FFE6E6;'; 
                        $cellStyle = 'text-align: center; border: 1px solid #000000; color: #FF0000; font-weight: bold;';
                    }

                    if ($item) {
                        $bonusHarian = ($item->adjustment_salary > 0) ? round($item->adjustment_salary) : 0;
                        $gajiPokokHarian = round($item->base_salary) + $bonusHarian;
                        $gajiLemburHarian = round($item->overtime_pay);
                        $koreksiMinusHarian = ($item->adjustment_salary < 0) ? round(abs($item->adjustment_salary)) : 0;
                        $totalPotonganHarian = round($item->late_penalty) + $koreksiMinusHarian;
                        $totalDiterimaHarian = ($gajiPokokHarian + $gajiLemburHarian) - $totalPotonganHarian;
                        
                        $userTotalDiterima += $totalDiterimaHarian;

                        $keteranganHarian = '-';
                        if ($item->adjustment_reason) {
                            $labelAdj = $item->adjustment_salary > 0 ? "Bonus" : "Potongan";
                            $keteranganHarian = "{$labelAdj}: " . number_format(abs($item->adjustment_salary)) . " ({$item->adjustment_reason})";
                        }
                    }
                @endphp

                @if($item)
                    <tr>
                        <td style="text-align: center; border: 1px solid #000000;">{{ $no++ }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ $date->translatedFormat('d M Y') }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ \Carbon\Carbon::parse($item->check_in_at)->format('H:i') }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ $item->check_out_at ? \Carbon\Carbon::parse($item->check_out_at)->format('H:i') : '-' }}</td>
                        <td style="text-align: center; border: 1px solid #000000; font-size: 9px;">{{ ucfirst($item->status) }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ $item->late_minutes }}m</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ $item->overtime_minutes }}m</td>
                        @if (!$isOnlyOrganik)
                            <td style="text-align: right; border: 1px solid #000000;">Rp {{ number_format($gajiPokokHarian, 0, ',', '.') }}</td>
                            <td style="text-align: right; border: 1px solid #000000;">Rp {{ number_format($gajiLemburHarian, 0, ',', '.') }}</td>
                            <td style="text-align: right; border: 1px solid #000000; color: #FF0000;">Rp {{ number_format($totalPotonganHarian, 0, ',', '.') }}</td>
                        @endif
                        <td style="text-align: left; border: 1px solid #000000; font-size: 10px;">{{ $keteranganHarian }}</td>
                        @if (!$isOnlyOrganik)
                            <td style="text-align: right; border: 1px solid #000000; background-color: #E2EFDA; font-weight: bold;">Rp {{ number_format($totalDiterimaHarian, 0, ',', '.') }}</td>
                        @endif
                        <td style="text-align: center; border: 1px solid #000000; font-size: 9px;">{{ ucfirst($item->status_approval) }}</td>
                    </tr>
                @else
                    <tr style="{{ $rowStyle }}">
                        <td style="{{ $cellStyle }}">{{ $no++ }}</td>
                        <td style="{{ $cellStyle }}">{{ $date->translatedFormat('d M Y') }}</td>
                        <td colspan="5" style="text-align: center; border: 1px solid #000000;">-</td>
                        <td style="text-align: right; border: 1px solid #000000;">Rp 0</td>
                        <td style="text-align: right; border: 1px solid #000000;">Rp 0</td>
                        <td style="text-align: right; border: 1px solid #000000;">Rp 0</td>
                        <td style="text-align: center; border: 1px solid #000000;">-</td>
                        <td style="text-align: right; border: 1px solid #000000;">Rp 0</td>
                        <td style="text-align: center; border: 1px solid #000000;">-</td>
                    </tr>
                @endif
            @endforeach

            {{-- TOTAL PER USER --}}
            @php
                $totalTelatRow = $userAbsensi->sum('late_minutes');
                $totalMenitLemburRow = $userAbsensi->sum('overtime_minutes');
                
                $totalPokokUser = round($userAbsensi->sum('base_salary') + $userAbsensi->where('adjustment_salary', '>', 0)->sum('adjustment_salary'));
                $totalLemburUser = round($userAbsensi->sum('overtime_pay'));
                $totalPotonganUser = round($userAbsensi->sum('late_penalty') + abs($userAbsensi->where('adjustment_salary', '<', 0)->sum('adjustment_salary')));
            @endphp
            <tr style="background-color: #F2F2F2; font-weight: bold;">
                <td colspan="5" style="text-align: right; border: 1px solid #000000;">SUBTOTAL:</td>
                <td style="text-align: center; border: 1px solid #000000;">{{ $totalTelatRow }}m</td>
                <td style="text-align: center; border: 1px solid #000000;">{{ $totalMenitLemburRow }}m</td>
                @if (!$isOnlyOrganik)
                    <td style="text-align: right; border: 1px solid #000000;">Rp {{ number_format($totalPokokUser, 0, ',', '.') }}</td>
                    <td style="text-align: right; border: 1px solid #000000;">Rp {{ number_format($totalLemburUser, 0, ',', '.') }}</td>
                    <td style="text-align: right; border: 1px solid #000000; color: #FF0000;">Rp {{ number_format($totalPotonganUser, 0, ',', '.') }}</td>
                @endif
                <td style="border: 1px solid #000000;"></td>
                @if (!$isOnlyOrganik)
                    <td style="text-align: right; border: 1px solid #000000; background-color: #C6E0B4; font-size: 11px;">Rp {{ number_format($userTotalDiterima, 0, ',', '.') }}</td>
                @endif
                <td style="border: 1px solid #000000;"></td>
            </tr>

            <tr><td colspan="14"></td></tr>
        </tbody>
    @endforeach

    {{-- GRAND TOTAL --}}
    @if (!$isOnlyOrganik)
        <tbody>
            <tr style="background-color: #00B0F0; color: #FFFFFF; font-weight: bold;">
                <td colspan="7" rowspan="2" style="text-align: center; vertical-align: middle; border: 1px solid #000000; font-size: 14px;">TOTAL KESELURUHAN</td>
                <td style="text-align: center; border: 1px solid #000000; background-color: #E2EFDA; color: #000;">Gaji Pokok</td>
                <td style="text-align: center; border: 1px solid #000000; background-color: #FFF2CC; color: #000;">Gaji Lembur</td>
                <td style="text-align: center; border: 1px solid #000000; background-color: #FFD9D9; color: #000;">Potongan</td>
                <td colspan="1" style="border: 1px solid #000000; background-color: #FFFFFF;"></td>
                <td style="text-align: center; border: 1px solid #000000; background-color: #C6E0B4; color: #000;">TOTAL DITERIMA</td>
                <td rowspan="2" style="border: 1px solid #000000; background-color: #FFFFFF;"></td>
            </tr>
            <tr style="font-weight: bold;">
                <td style="text-align: right; border: 1px solid #000000; background-color: #E2EFDA; color: #000;">Rp {{ number_format($grandTotalGajiPokok, 0, ',', '.') }}</td>
                <td style="text-align: right; border: 1px solid #000000; background-color: #FFF2CC; color: #000;">Rp {{ number_format($grandTotalGajiLembur, 0, ',', '.') }}</td>
                <td style="text-align: right; border: 1px solid #000000; background-color: #FFD9D9; color: #FF0000;">Rp {{ number_format($grandTotalPotongan, 0, ',', '.') }}</td>
                <td style="border: 1px solid #000000; background-color: #FFFFFF;"></td>
                <td style="text-align: right; border: 1px solid #000000; background-color: #C6E0B4; color: #000; font-size: 12px;">Rp {{ number_format($grandTotalGajiBersih, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    @endif
</table>
