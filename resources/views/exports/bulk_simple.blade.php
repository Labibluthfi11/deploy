{{-- resources/views/exports/bulk_simple.blade.php --}}

@foreach($dateGroups as $groupIndex => $group)
    <table>
        {{-- HEADER KUNING --}}
        <thead>
            <tr>
                <td colspan="15" style="font-weight: bold; font-size: 16px; text-align: center; height: 40px; vertical-align: middle; background-color: #FFFF00; border: 1px solid #000000;">
                    ABSENSI {{ strtoupper($categoryLabel) }}
                </td>
            </tr>
            <tr>
                <td colspan="15" style="font-weight: bold; text-align: center; background-color: #FFFF00; border: 1px solid #000000;">
                    {{ $group['month_label'] }} - {{ $group['week_label'] }}
                </td>
            </tr>
            <tr>
                <td colspan="15" style="text-align: center; border: 1px solid #000000;">
                    {{ $periodeStr }}
                </td>
            </tr>
            <tr>
                <td colspan="15"></td> {{-- Spasi --}}
            </tr>

            {{-- HEADER TABEL - CUMA SEKALI --}}
            <tr style="background-color: #D9D9D9;">
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 50px;">NO</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 200px;">Nama</th>
                @for($i = 1; $i <= 11; $i++)
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">
                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}-Jan
                    </th>
                @endfor
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Total Telat</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">Total Lembur</th>
            </tr>
        </thead>

        {{-- BODY TABEL --}}
        <tbody>
            @php $no = 1; @endphp
            @foreach($users as $user)
                @php
                    // Ambil absensi user ini
                    $userAbsensi = $absensiData->where('user_id', $user->id);

                    // Split tanggal jadi chunk 11 hari
                    $dateChunks = array_chunk($group['dates'], 11);

                    // Hitung total telat & lembur
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

                    // Total baris = 2 baris per chunk (tanggal + data)
                    $totalRows = count($dateChunks) * 2;
                @endphp

                {{-- LOOP SETIAP CHUNK --}}
                @foreach($dateChunks as $chunkIndex => $chunk)
                    {{-- BARIS TANGGAL --}}
                    <tr>
                        {{-- NO & NAMA di rowspan (cuma di chunk pertama) --}}
                        @if($chunkIndex === 0)
                            <td rowspan="{{ $totalRows }}" style="text-align: center; border: 1px solid #000000; vertical-align: middle; font-weight: bold;">{{ $no }}</td>
                            <td rowspan="{{ $totalRows }}" style="text-align: left; border: 1px solid #000000; padding-left: 5px; vertical-align: middle; font-weight: bold;">{{ $user->name }}</td>
                        @endif

                        {{-- ISI TANGGAL (01-Jan, 02-Jan, dst) --}}
                        @for($i = 0; $i < 11; $i++)
                            @php
                                if (isset($chunk[$i])) {
                                    $date = $chunk[$i];
                                    $cellContent = $date->format('d-M');
                                } else {
                                    $cellContent = '';
                                }
                            @endphp
                            <td style="text-align: center; border: 1px solid #000000; font-size: 10px;">{{ $cellContent }}</td>
                        @endfor

                        {{-- TOTAL (rowspan, cuma di chunk pertama) --}}
                        @if($chunkIndex === 0)
                            <td rowspan="{{ $totalRows }}" style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: middle;">
                                {{ $totalTelat > 0 ? $totalTelat . 'x' : '-' }}
                            </td>
                            <td rowspan="{{ $totalRows }}" style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: middle;">
                                {{ $totalLemburStr }}
                            </td>
                        @endif
                    </tr>

                    {{-- BARIS DATA --}}
                    <tr>
                        {{-- ISI DATA ABSENSI --}}
                        @for($i = 0; $i < 11; $i++)
                            @php
                                if (isset($chunk[$i])) {
                                    $date = $chunk[$i];

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

                                            // Lembur
                                            $lemburMenit = $absen->overtime_minutes ?? 0;
                                            if ($lemburMenit > 0) {
                                                $lemburJam = floor($lemburMenit / 60);
                                                $lemburSisa = $lemburMenit % 60;
                                                $lemburStr = sprintf('Lembur: %dj %dm', $lemburJam, $lemburSisa);
                                                $cellContent .= " $lemburStr";
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
                                } else {
                                    $cellContent = '';
                                    $cellStyle = 'text-align: center; border: 1px solid #000000; background-color: #F0F0F0;';
                                }
                            @endphp

                            <td style="{{ $cellStyle }}">{{ $cellContent }}</td>
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
            <tr><td colspan="50" style="height: 30px;"></td></tr>
        </table>
    @endif
@endforeach
