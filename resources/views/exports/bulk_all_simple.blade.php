{{-- resources/views/exports/bulk_all_simple.blade.php --}}
<table>
    <thead>
        {{-- ROW 1 --}}
        <tr>
            <td colspan="17" style="font-weight:bold; font-size:16px; text-align:center; height:40px; vertical-align:middle; background:#4F46E5; color:#ffffff; border:1px solid #000;">
                LAPORAN REKAP ABSENSI SEMUA KARYAWAN
            </td>
        </tr>
        {{-- ROW 2 --}}
        <tr>
            <td colspan="17" style="font-weight:bold; text-align:center; background:#4F46E5; color:#ffffff; border:1px solid #000;">
                {{ strtoupper(\Carbon\Carbon::parse($allDates[0])->translatedFormat('F Y')) }}
            </td>
        </tr>
        {{-- ROW 3 --}}
        <tr>
            <td colspan="17" style="text-align:center; border:1px solid #000;">
                {{ $periodeStr ?? '' }}
            </td>
        </tr>
        {{-- ROW 4 --}}
        <tr><td colspan="17"></td></tr>
        {{-- ROW 5 --}}
        <tr>
            <td colspan="4" style="font-weight:bold; font-size:14px; background:#D9D9D9; border:1px solid #000;">
                RINGKASAN GLOBAL PERIODE INI
            </td>
            <td colspan="13"></td>
        </tr>
        {{-- ROW 6: HADIR --}}
        <tr>
            <td colspan="2" style="font-weight:bold; border:1px solid #000;">Hadir</td>
            <td colspan="2" style="border:1px solid #000;">{{ $summary['total_hadir'] }}</td>
            <td colspan="13"></td>
        </tr>
        {{-- ROW 7: SAKIT --}}
        <tr>
            <td colspan="2" style="font-weight:bold; border:1px solid #000;">Sakit</td>
            <td colspan="2" style="border:1px solid #000;">{{ $summary['total_sakit'] }}</td>
            <td colspan="13"></td>
        </tr>
        {{-- ROW 8: IZIN --}}
        <tr>
            <td colspan="2" style="font-weight:bold; border:1px solid #000;">Izin</td>
            <td colspan="2" style="border:1px solid #000;">{{ $summary['total_izin'] }}</td>
            <td colspan="13"></td>
        </tr>
        {{-- ROW 9: ALPHA --}}
        <tr>
            <td colspan="2" style="font-weight:bold; border:1px solid #000;">Alpha</td>
            <td colspan="2" style="border:1px solid #000;">{{ $summary['total_alpha'] }}</td>
            <td colspan="13"></td>
        </tr>
        {{-- ROW 10 --}}
        <tr>
            <td colspan="4" style="font-weight:bold; border:1px solid #000; background:#f2f2f2;">Total Lembur: {{ $summary['total_lembur'] }} Hari</td>
            <td colspan="13"></td>
        </tr>
        {{-- ROW 11 --}}
        <tr><td colspan="17"></td></tr>

        {{-- ROW 12 --}}
        <tr>
            <td colspan="17" style="font-weight:bold; font-size:14px; background:#D9D9D9; border:1px solid #000;">
                DETAIL BERDASARKAN KATEGORI KARYAWAN
            </td>
        </tr>
        {{-- ROW 13 --}}
        <tr style="background:#f2f2f2;">
            <td colspan="3" style="font-weight:bold; text-align:center; border:1px solid #000;">Kategori</td>
            <td colspan="3" style="font-weight:bold; text-align:center; border:1px solid #000;">Hadir</td>
            <td colspan="3" style="font-weight:bold; text-align:center; border:1px solid #000;">Sakit</td>
            <td colspan="3" style="font-weight:bold; text-align:center; border:1px solid #000;">Izin</td>
            <td colspan="3" style="font-weight:bold; text-align:center; border:1px solid #000;">Alpha</td>
            <td colspan="2" style="font-weight:bold; text-align:center; border:1px solid #000;">Lembur (Hari)</td>
        </tr>
        @foreach($summary['breakdown'] as $kategori => $data)
            <tr>
                <td colspan="3" style="border:1px solid #000;">{{ $kategori }}</td>
                <td colspan="3" style="text-align:center; border:1px solid #000;">{{ $data['hadir'] }}</td>
                <td colspan="3" style="text-align:center; border:1px solid #000;">{{ $data['sakit'] }}</td>
                <td colspan="3" style="text-align:center; border:1px solid #000;">{{ $data['izin'] }}</td>
                <td colspan="3" style="text-align:center; border:1px solid #000;">{{ $data['alpha'] }}</td>
                <td colspan="2" style="text-align:center; border:1px solid #000;">{{ $data['lembur'] }}</td>
            </tr>
        @endforeach
        <tr><td colspan="17"></td></tr>

        {{-- HEADER KOLOM DATA --}}
        <tr style="background:#4F46E5; color:#ffffff;">
            <th style="border:1px solid #000; width:50px;">NO</th>
            <th colspan="11" style="border:1px solid #000; text-align:center;">Tanggal Dan Bulan (Check-In / Check-Out)</th>
            <th style="border:1px solid #000; width:45px;">Telat</th>
            <th style="border:1px solid #000; width:45px;">Izin</th>
            <th style="border:1px solid #000; width:45px;">Sakit</th>
            <th style="border:1px solid #000; width:45px;">Alpha</th>
            <th style="border:1px solid #000; width:60px;">Lembur</th>
        </tr>
    </thead>

    <tbody>
        @foreach($users as $userIndex => $user)
            @php
                $userAbsensi = $absensiData->where('user_id', $user->id);

                $totalTelat = 0;
                $totalIzin = 0;
                $totalSakit = 0;
                $totalAlpha = 0;
                $totalMenitLembur = 0;

                foreach ($allDates as $date) {
                    $absen = $userAbsensi->first(fn($i) =>
                        \Carbon\Carbon::parse($i->check_in_at)->isSameDay($date)
                    );

                    if ($absen) {
                        if (($absen->late_minutes ?? 0) > 0) $totalTelat++;
                        if (strtolower($absen->status) == 'izin') $totalIzin++;
                        if (strtolower($absen->status) == 'sakit') $totalSakit++;
                        $totalMenitLembur += $absen->overtime_minutes ?? 0;
                    } else {
                        if (!\Carbon\Carbon::parse($date)->isWeekend() && !\App\Models\Holiday::isHoliday($date)) {
                            $totalAlpha++;
                        }
                    }
                }

                $lemburJam = floor($totalMenitLembur / 60);
                $lemburMenit = $totalMenitLembur % 60;
                $totalLemburStr = $totalMenitLembur ? "{$lemburJam}j {$lemburMenit}m" : '-';

                $rowSpan = 1 + (count($dateChunks) * 2);
            @endphp

            <tr>
                <td rowspan="{{ $rowSpan }}" style="border:1px solid #000; vertical-align:top; text-align:center; font-weight:bold;">
                    {{ $userIndex + 1 }}
                </td>
                <td colspan="11" style="border:1px solid #000; font-weight:bold; padding:5px; text-align:center; background:#f9f9f9;">
                    {{ strtoupper($user->name) }} ({{ strtoupper($user->employment_type) }})
                </td>
                <td rowspan="{{ $rowSpan }}" style="border:1px solid #000; vertical-align:middle; text-align:center; font-size:10px;">
                    {{ $totalTelat ?: '' }}
                </td>
                <td rowspan="{{ $rowSpan }}" style="border:1px solid #000; vertical-align:middle; text-align:center; font-size:10px;">
                    {{ $totalIzin ?: '' }}
                </td>
                <td rowspan="{{ $rowSpan }}" style="border:1px solid #000; vertical-align:middle; text-align:center; font-size:10px;">
                    {{ $totalSakit ?: '' }}
                </td>
                <td rowspan="{{ $rowSpan }}" style="border:1px solid #000; vertical-align:middle; text-align:center; font-size:10px;">
                    {{ $totalAlpha ?: '' }}
                </td>
                <td rowspan="{{ $rowSpan }}" style="border:1px solid #000; vertical-align:middle; text-align:center; font-size:10px;">
                    {{ $totalLemburStr }}
                </td>
            </tr>

            @foreach($dateChunks as $chunkIndex => $chunk)
                <tr>
                    @foreach($chunk as $date)
                        <td style="border:1px solid #000; text-align:center; background:#f2f2f2; font-size:9px; font-weight:bold;">
                            {{ \Carbon\Carbon::parse($date)->format('d-M') }}
                        </td>
                    @endforeach
                    @for($k = count($chunk); $k < 11; $k++)
                        <td style="border:1px solid #000; background:#eaeaea;"></td>
                    @endfor
                </tr>
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
                                } else {
                                    $cell = ucfirst($absen->status);
                                }
                            } else {
                                if (!\Carbon\Carbon::parse($date)->isWeekend()) {
                                    if (\App\Models\Holiday::isHoliday($date)) {
                                        $holiday = \App\Models\Holiday::where('holiday_date', \Carbon\Carbon::parse($date)->toDateString())->first();
                                        $cell = 'Lbr: ' . $holiday->name;
                                        $bg = 'background:#D3D3D3;';
                                    } else {
                                        $cell = 'TA';
                                        $bg = 'background:#FFB6C1;';
                                    }
                                }
                            }
                        @endphp
                        <td style="border:1px solid #000; font-size:9px; text-align:center; {{ $bg }}">
                            {{ $cell }}
                        </td>
                    @endforeach
                    @for($k = count($chunk); $k < 11; $k++)
                        <td style="border:1px solid #000;"></td>
                    @endfor
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
