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

        {{-- HEADER TANGGAL PERTAMA --}}
        <tr style="background:#D9D9D9;">
            <th style="border:1px solid #000; width:200px;">Nama</th>

            @php
                $allDates = $group['dates'];
                $first11 = array_slice($allDates, 0, 11);
            @endphp

            @foreach($first11 as $date)
                <th style="border:1px solid #000; text-align:center; width:90px;">
                    {{ \Carbon\Carbon::parse($date)->format('d-M') }}
                </th>
            @endforeach

            @for($i = count($first11); $i < 11; $i++)
                <th style="border:1px solid #000;"></th>
            @endfor

            <th style="border:1px solid #000; width:100px;">Total Telat</th>
            <th style="border:1px solid #000; width:120px;">Total Lembur</th>
        </tr>
    </thead>

    {{-- ======================= --}}
    {{-- BODY --}}
    {{-- ======================= --}}
    <tbody>
        @foreach($users as $user)
            @php
                $userAbsensi = $absensiData->where('user_id', $user->id);
                $dateChunks = array_chunk($allDates, 11);

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

                $totalChunks = count($dateChunks);
                $rowSpan = 1 + (($totalChunks - 1) * 2);
            @endphp

            @foreach($dateChunks as $chunkIndex => $chunk)

                {{-- HEADER SELIPAN --}}
                @if($chunkIndex > 0)
                <tr>
                    <td style="border:1px solid #000; background:#f2f2f2;"></td>

                    @foreach($chunk as $date)
                        <td style="border:1px solid #000; font-weight:bold; text-align:center; background:#f2f2f2;">
                            {{ \Carbon\Carbon::parse($date)->format('d-M') }}
                        </td>
                    @endforeach

                    @for($k = count($chunk); $k < 11; $k++)
                        <td style="border:1px solid #000; background:#eaeaea;"></td>
                    @endfor

                    <td style="border:1px solid #000; background:#f2f2f2;"></td>
                    <td style="border:1px solid #000; background:#f2f2f2;"></td>
                </tr>
                @endif

                {{-- DATA --}}
                <tr>
                    @if($chunkIndex === 0)
                        <td rowspan="{{ $rowSpan }}" style="border:1px solid #000; vertical-align:top; font-weight:bold;">
                            {{ $user->name }}
                        </td>
                    @endif

                    @foreach($chunk as $date)
                        @php
                            $absen = $userAbsensi->first(fn($i) =>
                                \Carbon\Carbon::parse($i->check_in_at)->isSameDay($date)
                            );

                            $cell = '-';
                            $bg = '';

                            if ($absen) {
                                $in = \Carbon\Carbon::parse($absen->check_in_at)->format('H:i');
                                $out = $absen->check_out_at ? \Carbon\Carbon::parse($absen->check_out_at)->format('H:i') : '-';

                                if ($absen->status == 'hadir') {
                                    $cell = "$in - $out";
                                    if ($absen->late_minutes > 0) $cell .= " Tl:{$absen->late_minutes}m";
                                    if ($absen->overtime_minutes > 0) {
                                        $jam = floor($absen->overtime_minutes / 60);
                                        $cell .= " Lb:{$jam}j";
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

                        <td style="border:1px solid #000; font-size:10px; text-align:center; {{ $bg }}">
                            {{ $cell }}
                        </td>
                    @endforeach

                    @for($k = count($chunk); $k < 11; $k++)
                        <td style="border:1px solid #000;"></td>
                    @endfor

                    @if($chunkIndex === 0)
                        <td rowspan="{{ $rowSpan }}" style="border:1px solid #000; vertical-align:top; text-align:center;">
                            {{ $totalTelat ?: '' }}
                        </td>
                        <td rowspan="{{ $rowSpan }}" style="border:1px solid #000; vertical-align:top; text-align:center;">
                            {{ $totalLemburStr }}
                        </td>
                    @endif
                </tr>

            @endforeach
        @endforeach
    </tbody>
</table>

@if($groupIndex < count($dateGroups)-1)
<table><tr><td style="height:30px;"></td></tr></table>
@endif
@endforeach
