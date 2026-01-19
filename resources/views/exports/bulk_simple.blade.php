{{-- resources/views/exports/bulk_simple.blade.php --}}

@foreach($dateGroups as $groupIndex => $group)
    <table>
        {{-- HEADER JUDUL (BAGIAN KUNING) --}}
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
            <tr style="background-color: #D9D9D9;">
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 50px; vertical-align: middle;">NO</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 200px; vertical-align: middle;">Nama</th>

                {{-- Ambil 11 Tanggal Pertama untuk Header --}}
                @php
                    $allDates = $group['dates']; // Array tanggal
                    $first11 = array_slice($allDates, 0, 11);
                @endphp

                @foreach($first11 as $date)
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">
                        {{ \Carbon\Carbon::parse($date)->format('d-M') }}
                    </th>
                @endforeach

                {{-- Isi sisa header jika tanggal kurang dari 11 (biar tabel rapi) --}}
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
                    // 1. SIAPKAN DATA
                    $userAbsensi = $absensiData->where('user_id', $user->id);
                    $dateChunks = array_chunk($allDates, 11); // Potong array tanggal per 11 biji

                    // 2. HITUNG TOTAL (Kalkulasi dulu di awal biar bisa ditaruh di merged cell)
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

                    // 3. HITUNG ROWSPAN
                    // Rumus: Chunk pertama butuh 1 baris (karena headernya ikut <thead>)
                    // Chunk sisanya butuh 2 baris (1 baris Judul Tanggal + 1 baris Data)
                    $totalChunks = count($dateChunks);
                    $rowSpan = 1 + (($totalChunks - 1) * 2);
                @endphp

                @foreach($dateChunks as $chunkIndex => $chunk)

                    {{-- JIKA CHUNK > 0 (Artinya tanggal 12-22, 23-31, dst), KITA BUTUH HEADER TANGGAL --}}
                    @if($chunkIndex > 0)
                        <tr>
                            {{-- Header Tanggal Lanjutan --}}
                            @foreach($chunk as $date)
                                <td style="font-weight: bold; text-align: center; border: 1px solid #000000; background-color: #f2f2f2;">
                                    {{ \Carbon\Carbon::parse($date)->format('d-M') }}
                                </td>
                            @endforeach

                            {{-- Isi kosong jika chunk terakhir gak sampe 11 kolom --}}
                            @for($k = count($chunk); $k < 11; $k++)
                                <td style="border: 1px solid #000000; background-color: #eaeaea;"></td>
                            @endfor
                        </tr>
                    @endif

                    {{-- BARIS DATA --}}
                    <tr>
                        {{-- NO & NAMA (Cuma dirender pas chunk pertama, terus di-rowspan ke bawah) --}}
                        @if($chunkIndex === 0)
                            <td rowspan="{{ $rowSpan }}" style="text-align: center; border: 1px solid #000000; vertical-align: top; font-weight: bold; padding-top: 10px;">{{ $no }}</td>
                            <td rowspan="{{ $rowSpan }}" style="text-align: left; border: 1px solid #000000; vertical-align: top; font-weight: bold; padding: 10px 5px;">{{ $user->name }}</td>
                        @endif

                        {{-- ISI CELL DATA ABSENSI --}}
                        @foreach($chunk as $date)
                            @php
                                $absen = $userAbsensi->first(function($item) use ($date) {
                                    return \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                                });

                                $cellContent = '';
                                $bgStyle = ''; // Default putih

                                // Cek Weekend
                                $d = \Carbon\Carbon::parse($date);
                                $isWeekend = $d->isWeekend();

                                if ($absen) {
                                    $in = \Carbon\Carbon::parse($absen->check_in_at)->format('H:i');
                                    $out = $absen->check_out_at ? \Carbon\Carbon::parse($absen->check_out_at)->format('H:i') : '-';

                                    $status = strtolower($absen->status);

                                    if ($status == 'hadir') {
                                        $cellContent = "$in - $out";

                                        // Detail Telat/Lembur di dalam cell
                                        if (($absen->late_minutes ?? 0) > 0) {
                                            $cellContent .= " (T: {$absen->late_minutes}m)";
                                        }
                                        if (($absen->overtime_minutes ?? 0) > 0) {
                                            $jam = floor($absen->overtime_minutes / 60);
                                            $mnt = $absen->overtime_minutes % 60;
                                            $cellContent .= " (L: {$jam}j {$mnt}m)";
                                        }
                                    } elseif ($status == 'izin') {
                                        $cellContent = 'Izin';
                                        $bgStyle = 'background-color: #FFFACD;'; // Kuning muda
                                    } elseif ($status == 'sakit') {
                                        $cellContent = 'Sakit';
                                        $bgStyle = 'background-color: #FFFACD;';
                                    } else {
                                        $cellContent = ucfirst($status);
                                    }
                                } else {
                                    // GAK ABSEN
                                    if (!$isWeekend) {
                                        $cellContent = 'Tidak Masuk';
                                        $bgStyle = 'background-color: #FFB6C1; color: #8B0000;'; // Merah muda
                                    } else {
                                        $cellContent = '-';
                                    }
                                }
                            @endphp

                            <td style="text-align: center; border: 1px solid #000000; font-size: 10px; vertical-align: middle; {{ $bgStyle }}">
                                {{ $cellContent }}
                            </td>
                        @endforeach

                        {{-- Isi cell kosong jika data di baris ini kurang dari 11 kolom --}}
                        @for($k = count($chunk); $k < 11; $k++)
                            <td style="border: 1px solid #000000; background-color: #eaeaea;"></td>
                        @endfor

                        {{-- TOTAL (Cuma dirender pas chunk pertama, rowspan ke bawah) --}}
                        @if($chunkIndex === 0)
                            <td rowspan="{{ $rowSpan }}" style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: top; padding-top: 10px;">
                                {{ $totalTelat > 0 ? $totalTelat : '-' }}
                            </td>
                            <td rowspan="{{ $rowSpan }}" style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: top; padding-top: 10px;">
                                {{ $totalLemburStr }}
                            </td>
                        @endif
                    </tr>
                @endforeach

                @php $no++; @endphp
            @endforeach
        </tbody>
    </table>

    {{-- Spasi antar Group (misal antar departemen/bulan) --}}
    @if($groupIndex < count($dateGroups) - 1)
        <table><tr><td colspan="15" style="height: 30px;"></td></tr></table>
    @endif
@endforeach
