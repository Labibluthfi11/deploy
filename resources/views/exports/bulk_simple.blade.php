{{-- resources/views/exports/bulk_simple.blade.php --}}

@foreach($users as $userIndex => $user)
    @php
        // Ambil absensi user ini aja
        $userAbsensi = $absensiData->where('user_id', $user->id);
    @endphp

    @foreach($dateGroups as $groupIndex => $group)
        <table>
            {{-- HEADER KUNING --}}
            <thead>
                <tr>
                    <td colspan="6" style="font-weight: bold; font-size: 16px; text-align: center; height: 40px; vertical-align: middle; background-color: #FFFF00; border: 1px solid #000000;">
                        ABSENSI {{ strtoupper($categoryLabel) }}
                    </td>
                </tr>
                <tr>
                    <td colspan="6" style="font-weight: bold; text-align: center; background-color: #FFFF00; border: 1px solid #000000;">
                        {{ $group['month_label'] }} - {{ $group['week_label'] }}
                    </td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align: center; border: 1px solid #000000;">
                        {{ $periodeStr }}
                    </td>
                </tr>
                <tr>
                    <td colspan="6" style="font-weight: bold; text-align: center; background-color: #D9D9D9; border: 1px solid #000000; font-size: 14px;">
                        {{ $user->name }}
                    </td>
                </tr>
                <tr>
                    <td colspan="6"></td> {{-- Spasi --}}
                </tr>

                {{-- HEADER TABEL --}}
                <tr style="background-color: #D9D9D9;">
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Tanggal</th>
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Hari</th>
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 150px;">Jam Masuk - Keluar</th>
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Telat</th>
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Lembur</th>
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 150px;">Keterangan</th>
                </tr>
            </thead>

            {{-- BODY TABEL --}}
            <tbody>
                @php
                    $totalTelatChunk = 0;
                    $totalLemburChunk = 0;
                @endphp

                @foreach($group['dates'] as $date)
                    @php
                        // Data untuk baris ini
                        $dayName = $date->translatedFormat('l');
                        $dateStr = $date->format('d-M-Y');
                        $isWeekday = $date->dayOfWeek >= 1 && $date->dayOfWeek <= 5;

                        // Cari absensi user ini di tanggal ini
                        $absen = $userAbsensi->first(function($item) use ($date) {
                            return \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                        });

                        $jamMasukKeluar = '-';
                        $telatStr = '-';
                        $lemburStr = '-';
                        $keterangan = '-';
                        $rowStyle = '';

                        if ($absen) {
                            // Ada absensi
                            $checkIn = \Carbon\Carbon::parse($absen->check_in_at)->format('H:i');
                            $checkOut = $absen->check_out_at ? \Carbon\Carbon::parse($absen->check_out_at)->format('H:i') : '-';

                            if (strtolower($absen->status) === 'hadir') {
                                $jamMasukKeluar = "$checkIn - $checkOut";

                                // Hitung telat
                                $telatMenit = $absen->late_minutes ?? 0;
                                if ($telatMenit > 0) {
                                    $totalTelatChunk++;
                                    $telatJam = floor($telatMenit / 60);
                                    $telatSisa = $telatMenit % 60;
                                    if ($telatJam > 0) {
                                        $telatStr = sprintf('%dj %dm', $telatJam, $telatSisa);
                                    } else {
                                        $telatStr = sprintf('%dm', $telatSisa);
                                    }
                                } else {
                                    $telatStr = '-';
                                }

                                // Hitung lembur
                                $lemburMenit = $absen->overtime_minutes ?? 0;
                                if ($lemburMenit > 0) {
                                    $totalLemburChunk += $lemburMenit;
                                    $lemburJam = floor($lemburMenit / 60);
                                    $lemburSisa = $lemburMenit % 60;
                                    $lemburStr = sprintf('%dj %dm', $lemburJam, $lemburSisa);
                                } else {
                                    $lemburStr = '-';
                                }

                                $keterangan = 'Hadir';
                            } elseif (strtolower($absen->status) === 'izin') {
                                $keterangan = 'Izin';
                            } elseif (strtolower($absen->status) === 'sakit') {
                                $keterangan = 'Sakit';
                            } else {
                                $keterangan = ucfirst($absen->status);
                            }
                        } else {
                            // Ga ada absensi
                            if ($isWeekday) {
                                // Hari kerja tapi ga masuk
                                $keterangan = 'Tidak Masuk';
                                $rowStyle = 'background-color: #FFB6C1;';
                            } else {
                                // Weekend
                                $keterangan = 'Libur';
                            }
                        }
                    @endphp

                    <tr style="{{ $rowStyle }}">
                        <td style="text-align: center; border: 1px solid #000000;">{{ $dateStr }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ $dayName }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ $jamMasukKeluar }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ $telatStr }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ $lemburStr }}</td>
                        <td style="text-align: center; border: 1px solid #000000;">{{ $keterangan }}</td>
                    </tr>
                @endforeach

                {{-- SUMMARY ROW --}}
                @php
                    $lemburJamTotal = floor($totalLemburChunk / 60);
                    $lemburMenitTotal = $totalLemburChunk % 60;
                    $lemburTotalStr = $totalLemburChunk > 0 ? sprintf('%dj %dm', $lemburJamTotal, $lemburMenitTotal) : '-';
                @endphp
                <tr style="background-color: #FFFFCC;">
                    <td colspan="3" style="font-weight: bold; text-align: center; border: 1px solid #000000;">TOTAL</td>
                    <td style="font-weight: bold; text-align: center; border: 1px solid #000000;">{{ $totalTelatChunk }}x</td>
                    <td style="font-weight: bold; text-align: center; border: 1px solid #000000;">{{ $lemburTotalStr }}</td>
                    <td style="text-align: center; border: 1px solid #000000;">-</td>
                </tr>
            </tbody>
        </table>

        {{-- Spasi antar chunk --}}
        @if($groupIndex < count($dateGroups) - 1)
            <table>
                <tr><td colspan="10" style="height: 30px;"></td></tr>
            </table>
        @endif
    @endforeach

    {{-- Page break antar user --}}
    @if($userIndex < count($users) - 1)
        <table>
            <tr><td colspan="10" style="height: 50px; page-break-after: always;"></td></tr>
        </table>
    @endif
@endforeach
