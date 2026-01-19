{{-- resources/views/exports/bulk_simple.blade.php --}}

@foreach($dateGroups as $groupIndex => $group)
    <table>
        {{-- HEADER KUNING --}}
        <thead>
            <tr>
                <td colspan="{{ count($users) + 3 }}" style="font-weight: bold; font-size: 16px; text-align: center; height: 40px; vertical-align: middle; background-color: #FFFF00; border: 1px solid #000000;">
                    ABSENSI {{ strtoupper($categoryLabel) }}
                </td>
            </tr>
            <tr>
                <td colspan="{{ count($users) + 3 }}" style="font-weight: bold; text-align: center; background-color: #FFFF00; border: 1px solid #000000;">
                    {{ $group['month_label'] }} - {{ $group['week_label'] }}
                </td>
            </tr>
            <tr>
                <td colspan="{{ count($users) + 3 }}" style="text-align: center; border: 1px solid #000000;">
                    {{ $periodeStr }}
                </td>
            </tr>
            <tr>
                <td colspan="{{ count($users) + 3 }}"></td> {{-- Spasi --}}
            </tr>

            {{-- HEADER TABEL --}}
            <tr style="background-color: #D9D9D9;">
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Tanggal</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Hari</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">Total Telat Per Hari</th>
                @foreach($users as $user)
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 150px;">
                        {{ $user->name }}
                    </th>
                @endforeach
            </tr>
        </thead>

        {{-- BODY TABEL --}}
        <tbody>
            @foreach($group['dates'] as $date)
                @php
                    // Data untuk baris ini
                    $dayName = $date->translatedFormat('l'); // Monday, Tuesday, dst
                    $dateStr = $date->format('d-M-Y');
                    $isWeekday = $date->dayOfWeek >= 1 && $date->dayOfWeek <= 5;

                    // Hitung total telat untuk HARI ini (semua user)
                    $totalTelatHari = 0;

                    foreach ($users as $user) {
                        $absen = $absensiData->first(function($item) use ($user, $date) {
                            return $item->user_id == $user->id &&
                                   \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                        });

                        if ($absen && ($absen->late_minutes ?? 0) > 0) {
                            $totalTelatHari++;
                        }
                    }
                @endphp

                <tr>
                    {{-- TANGGAL --}}
                    <td style="text-align: center; border: 1px solid #000000;">{{ $dateStr }}</td>

                    {{-- HARI --}}
                    <td style="text-align: center; border: 1px solid #000000;">{{ $dayName }}</td>

                    {{-- TOTAL TELAT HARI INI --}}
                    <td style="text-align: center; border: 1px solid #000000;">{{ $totalTelatHari > 0 ? $totalTelatHari : '-' }}</td>

                    {{-- LOOP SEMUA USER --}}
                    @foreach($users as $user)
                        @php
                            // Cari absensi user ini di tanggal ini
                            $absen = $absensiData->first(function($item) use ($user, $date) {
                                return $item->user_id == $user->id &&
                                       \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                            });

                            $cellContent = '-';
                            $cellStyle = 'text-align: center; border: 1px solid #000000; vertical-align: middle;';

                            if ($absen) {
                                // Ada absensi
                                $checkIn = \Carbon\Carbon::parse($absen->check_in_at)->format('H:i');
                                $checkOut = $absen->check_out_at ? \Carbon\Carbon::parse($absen->check_out_at)->format('H:i') : '-';

                                if (strtolower($absen->status) === 'hadir') {
                                    $cellContent = "$checkIn - $checkOut";

                                    // 🔥 TAMPILIN LEMBUR PER USER
                                    $lemburMenit = $absen->overtime_minutes ?? 0;
                                    if ($lemburMenit > 0) {
                                        $lemburJam = floor($lemburMenit / 60);
                                        $lemburSisa = $lemburMenit % 60;
                                        $lemburStr = sprintf('%dj %dm', $lemburJam, $lemburSisa);
                                        $cellContent .= "\nLembur: $lemburStr";
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
                                if ($isWeekday) {
                                    // Hari kerja tapi ga masuk = TIDAK MASUK (background merah muda)
                                    $cellContent = 'Tidak Masuk';
                                    $cellStyle = 'text-align: center; border: 1px solid #000000; background-color: #FFB6C1; vertical-align: middle;';
                                } else {
                                    // Weekend = strip biasa
                                    $cellContent = '-';
                                }
                            }
                        @endphp

                        <td style="{{ $cellStyle }}">{{ $cellContent }}</td>
                    @endforeach
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
