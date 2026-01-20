{{-- resources/views/exports/bulk_simple.blade.php --}}

@foreach($dateGroups as $groupIndex => $group)
<table>
    {{-- ======================= --}}
    {{-- HEADER UTAMA --}}
    {{-- ======================= --}}
    <thead>
        <tr>
            <td colspan="14" style="font-weight:bold; font-size:16px; text-align:center; height:40px; vertical-align:middle; background:#FFFF00; border:1px solid #000;">
                ABSENSI {{ strtoupper($categoryLabel ?? 'KARYAWAN') }}
            </td>
        </tr>
        <tr>
            <td colspan="14" style="font-weight:bold; text-align:center; background:#FFFF00; border:1px solid #000;">
                {{ $group['month_label'] }}
            </td>
        </tr>
        <tr>
            <td colspan="14" style="text-align:center; border:1px solid #000;">
                {{ $periodeStr ?? '' }}
            </td>
        </tr>
        <tr><td colspan="14"></td></tr>

        {{-- HEADER KOLOM --}}
        <tr style="background:#D9D9D9;">
            <th style="border:1px solid #000; width:50px;">NO</th>
            <th style="border:1px solid #000; width:200px;">Nama</th>
            <th colspan="11" style="border:1px solid #000; text-align:center;">Tanggal Dan Bulan</th>
            <th style="border:1px solid #000; width:100px;">Total Telat</th>
            <th style="border:1px solid #000; width:120px;">Total Lembur</th>
        </tr>
    </thead>

    {{-- ======================= --}}
    {{-- BODY --}}
    {{-- ======================= --}}
    <tbody>
        @foreach($users as $userIndex => $user)
            @php
                $userAbsensi = $absensiData->where('user_id', $user->id);
                $allDates = $group['dates'];
                $dateChunks = array_chunk($allDates, 11);

                // Hitung total telat & lembur untuk dates di group ini
                $totalTelat = 0;
                $totalMenitLembur = 0;

                foreach ($allDates as $date) {
                    $absen = $userAbsensi->first(fn($i) =>
                        \Carbon\Carbon::parse($i->check_in_at)->isSameDay($date)
                    );

                    if ($absen) {
                        if (($absen->late_minutes ?? 0) > 0) $totalTelat++;
                        $totalMenitLembur += $absen->overtime_minutes ?? 0;
                    }
                }

                $lemburJam = floor($totalMenitLembur / 60);
                $lemburMenit = $totalMenitLembur % 60;
                $totalLemburStr = $totalMenitLembur ? "{$lemburJam}j {$lemburMenit}m" : '-';

                // Rowspan = jumlah chunk * 2 (header tanggal + data)
                $rowSpan = count($dateChunks) * 2;
            @endphp

            @foreach($dateChunks as $chunkIndex => $chunk)
                {{-- ROW HEADER TANGGAL --}}
                <tr>
                    {{-- NO & NAMA - Hanya di chunk pertama --}}
                    @if($chunkIndex === 0)
                        <td rowspan="{{ $rowSpan }}" style="border:1px solid #000; vertical-align:top; text-align:center; font-weight:bold;">
                            {{ $userIndex + 1 }}
                        </td>
                        <td rowspan="{{ $rowSpan }}" style="border:1px solid #000; vertical-align:top; font-weight:bold; padding-left:5px;">
                            {{ $user->name }}
                        </td>
                    @endif

                    {{-- HEADER TANGGAL (01-Jan, 02-Jan, dst) --}}
                    @foreach($chunk as $date)
                        <td style="border:1px solid #000; text-align:center; background:#f2f2f2; font-size:9px; font-weight:bold;">
                            {{ \Carbon\Carbon::parse($date)->format('d-M') }}
                        </td>
                    @endforeach

                    {{-- Padding kalau chunk kurang dari 11 --}}
                    @for($k = count($chunk); $k < 11; $k++)
                        <td style="border:1px solid #000; background:#eaeaea;"></td>
                    @endfor

                    {{-- TOTAL TELAT & LEMBUR - Hanya di chunk pertama --}}
                    @if($chunkIndex === 0)
                        <td rowspan="{{ $rowSpan }}" style="border:1px solid #000; vertical-align:top; text-align:center;">
                            {{ $totalTelat ?: '' }}
                        </td>
                        <td rowspan="{{ $rowSpan }}" style="border:1px solid #000; vertical-align:top; text-align:center;">
                            {{ $totalLemburStr }}
                        </td>
                    @endif
                </tr>

                {{-- ROW DATA ABSENSI --}}
                <tr>
                    @foreach($chunk as $date)
                        @php
                            $absen = $userAbsensi->first(fn($i) =>
                                \Carbon\Carbon::parse($i->check_in_at)->isSameDay($date)
                            );

                            $cell = '-';
                            $bg = '';

                            if ($absen) {
                                $in = \Carbon\Carbon::parse($absen->check_in_at)->format('H:i');
                                $out = $absen->check_out_at ? \Carbon\Carbon::parse($absen->check_out_at)->format('H:i') : '--';

                                if ($absen->status == 'hadir') {
                                    $cell = "$in - $out";
                                    if ($absen->late_minutes > 0) $cell .= " Tl:{$absen->late_minutes}m";
                                    if ($absen->overtime_minutes > 0) {
                                        $jam = floor($absen->overtime_minutes / 60);
                                        $menit = $absen->overtime_minutes % 60;
                                        $cell .= " Lb:{$jam}j";
                                        if ($menit > 0) $cell .= " {$menit}m";
                                    }
                                } else {
                                    $cell = ucfirst($absen->status);
                                }
                            } else {
                                if (!\Carbon\Carbon::parse($date)->isWeekend()) {
                                    $cell = 'Tidak Masuk';
                                    $bg = 'background:#FFB6C1;';
                                }
                            }
                        @endphp

                        <td style="border:1px solid #000; font-size:9px; text-align:center; {{ $bg }}">
                            {{ $cell }}
                        </td>
                    @endforeach

                    {{-- Padding kalau chunk kurang dari 11 --}}
                    @for($k = count($chunk); $k < 11; $k++)
                        <td style="border:1px solid #000;"></td>
                    @endfor
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

{{-- Spacing antar table group --}}
@if($groupIndex < count($dateGroups)-1)
<table><tr><td style="height:30px;"></td></tr></table>
@endif
@endforeach
