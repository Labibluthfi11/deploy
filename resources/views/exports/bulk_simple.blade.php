{{-- resources/views/exports/bulk_simple.blade.php --}}

@foreach($dateGroups as $groupIndex => $group)
    <table>
        {{-- HEADER JUDUL (KUNING) --}}
        <thead>
            <tr>
                <td colspan="15" style="font-weight: bold; font-size: 16px; text-align: center; height: 40px; vertical-align: middle; background-color: #FFFF00; border: 1px solid #000000;">
                    ABSENSI {{ strtoupper($categoryLabel ?? 'KARYAWAN') }}
                </td>
            </tr>
            <tr>
                <td colspan="15" style="font-weight: bold; text-align: center; background-color: #FFFF00; border: 1px solid #000000;">
                    {{ $group['month_label'] }}
                </td>
            </tr>
            <tr>
                <td colspan="15" style="text-align: center; border: 1px solid #000000;">
                    {{ $periodeStr ?? '' }}
                </td>
            </tr>
            <tr><td colspan="15"></td></tr> {{-- Spasi Kosong --}}

            {{-- HEADER KOLOM UTAMA (NO, NAMA, TANGGAL 1-11, TOTAL) --}}
            {{-- Ini cuma buat Header baris pertama (tanggal 1-11) --}}
            <tr style="background-color: #D9D9D9;">
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 50px; vertical-align: middle;">NO</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 200px; vertical-align: middle;">Nama</th>

                @php
                    $allDates = $group['dates'];
                    // Ambil 11 tanggal pertama buat header utama
                    $first11 = array_slice($allDates, 0, 11);
                @endphp

                @foreach($first11 as $date)
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">
                        {{ \Carbon\Carbon::parse($date)->format('d-M') }}
                    </th>
                @endforeach

                {{-- Padding header kalau < 11 hari (jarang terjadi tapi buat jaga2) --}}
                @for($i = count($first11); $i < 11; $i++)
                    <th style="border: 1px solid #000000; background-color: #D9D9D9;"></th>
                @endfor

                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px; vertical-align: middle;">Total Telat</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px; vertical-align: middle;">Total Lembur</th>
            </tr>
        </thead>

        {{-- BODY DATA --}}
        <tbody>
            @php $no = 1; @endphp
            @foreach($users as $user)
                @php
                    // SIAPKAN DATA
                    $userAbsensi = $absensiData->where('user_id', $user->id);
                    // Potong tanggal jadi per 11 biji (1-11, 12-22, 23-31)
                    $dateChunks = array_chunk($allDates, 11);

                    // HITUNG TOTAL DULU
                    $totalTelat = 0;
                    $totalMenitLembur = 0;

                    foreach ($allDates as $date) {
                        $absen = $userAbsensi->first(function($item) use ($date) {
                            return \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                        });
                        if ($absen) {
                            if (($absen->late_minutes ?? 0) > 0) $totalTelat++;
                            $totalMenitLembur += $absen->overtime_minutes ?? 0;
                        }
                    }
                    $lemburJam = floor($totalMenitLembur / 60);
                    $lemburMenit = $totalMenitLembur % 60;
                    $totalLemburStr = $totalMenitLembur > 0 ? sprintf('%dj %dm', $lemburJam, $lemburMenit) : '-';

                    // HITUNG ROWSPAN
                    // Chunk pertama = 1 baris (Data)
                    // Chunk selanjutnya = 2 baris (Header Tanggal + Data)
                    // Rumus: 1 + ((JumlahChunk - 1) * 2)
                    $totalChunks = count($dateChunks);
                    $rowSpan = 1 + (($totalChunks - 1) * 2);
                @endphp

                @foreach($dateChunks as $chunkIndex => $chunk)

                    {{-- == LOGIC HEADER SELIPAN (TANGGAL 12 KE ATAS) == --}}
                    @if($chunkIndex > 0)
                        <tr>
                            {{-- Perhatikan: Tidak ada <td> Nama/No disini karena sudah ke-cover Rowspan dari atas --}}
                            @foreach($chunk as $date)
                                <td style="font-weight: bold; text-align: center; border: 1px solid #000000; background-color: #f2f2f2;">
                                    {{ \Carbon\Carbon::parse($date)->format('d-M') }}
                                </td>
                            @endforeach

                            {{-- Isi kotak kosong sisa header (biar excel ga geser) --}}
                            @for($k = count($chunk); $k < 11; $k++)
                                <td style="border: 1px solid #000000; background-color: #eaeaea;"></td>
                            @endfor

                            {{-- Tidak ada <td> Total disini karena sudah ke-cover Rowspan --}}
                        </tr>
                    @endif

                    {{-- == BARIS DATA == --}}
                    <tr>
                        {{-- KOLOM KIRI (NO & NAMA) - Cuma diprint pas chunk pertama --}}
                        @if($chunkIndex === 0)
                            <td rowspan="{{ $rowSpan }}" style="text-align: center; border: 1px solid #000000; vertical-align: top; font-weight: bold;">{{ $no }}</td>
                            <td rowspan="{{ $rowSpan }}" style="text-align: left; border: 1px solid #000000; vertical-align: top; font-weight: bold; padding-left: 5px;">{{ $user->name }}</td>
                        @endif

                        {{-- ISI DATA ABSEN --}}
                        @foreach($chunk as $date)
                            @php
                                $absen = $userAbsensi->first(function($item) use ($date) {
                                    return \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                                });

                                $cellContent = '';
                                $bgStyle = '';
                                $isWeekend = \Carbon\Carbon::parse($date)->isWeekend();

                                if ($absen) {
                                    $in = \Carbon\Carbon::parse($absen->check_in_at)->format('H:i');
                                    $out = $absen->check_out_at ? \Carbon\Carbon::parse($absen->check_out_at)->format('H:i') : '-';
                                    $status = strtolower($absen->status);

                                    if ($status == 'hadir') {
                                        $cellContent = "$in - $out";
                                        // Info tambahan (optional, sesuaikan kebutuhan)
                                        if (($absen->late_minutes ?? 0) > 0) $cellContent .= " (Telat)";
                                        if (($absen->overtime_minutes ?? 0) > 0) {
                                            $jam = floor($absen->overtime_minutes / 60);
                                            $cellContent .= " (L:{$jam}j)";
                                        }
                                    } elseif ($status == 'izin' || $status == 'sakit') {
                                        $cellContent = ucfirst($status);
                                        $bgStyle = 'background-color: #FFFACD;';
                                    } else {
                                        $cellContent = ucfirst($status);
                                    }
                                } else {
                                    if (!$isWeekend) {
                                        $cellContent = 'Tidak Masuk';
                                        $bgStyle = 'background-color: #FFB6C1;';
                                    } else {
                                        $cellContent = '-';
                                    }
                                }
                            @endphp

                            <td style="text-align: center; border: 1px solid #000000; font-size: 10px; vertical-align: middle; {{ $bgStyle }}">
                                {{ $cellContent }}
                            </td>
                        @endforeach

                        {{-- Isi kotak kosong sisa data (biar layout kotak sempurna) --}}
                        @for($k = count($chunk); $k < 11; $k++)
                            <td style="border: 1px solid #000000; background-color: #eaeaea;"></td>
                        @endfor

                        {{-- KOLOM KANAN (TOTAL) - Cuma diprint pas chunk pertama --}}
                        @if($chunkIndex === 0)
                            <td rowspan="{{ $rowSpan }}" style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: top;">
                                {{ $totalTelat > 0 ? $totalTelat : '-' }}
                            </td>
                            <td rowspan="{{ $rowSpan }}" style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: top;">
                                {{ $totalLemburStr }}
                            </td>
                        @endif
                    </tr>

                @endforeach {{-- End Loop Chunk --}}

                @php $no++; @endphp
            @endforeach {{-- End Loop User --}}
        </tbody>
    </table>

    {{-- Jarak kalau ada bulan berikutnya --}}
    @if($groupIndex < count($dateGroups) - 1)
        <table><tr><td colspan="15" style="height: 30px;"></td></tr></table>
    @endif
@endforeach
