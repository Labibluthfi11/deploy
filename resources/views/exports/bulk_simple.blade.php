{{-- resources/views/exports/bulk_simple.blade.php --}}

@foreach($dateGroups as $groupIndex => $group)
    <table>
        {{-- ======================= --}}
        {{-- HEADER UTAMA (KUNING) --}}
        {{-- ======================= --}}
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
            <tr><td colspan="15"></td></tr>

            {{-- HEADER BARIS PERTAMA (TANGGAL 1 s.d 11) --}}
            <tr style="background-color: #D9D9D9;">
                {{-- Kolom Nama --}}
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 200px; vertical-align: middle;">Nama</th>

                @php
                    $allDates = $group['dates'];
                    $first11 = array_slice($allDates, 0, 11);
                @endphp

                {{-- Loop Tanggal 1-11 --}}
                @foreach($first11 as $date)
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">
                        {{ \Carbon\Carbon::parse($date)->format('d-M') }}
                    </th>
                @endforeach

                {{-- Isi sisa kosong jika < 11 hari --}}
                @for($i = count($first11); $i < 11; $i++)
                    <th style="border: 1px solid #000000; background-color: #D9D9D9;"></th>
                @endfor

                {{-- Kolom Total --}}
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px; vertical-align: middle;">Total Telat</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px; vertical-align: middle;">Total Lembur</th>
            </tr>
        </thead>

        {{-- ======================= --}}
        {{-- BODY DATA USER --}}
        {{-- ======================= --}}
        <tbody>
            @foreach($users as $user)
                @php
                    // 1. Siapkan Data
                    $userAbsensi = $absensiData->where('user_id', $user->id);
                    $dateChunks = array_chunk($allDates, 11); // Potong array tanggal per 11 biji

                    // 2. Hitung Total (Di awal)
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

                    // 3. HITUNG ROWSPAN (PENTING!)
                    // Chunk 1 = 1 baris (Data)
                    // Chunk sisanya = 2 baris (Header + Data)
                    // Rumus: 1 + ((TotalChunk - 1) * 2)
                    $totalChunks = count($dateChunks);
                    $rowSpan = 1 + (($totalChunks - 1) * 2);
                @endphp

                {{-- LOOP SETIAP PECAHAN TANGGAL (Chunk) --}}
                @foreach($dateChunks as $chunkIndex => $chunk)

                    {{-- A. BARIS HEADER SELIPAN (Untuk Tanggal 12++, 23++, dst) --}}
                    @if($chunkIndex > 0)
                        <tr>
                            {{-- HEADER TANGGAL --}}
                            @foreach($chunk as $date)
                                <td style="font-weight: bold; text-align: center; border: 1px solid #000000; background-color: #f2f2f2;">
                                    {{ \Carbon\Carbon::parse($date)->format('d-M') }}
                                </td>
                            @endforeach

                            {{-- Isi sisa kosong --}}
                            @for($k = count($chunk); $k < 11; $k++)
                                <td style="border: 1px solid #000000; background-color: #eaeaea;"></td>
                            @endfor
                        </tr>
                    @endif

                    {{-- B. BARIS DATA --}}
                    <tr>
                        {{-- NAMA (Cuma muncul di Chunk 0, lalu di-merge ke bawah) --}}
                        @if($chunkIndex === 0)
                            <td rowspan="{{ $rowSpan }}" style="text-align: left; border: 1px solid #000000; vertical-align: top; font-weight: bold; padding: 5px;">
                                {{ $user->name }}
                            </td>
                        @endif

                        {{-- ISI DATA ABSENSI --}}
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
                                        if (($absen->late_minutes ?? 0) > 0) $cellContent .= " Tl:{$absen->late_minutes}m";
                                        if (($absen->overtime_minutes ?? 0) > 0) {
                                            $jam = floor($absen->overtime_minutes / 60);
                                            $cellContent .= " Lb:{$jam}j";
                                        }
                                    } elseif ($status == 'izin' || $status == 'sakit') {
                                        $cellContent = ucfirst($status);
                                        $bgStyle = 'background-color: #FFFACD;'; // Kuning
                                    } else {
                                        $cellContent = ucfirst($status);
                                    }
                                } else {
                                    if (!$isWeekend) {
                                        $cellContent = 'Tidak Masuk';
                                        $bgStyle = 'background-color: #FFB6C1;'; // Merah Muda
                                    } else {
                                        $cellContent = '-';
                                    }
                                }
                            @endphp

                            <td style="text-align: center; border: 1px solid #000000; font-size: 10px; vertical-align: middle; {{ $bgStyle }}">
                                {{ $cellContent }}
                            </td>
                        @endforeach

                        {{-- Sisa Kosong Data --}}
                        @for($k = count($chunk); $k < 11; $k++)
                            <td style="border: 1px solid #000000; background-color: #eaeaea;"></td>
                        @endfor

                        {{-- TOTAL (Cuma muncul di Chunk 0, lalu di-merge ke bawah) --}}
                        @if($chunkIndex === 0)
                            <td rowspan="{{ $rowSpan }}" style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: top; padding-top: 10px;">
                                {{ $totalTelat > 0 ? $totalTelat : '' }}
                            </td>
                            <td rowspan="{{ $rowSpan }}" style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: top; padding-top: 10px;">
                                {{ $totalLemburStr }}
                            </td>
                        @endif
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    {{-- Spasi antar tabel bulan --}}
    @if($groupIndex < count($dateGroups) - 1)
        <table><tr><td colspan="15" style="height: 30px;"></td></tr></table>
    @endif
@endforeach
