{{-- resources/views/exports/bulk_simple.blade.php --}}

@foreach($dateGroups as $groupIndex => $group)
    @php
        $totalDays = count($group['dates']);
        $chunks = array_chunk($group['dates'], 11);
        $totalCols = $totalDays + 4; // tanggal + NO + Nama + Total Telat + Total Lembur
    @endphp

    <table>
        {{-- HEADER KUNING --}}
        <thead>
            <tr>
                <td colspan="{{ $totalCols }}" style="font-weight: bold; font-size: 16px; text-align: center; height: 40px; vertical-align: middle; background-color: #FFFF00; border: 1px solid #000000;">
                    ABSENSI {{ strtoupper($categoryLabel) }}
                </td>
            </tr>
            <tr>
                <td colspan="{{ $totalCols }}" style="font-weight: bold; text-align: center; background-color: #FFFF00; border: 1px solid #000000;">
                    {{ $group['month_label'] }}
                </td>
            </tr>
            <tr>
                <td colspan="{{ $totalCols }}" style="text-align: center; border: 1px solid #000000;">
                    {{ $periodeStr }}
                </td>
            </tr>
            <tr>
                <td colspan="{{ $totalCols }}"></td>
            </tr>

            {{-- HEADER TABEL --}}
            <tr style="background-color: #D9D9D9;">
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 50px;">NO</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 200px;">Nama</th>
                @foreach($group['dates'] as $date)
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">
                        {{ $date->format('d-M') }}
                    </th>
                @endforeach
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Total Telat</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">Total Lembur</th>
            </tr>
        </thead>

        {{-- BODY TABEL --}}
        <tbody>
            @php $no = 1; @endphp
            @foreach($users as $user)
                @php
                    $userAbsensi = $absensiData->where('user_id', $user->id);
                    $dateChunks = array_chunk($group['dates'], 11);

                    $totalTelat = 0;
                    $totalMenitLembur = 0;

                    foreach ($group['dates'] as $date) {
                        $absen = $userAbsensi->first(function($item) use ($date) {
                            return \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                        });

                        if ($absen) {
                            if (($absen->late_minutes ?? 0) > 0) {
                                $totalTelat++;
                            }
                            $totalMenitLembur += $absen->overtime_minutes ?? 0;
                        }
                    }

                    $lemburJam = floor($totalMenitLembur / 60);
                    $lemburMenit = $totalMenitLembur % 60;
                    $totalLemburStr = $totalMenitLembur > 0 ? sprintf('%dj %dm', $lemburJam, $lemburMenit) : '-';

                    $totalRows = count($dateChunks) * 2;
                @endphp

                {{-- LOOP SETIAP CHUNK --}}
                @foreach($dateChunks as $chunkIndex => $chunk)
                    {{-- BARIS TANGGAL --}}
                    <tr>
                        @if($chunkIndex === 0)
                            <td rowspan="{{ $totalRows }}" style="text-align: center; border: 1px solid #000000; vertical-align: middle; font-weight: bold;">{{ $no }}</td>
                            <td rowspan="{{ $totalRows }}" style="text-align: left; border: 1px solid #000000; padding-left: 5px; vertical-align: middle; font-weight: bold;">{{ $user->name }}</td>
                        @endif

                        @foreach($chunk as $date)
                            <td style="text-align: center; border: 1px solid #000000; font-size: 10px; font-weight: bold;">
                                {{ $date->format('d-M') }}
                            </td>
                        @endforeach

                        {{-- Padding kalo chunk kurang dari 11 --}}
                        @for($i = count($chunk); $i < 11; $i++)
                            <td style="border: 1px solid #000000; background-color: #F0F0F0;"></td>
                        @endfor

                        @if($chunkIndex === 0)
                            <td rowspan="{{ $totalRows }}" style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: middle;">
                                {{ $totalTelat > 0 ? $totalTelat : '-' }}
                            </td>
                            <td rowspan="{{ $totalRows }}" style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: middle;">
                                {{ $totalLemburStr }}
                            </td>
                        @endif
                    </tr>

                    {{-- BARIS DATA --}}
                    <tr>
                        @foreach($chunk as $date)
                            @php
                                $absen = $userAbsensi->first(function($item) use ($date) {
                                    return \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                                });

                                $cellContent = '';
                                $cellStyle = 'text-align: center; border: 1px solid #000000; vertical-align: middle; font-size: 10px;';

                                if ($absen) {
                                    $checkIn = \Carbon\Carbon::parse($absen->check_in_at)->format('H:i');
                                    $checkOut = $absen->check_out_at ? \Carbon\Carbon::parse($absen->check_out_at)->format('H:i') : '-';

                                    if (strtolower($absen->status) === 'hadir') {
                                        $cellContent = "$checkIn - $checkOut";

                                        $lemburMenit = $absen->overtime_minutes ?? 0;
                                        if ($lemburMenit > 0) {
                                            $lemburJam = floor($lemburMenit / 60);
                                            $lemburSisa = $lemburMenit % 60;

                                            if (($absen->late_minutes ?? 0) > 0) {
                                                $telatMenit = $absen->late_minutes;
                                                $cellContent .= " Telat: {$telatMenit}m";
                                            }

                                            $lemburStr = sprintf(' Lembur: %dj %dm', $lemburJam, $lemburSisa);
                                            $cellContent .= $lemburStr;
                                        } elseif (($absen->late_minutes ?? 0) > 0) {
                                            $telatMenit = $absen->late_minutes;
                                            $cellContent .= " Telat: {$telatMenit}m";
                                        }
                                    } elseif (strtolower($absen->status) === 'izin') {
                                        $cellContent = 'Izin';
                                    } elseif (strtolower($absen->status) === 'sakit') {
                                        $cellContent = 'Sakit';
                                    } else {
                                        $cellContent = ucfirst($absen->status);
                                    }
                                } else {
                                    $isWeekday = $date->dayOfWeek >= 1 && $date->dayOfWeek <= 5;

                                    if ($isWeekday) {
                                        $cellContent = 'Tidak Masuk';
                                        $cellStyle = 'text-align: center; border: 1px solid #000000; background-color: #FFB6C1; vertical-align: middle; font-size: 10px;';
                                    } else {
                                        $cellContent = '-';
                                    }
                                }
                            @endphp

                            <td style="{{ $cellStyle }}">{{ $cellContent }}</td>
                        @endforeach

                        {{-- Padding kalo chunk kurang dari 11 --}}
                        @for($i = count($chunk); $i < 11; $i++)
                            <td style="border: 1px solid #000000; background-color: #F0F0F0;"></td>
                        @endfor
                    </tr>
                @endforeach

                @php $no++; @endphp
            @endforeach
        </tbody>
    </table>

    {{-- Spasi antar tabel --}}
    @if($groupIndex < count($dateGroups) - 1)
        <table>
            <tr><td colspan="{{ $totalCols }}" style="height: 30px;"></td></tr>
        </table>
    @endif
@endforeach
