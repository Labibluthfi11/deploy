{{-- resources/views/exports/bulk_simple.blade.php --}}

@foreach($sections as $sectionIndex => $section)
    <table>
        {{-- HEADER KUNING --}}
        <thead>
            <tr>
                <td colspan="{{ count($users) + 2 }}" style="font-weight: bold; font-size: 16px; text-align: center; height: 40px; vertical-align: middle; background-color: #FFFF00; border: 1px solid #000000;">
                    ABSENSI {{ strtoupper($categoryLabel) }}
                </td>
            </tr>
            <tr>
                <td colspan="{{ count($users) + 2 }}" style="font-weight: bold; text-align: center; background-color: #FFFF00; border: 1px solid #000000;">
                    {{ strtoupper($section['month']) }} - Minggu Ke-{{ $section['week'] }}
                </td>
            </tr>
            <tr>
                <td colspan="{{ count($users) + 2 }}" style="text-align: center; border: 1px solid #000000;">
                    {{ \Carbon\Carbon::parse($section['dates'][0])->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse(end($section['dates']))->translatedFormat('d M Y') }}
                </td>
            </tr>
            <tr>
                <td colspan="{{ count($users) + 2 }}"></td> {{-- Spasi --}}
            </tr>

            {{-- HEADER TABEL --}}
            <tr style="background-color: #D9D9D9;">
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Tanggal</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 80px;">Hari</th>
                @foreach($users as $user)
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">
                        {{ $user->name }}
                    </th>
                @endforeach
            </tr>
        </thead>

        {{-- BODY TABEL --}}
        <tbody>
            @foreach($section['dates'] as $date)
                <tr>
                    {{-- TANGGAL --}}
                    <td style="text-align: center; border: 1px solid #000000; font-weight: bold;">
                        {{ $date->translatedFormat('d-M-Y') }}
                    </td>

                    {{-- HARI --}}
                    <td style="text-align: center; border: 1px solid #000000;">
                        {{ $date->translatedFormat('l') }}
                    </td>

                    {{-- LOOP PER USER --}}
                    @foreach($users as $user)
                        @php
                            // Cari absensi user di tanggal ini
                            $absen = $absensiData->first(function($item) use ($user, $date) {
                                return $item->user_id === $user->id && \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                            });

                            $cellContent = '-';
                            $cellStyle = 'text-align: center; border: 1px solid #000000; font-size: 10px;';

                            if ($absen) {
                                // Ada absensi
                                $checkIn = \Carbon\Carbon::parse($absen->check_in_at)->format('H:i');
                                $checkOut = $absen->check_out_at ? \Carbon\Carbon::parse($absen->check_out_at)->format('H:i') : '-';

                                if (strtolower($absen->status) === 'hadir') {
                                    $cellContent = "$checkIn - $checkOut";

                                    // 🔥 TAMBAH LEMBUR (KALO ADA)
                                    if ($absen->overtime_minutes > 0) {
                                        $overtimeHours = floor($absen->overtime_minutes / 60);
                                        $overtimeMinutes = $absen->overtime_minutes % 60;
                                        $cellContent .= "\nLembur: {$overtimeHours}j {$overtimeMinutes}m";
                                        $cellStyle = 'text-align: center; border: 1px solid #000000; font-size: 9px; background-color: #E7F3FF;'; // Biru muda
                                    }

                                    // 🔥 TAMBAH TELAT (KALO ADA)
                                    if (($absen->late_minutes ?? 0) > 0) {
                                        $cellContent .= "\n⏱ Telat: {$absen->late_minutes}m";
                                        $cellStyle = 'text-align: center; border: 1px solid #000000; font-size: 9px; background-color: #FFF3CD;'; // Kuning muda
                                    }

                                } elseif (strtolower($absen->status) === 'izin') {
                                    $cellContent = 'Izin';
                                    $cellStyle .= ' background-color: #D1ECF1;'; // Biru tosca
                                } elseif (strtolower($absen->status) === 'sakit') {
                                    $cellContent = 'Sakit';
                                    $cellStyle .= ' background-color: #F8D7DA;'; // Merah muda
                                } else {
                                    $cellContent = ucfirst($absen->status);
                                }
                            } else {
                                // Ga ada absensi
                                $isWeekday = $date->dayOfWeek >= 1 && $date->dayOfWeek <= 5;

                                if ($isWeekday) {
                                    // Hari kerja tapi ga masuk = KOSONG (highlight merah)
                                    $cellContent = 'Tidak Masuk';
                                    $cellStyle = 'text-align: center; border: 1px solid #000000; font-size: 9px; background-color: #FFCCCC; font-weight: bold;';
                                } else {
                                    // Weekend = strip biasa
                                    $cellContent = '-';
                                    $cellStyle .= ' background-color: #F0F0F0;'; // Abu-abu
                                }
                            }
                        @endphp

                        <td style="{{ $cellStyle }}">{!! nl2br($cellContent) !!}</td>
                    @endforeach
                </tr>
            @endforeach

            {{-- ROW TOTAL --}}
            <tr style="background-color: #FFF3CD; font-weight: bold;">
                <td colspan="2" style="text-align: center; border: 1px solid #000000;">TOTAL</td>
                @foreach($users as $user)
                    @php
                        // Hitung total di section ini
                        $totalTelat = 0;
                        $totalMenitLembur = 0;

                        foreach ($section['dates'] as $date) {
                            $absen = $absensiData->first(function($item) use ($user, $date) {
                                return $item->user_id === $user->id && \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                            });

                            if ($absen) {
                                if (($absen->late_minutes ?? 0) > 0) {
                                    $totalTelat++;
                                }
                                $totalMenitLembur += $absen->overtime_minutes ?? 0;
                            }
                        }

                        $overtimeHours = floor($totalMenitLembur / 60);
                        $overtimeMinutes = $totalMenitLembur % 60;
                    @endphp

                    <td style="text-align: center; border: 1px solid #000000; font-size: 9px;">
                        Telat: {{ $totalTelat }}x<br>
                        Lembur: {{ $overtimeHours }}j {{ $overtimeMinutes }}m
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>

    {{-- SPASI ANTAR SECTION --}}
    @if($sectionIndex < count($sections) - 1)
        <table><tr><td colspan="{{ count($users) + 2 }}" style="height: 30px;"></td></tr></table>
    @endif
@endforeach
