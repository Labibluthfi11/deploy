{{-- resources/views/exports/bulk_simple.blade.php --}}

<table>
    {{-- ======================= --}}
    {{-- HEADER UTAMA --}}
    {{-- ======================= --}}
    <thead>
        <tr>
            <td colspan="17" style="font-weight:bold; font-size:16px; text-align:center; height:40px; vertical-align:middle; background:#FFFF00; border:1px solid #000;">
                ABSENSI {{ strtoupper($categoryLabel ?? 'KARYAWAN') }}
            </td>
        </tr>
        <tr>
            <td colspan="17" style="font-weight:bold; text-align:center; background:#FFFF00; border:1px solid #000;">
                {{ strtoupper(\Carbon\Carbon::parse($allDates[0])->translatedFormat('F Y')) }}
            </td>
        </tr>
        <tr>
            <td colspan="17" style="text-align:center; border:1px solid #000;">
                {{ $periodeStr ?? '' }}
            </td>
        </tr>
        <tr><td colspan="17"></td></tr>

        {{-- HEADER KOLOM --}}
        <tr style="background:#D9D9D9;">
            <th style="border:1px solid #000; width:50px;">NO</th>
            <th colspan="11" style="border:1px solid #000; text-align:center;">Tanggal Dan Bulan</th>
            <th style="border:1px solid #000; width:45px;">Telat</th>
            <th style="border:1px solid #000; width:45px;">Izin</th>
            <th style="border:1px solid #000; width:45px;">Sakit</th>
            <th style="border:1px solid #000; width:45px;">Alpha</th>
            <th style="border:1px solid #000; width:60px;">Lembur</th>
        </tr>
    </thead>

    {{-- ======================= --}}
    {{-- BODY --}}
    {{-- ======================= --}}
    <tbody>
        @foreach($users as $userIndex => $user)
            @php
                $userAbsensi = $absensiData->where('user_id', $user->id);

                // Hitung total telat, izin, sakit, alpha & lembur UNTUK SEMUA TANGGAL
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
                        // Alpha = tidak ada absensi & bukan weekend & bukan hari libur
                        if (!\Carbon\Carbon::parse($date)->isWeekend() && !\App\Models\Holiday::isHoliday($date)) {
                            $totalAlpha++;
                        }
                    }
                }

                $lemburJam = floor($totalMenitLembur / 60);
                $lemburMenit = $totalMenitLembur % 60;
                $totalLemburStr = $totalMenitLembur ? "{$lemburJam}j {$lemburMenit}m" : '-';

                // Rowspan = 1 (nama) + jumlah chunk * 2 (header tanggal + data)
                $rowSpan = 1 + (count($dateChunks) * 2);
            @endphp

            {{-- ROW NAMA USER (TENGAH ATAS) --}}
            <tr>
                <td rowspan="{{ $rowSpan }}" style="border:1px solid #000; vertical-align:top; text-align:center; font-weight:bold;">
                    {{ $userIndex + 1 }}
                </td>
                <td colspan="11" style="border:1px solid #000; font-weight:bold; padding:5px; text-align:center; background:#f9f9f9;">
                    {{ strtoupper($user->name) }}
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
                {{-- ROW HEADER TANGGAL --}}
                <tr>
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
                                    if (strtolower($absen->tipe ?? '') === 'sakit') {
                                        $cell .= " (Hadir & Sakit)";
                                    }
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
                                    if (\App\Models\Holiday::isHoliday($date)) {
                                        $holiday = \App\Models\Holiday::where('holiday_date', \Carbon\Carbon::parse($date)->toDateString())->first();
                                        $cell = 'Lbr: ' . ($holiday->name ?? 'Hari Libur');
                                        $bg = 'background:#D3D3D3;';
                                    } else {
                                        $cell = 'Tidak Masuk';
                                        $bg = 'background:#FFB6C1;';
                                    }
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
