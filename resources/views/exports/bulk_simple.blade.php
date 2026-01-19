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

            {{-- HEADER TABEL --}}
            <tr style="background-color: #D9D9D9;">
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 50px;">NO</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 200px;">Nama</th>
                @for($i = 1; $i <= 11; $i++)
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">
                        Tanggal {{ $i }}
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

                    // Hitung total telat & lembur UNTUK SELURUH BULAN INI
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

                    // Format total lembur
                    $lemburJam = floor($totalMenitLembur / 60);
                    $lemburMenit = $totalMenitLembur % 60;
                    $totalLemburStr = $totalMenitLembur > 0 ? sprintf('%dj %dm', $lemburJam, $lemburMenit) : '-';
                @endphp

                {{-- LOOP SETIAP CHUNK TANGGAL UNTUK USER INI --}}
                @foreach($dateChunks as $chunkIndex => $chunk)
                    <tr>
                        {{-- NO & NAMA (hanya di baris pertama) --}}
                        @if($chunkIndex === 0)
                            <td rowspan="{{ count($dateChunks) }}" style="text-align: center; border: 1px solid #000000; vertical-align: middle; font-weight: bold;">{{ $no }}</td>
                            <td rowspan="{{ count($dateChunks) }}" style="text-align: left; border: 1px solid #000000; padding-left: 5px; vertical-align: middle; font-weight: bold;">{{ $user->name }}</td>
                        @endif

                        {{-- LOOP 11 TANGGAL (atau sisa tanggal) --}}
                        @for($i = 0; $i < 11; $i++)
                            @php
                                if (isset($chunk[$i])) {
                                    $date = $chunk[$i];

                                    // Cari absensi di tanggal ini
                                    $absen = $userAbsensi->first(function($item) use ($date) {
                                        return \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                                    });

                                    $cellContent = $date->format('d-M') . "\n";
                                    $cellStyle = 'text-align: center; border: 1px solid #000000; vertical-align: middle; font-size: 10px;';

                                    if ($absen) {
                                        // Ada absensi
                                        $checkIn = \Carbon\Carbon::parse($absen->check_in_at)->format('H:i');
                                        $checkOut = $absen->check_out_at ? \Carbon\Carbon::parse($absen->check_out_at)->format('H:i') : '-';

                                        if (strtolower($absen->status) === 'hadir') {
                                            $cellContent .= "$checkIn - $checkOut";

                                            // TELAT INFO
                                            $telatMenit = $absen->late_minutes ?? 0;
                                            if ($telatMenit > 0) {
                                                $telatJam = floor($telatMenit / 60);
                                                $telatSisa = $telatMenit % 60;
                                                if ($telatJam > 0) {
                                                    $telatStr = sprintf('T:%dj%dm', $telatJam, $telatSisa);
                                                } else {
                                                    $telatStr = sprintf('T:%dm', $telatSisa);
                                                }
                                                $cellContent .= "\n$telatStr";
                                            }

                                            // LEMBUR INFO
                                            $lemburMenit = $absen->overtime_minutes ?? 0;
                                            if ($lemburMenit > 0) {
                                                $lemburJam = floor($lemburMenit / 60);
                                                $lemburSisa = $lemburMenit % 60;
                                                $lemburStr = sprintf('L:%dj%dm', $lemburJam, $lemburSisa);
                                                $cellContent .= "\n$lemburStr";
                                            }
                                        } elseif (strtolower($absen->status) === 'izin') {
                                            $cellContent .= 'Izin';
                                        } elseif (strtolower($absen->status) === 'sakit') {
                                            $cellContent .= 'Sakit';
                                        } else {
                                            $cellContent .= ucfirst($absen->status);
                                        }
                                    } else {
                                        // Ga ada absensi
                                        $isWeekday = $date->dayOfWeek >= 1 && $date->dayOfWeek <= 5;

                                        if ($isWeekday) {
                                            $cellContent .= 'Tidak Masuk';
                                            $cellStyle = 'text-align: center; border: 1px solid #000000; background-color: #FFB6C1; vertical-align: middle; font-size: 10px;';
                                        } else {
                                            $cellContent .= '-';
                                        }
                                    }
                                } else {
                                    // Ga ada tanggal lagi (sisa chunk)
                                    $cellContent = '';
                                    $cellStyle = 'text-align: center; border: 1px solid #000000; background-color: #F0F0F0;';
                                }
                            @endphp

                            <td style="{{ $cellStyle }}">{{ $cellContent }}</td>
                        @endfor

                        {{-- TOTAL TELAT & LEMBUR (hanya di baris pertama) --}}
                        @if($chunkIndex === 0)
                            <td rowspan="{{ count($dateChunks) }}" style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: middle;">
                                {{ $totalTelat > 0 ? $totalTelat . 'x' : '-' }}
                            </td>
                            <td rowspan="{{ count($dateChunks) }}" style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: middle;">
                                {{ $totalLemburStr }}
                            </td>
                        @endif
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
