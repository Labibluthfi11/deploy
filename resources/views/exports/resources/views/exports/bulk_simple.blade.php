{{-- resources/views/exports/bulk_simple.blade.php --}}
<table>
    {{-- HEADER KUNING --}}
    <thead>
        <tr>
            <td colspan="{{ count($allDates) + 3 }}" style="font-weight: bold; font-size: 16px; text-align: center; height: 40px; vertical-align: middle; background-color: #FFFF00; border: 1px solid #000000;">
                ABSENSI {{ strtoupper($categoryLabel) }}
            </td>
        </tr>
        <tr>
            <td colspan="{{ count($allDates) + 3 }}" style="font-weight: bold; text-align: center; background-color: #FFFF00; border: 1px solid #000000;">
                Minggu Ke-1
            </td>
        </tr>
        <tr>
            <td colspan="{{ count($allDates) + 3 }}" style="text-align: center; border: 1px solid #000000;">
                {{ $periodeStr }}
            </td>
        </tr>
        <tr>
            <td colspan="{{ count($allDates) + 3 }}"></td> {{-- Spasi --}}
        </tr>

        {{-- HEADER TABEL --}}
        <tr style="background-color: #D9D9D9;">
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 50px;">NO</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 200px;">Nama</th>
            @foreach($allDates as $date)
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">
                    {{ $date->format('d-M') }}
                </th>
            @endforeach
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 80px;">Total Telat</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Total Menit Lembur</th>
        </tr>
    </thead>

    {{-- BODY TABEL --}}
    <tbody>
        @php $no = 1; @endphp
        @foreach($users as $user)
            @php
                // Ambil absensi user ini
                $userAbsensi = $absensiData->where('user_id', $user->id);

                // Hitung total telat & lembur
                $totalTelat = 0;
                $totalMenitLembur = 0;

                foreach ($allDates as $date) {
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
            @endphp

            <tr>
                {{-- NO --}}
                <td style="text-align: center; border: 1px solid #000000;">{{ $no++ }}</td>

                {{-- NAMA --}}
                <td style="text-align: left; border: 1px solid #000000; padding-left: 5px;">{{ $user->name }}</td>

                {{-- LOOP TANGGAL --}}
                @foreach($allDates as $date)
                    @php
                        // Cari absensi di tanggal ini
                        $absen = $userAbsensi->first(function($item) use ($date) {
                            return \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                        });

                        $cellContent = '-';
                        $cellStyle = 'text-align: center; border: 1px solid #000000;';

                        if ($absen) {
                            // Ada absensi
                            $checkIn = \Carbon\Carbon::parse($absen->check_in_at)->format('H:i');
                            $checkOut = $absen->check_out_at ? \Carbon\Carbon::parse($absen->check_out_at)->format('H:i') : '-';

                            if (strtolower($absen->status) === 'hadir') {
                                $cellContent = "$checkIn - $checkOut";
                            } elseif (strtolower($absen->status) === 'izin') {
                                $cellContent = 'Izin';
                            } elseif (strtolower($absen->status) === 'sakit') {
                                $cellContent = 'Sakit';
                            } else {
                                $cellContent = ucfirst($absen->status);
                            }
                        } else {
                            // Ga ada absensi
                            // Cek hari kerja (Senin-Jumat)
                            $isWeekday = $date->dayOfWeek >= 1 && $date->dayOfWeek <= 5;

                            if ($isWeekday) {
                                // Hari kerja tapi ga masuk = KOSONG (ga perlu warna)
                                $cellContent = '';
                            } else {
                                // Weekend = strip biasa
                                $cellContent = '-';
                            }
                        }
                    @endphp

                    <td style="{{ $cellStyle }}">{{ $cellContent }}</td>
                @endforeach

                {{-- TOTAL TELAT --}}
                <td style="text-align: center; border: 1px solid #000000; font-weight: bold;">
                    {{ $totalTelat }}
                </td>

                {{-- TOTAL MENIT LEMBUR --}}
                <td style="text-align: center; border: 1px solid #000000; font-weight: bold;">
                    {{ $totalMenitLembur }}'
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
