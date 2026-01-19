{{-- resources/views/exports/bulk_simple.blade.php --}}

@foreach($dateGroups as $groupIndex => $group)
    <table>
        {{-- HEADER KUNING --}}
        <thead>
            <tr>
                <td colspan="{{ count($group['dates']) + 4 }}" style="font-weight: bold; font-size: 16px; text-align: center; height: 40px; vertical-align: middle; background-color: #FFFF00; border: 1px solid #000000;">
                    ABSENSI {{ strtoupper($categoryLabel) }}
                </td>
            </tr>
            <tr>
                <td colspan="{{ count($group['dates']) + 4 }}" style="font-weight: bold; text-align: center; background-color: #FFFF00; border: 1px solid #000000;">
                    {{ $group['month_label'] }} - {{ $group['week_label'] }}
                </td>
            </tr>
            <tr>
                <td colspan="{{ count($group['dates']) + 4 }}" style="text-align: center; border: 1px solid #000000;">
                    {{ $periodeStr }}
                </td>
            </tr>
            <tr>
                <td colspan="{{ count($group['dates']) + 4 }}"></td> {{-- Spasi --}}
            </tr>

            {{-- HEADER TABEL --}}
            <tr style="background-color: #D9D9D9;">
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 50px;">NO</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 200px;">Nama</th>
                @foreach($group['dates'] as $date)
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">
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
                    // Ambil absensi user ini
                    $userAbsensi = $absensiData->where('user_id', $user->id);

                    // Hitung total telat & lembur UNTUK CHUNK INI
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

                <tr>
                    {{-- NO --}}
                    <td style="text-align: center; border: 1px solid #000000; vertical-align: middle;">{{ $no++ }}</td>

                    {{-- NAMA --}}
                    <td style="text-align: left; border: 1px solid #000000; padding-left: 5px; vertical-align: middle;">{{ $user->name }}</td>

                    {{-- LOOP TANGGAL --}}
                    @foreach($group['dates'] as $date)
                        @php
                            // Cari absensi di tanggal ini
                            $absen = $userAbsensi->first(function($item) use ($date) {
                                return \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                            });

                            $cellContent = '-';
                            $cellStyle = 'text-align: center; border: 1px solid #000000; vertical-align: middle; font-size: 10px;';

                            if ($absen) {
                                // Ada absensi
                                $checkIn = \Carbon\Carbon::parse($absen->check_in_at)->format('H:i');
                                $checkOut = $absen->check_out_at ? \Carbon\Carbon::parse($absen->check_out_at)->format('H:i') : '-';

                                if (strtolower($absen->status) === 'hadir') {
                                    $cellContent = "$checkIn - $checkOut";

                                    // 🔥 TELAT INFO
                                    $telatMenit = $absen->late_minutes ?? 0;
                                    if ($telatMenit > 0) {
                                        $telatJam = floor($telatMenit / 60);
                                        $telatSisa = $telatMenit % 60;
                                        if ($telatJam > 0) {
                                            $telatStr = sprintf('Telat: %dj %dm', $telatJam, $telatSisa);
                                        } else {
                                            $telatStr = sprintf('Telat: %dm', $telatSisa);
                                        }
                                        $cellContent .= "\n$telatStr";
                                    }

                                    // 🔥 LEMBUR INFO
                                    $lemburMenit = $absen->overtime_minutes ?? 0;
                                    if ($lemburMenit > 0) {
                                        $lemburJam = floor($lemburMenit / 60);
                                        $lemburSisa = $lemburMenit % 60;
                                        $lemburStr = sprintf('Lembur: %dj %dm', $lemburJam, $lemburSisa);
                                        $cellContent .= "\n$lemburStr";
                                    }
                                } elseif (strtolower($absen->status) === 'izin') {
                                    $cellContent = 'Izin';
                                } elseif (strtolower($absen->status) === 'sakit') {
                                    $cellContent = 'Sakit';
                                } else {
                                    $cellContent = ucfirst($absen->status);
                                }
                            } else {
                                // Ga ada absensi
                                $isWeekday = $date->dayOfWeek >= 1 && $date->dayOfWeek <= 5;

                                if ($isWeekday) {
                                    // Hari kerja tapi ga masuk
                                    $cellContent = 'Tidak Masuk';
                                    $cellStyle = 'text-align: center; border: 1px solid #000000; background-color: #FFB6C1; vertical-align: middle; font-size: 10px;';
                                } else {
                                    // Weekend
                                    $cellContent = '-';
                                }
                            }
                        @endphp

                        <td style="{{ $cellStyle }}">{{ $cellContent }}</td>
                    @endforeach

                    {{-- TOTAL TELAT --}}
                    <td style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: middle;">
                        {{ $totalTelat > 0 ? $totalTelat . 'x' : '-' }}
                    </td>

                    {{-- TOTAL LEMBUR --}}
                    <td style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: middle;">
                        {{ $totalLemburStr }}
                    </td>
                </tr>
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
