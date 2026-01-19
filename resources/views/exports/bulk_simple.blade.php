{{-- resources/views/exports/bulk_simple.blade.php --}}

@foreach($dateGroups as $groupIndex => $group)
    <table>
        <thead>
            {{-- 1. JUDUL ATAS (KUNING) --}}
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

            {{-- 2. HEADER UTAMA (Cuma buat Tanggal 1-11) --}}
            <tr style="background-color: #D9D9D9;">
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 50px; vertical-align: middle;">NO</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 200px; vertical-align: middle;">Nama</th>

                @php
                    $allDates = $group['dates'];
                    // Ambil 11 tanggal pertama
                    $first11 = array_slice($allDates, 0, 11);
                @endphp

                @foreach($first11 as $date)
                    <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">
                        {{ \Carbon\Carbon::parse($date)->format('d-M') }}
                    </th>
                @endforeach

                {{-- Kalau tanggal kurang dari 11, isi kotak kosong biar rapi --}}
                @for($i = count($first11); $i < 11; $i++)
                    <th style="border: 1px solid #000000; background-color: #D9D9D9;"></th>
                @endfor

                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px; vertical-align: middle;">Total Telat</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px; vertical-align: middle;">Total Lembur</th>
            </tr>
        </thead>

        <tbody>
            @php $no = 1; @endphp
            @foreach($users as $user)
                @php
                    // SIAPKAN DATA
                    $userAbsensi = $absensiData->where('user_id', $user->id);

                    // PECAH TANGGAL JADI PER 11 BIJI (Chunking)
                    $dateChunks = array_chunk($allDates, 11);

                    // HITUNG TOTAL (Lakukan di awal untuk rowspan)
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

                    // RUMUS ROWSPAN (PENTING BIAR GAK PECAH)
                    // Chunk 1 (1-11) = 1 baris data.
                    // Chunk 2 dst (12-22, 23-31) = Butuh 2 baris (1 baris Header Tanggal + 1 baris Data).
                    // Rumus: 1 + ((JumlahChunk - 1) * 2)
                    $totalChunks = count($dateChunks);
                    $rowSpan = 1 + (($totalChunks - 1) * 2);
                @endphp

                {{-- LOOP SETIAP POTONGAN TANGGAL (1-11, 12-22, dst) --}}
                @foreach($dateChunks as $chunkIndex => $chunk)

                    {{-- A. JIKA INI CHUNK KE-2 KEATAS (Tgl 12++), BIKIN BARIS HEADER DULU --}}
                    @if($chunkIndex > 0)
                        <tr>
                            {{-- Rowspan Nama & No sudah cover ini, jadi langsung loop tanggal --}}
                            @foreach($chunk as $date)
                                <td style="font-weight: bold; text-align: center; border: 1px solid #000000; background-color: #f2f2f2;">
                                    {{ \Carbon\Carbon::parse($date)->format('d-M') }}
                                </td>
                            @endforeach

                            {{-- Isi sisa kosong kalo tanggal abis --}}
                            @for($k = count($chunk); $k < 11; $k++)
                                <td style="border: 1px solid #000000; background-color: #eaeaea;"></td>
                            @endfor
                        </tr>
                    @endif

                    {{-- B. BARIS DATA ABSENSI --}}
                    <tr>
                        {{-- 1. NO & NAMA (Cuma render SEKALI di chunk pertama, terus rowspan ke bawah) --}}
                        @if($chunkIndex === 0)
                            <td rowspan="{{ $rowSpan }}" style="text-align: center; border: 1px solid #000000; vertical-align: top; font-weight: bold; padding-top:10px;">{{ $no }}</td>
                            <td rowspan="{{ $rowSpan }}" style="text-align: left; border: 1px solid #000000; vertical-align: top; font-weight: bold; padding:10px 5px;">{{ $user->name }}</td>
                        @endif

                        {{-- 2. ISI KOTAK ABSEN --}}
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
                                        // Tambah info telat/lembur di cell
                                        if (($absen->late_minutes ?? 0) > 0) $cellContent .= " (T:{$absen->late_minutes}m)";
                                        if (($absen->overtime_minutes ?? 0) > 0) {
                                            $jam = floor($absen->overtime_minutes / 60);
                                            $cellContent .= " (L:{$jam}j)";
                                        }
                                    } elseif ($status == 'izin' || $status == 'sakit') {
                                        $cellContent = ucfirst($status);
                                        $bgStyle = 'background-color: #FFFACD;'; // Kuning muda
                                    } else {
                                        $cellContent = ucfirst($status);
                                    }
                                } else {
                                    if (!$isWeekend) {
                                        $cellContent = 'Tidak Masuk';
                                        $bgStyle = 'background-color: #FFB6C1;'; // Merah
                                    } else {
                                        $cellContent = '-';
                                    }
                                }
                            @endphp

                            <td style="text-align: center; border: 1px solid #000000; font-size: 10px; vertical-align: middle; {{ $bgStyle }}">
                                {{ $cellContent }}
                            </td>
                        @endforeach

                        {{-- Isi sisa kosong --}}
                        @for($k = count($chunk); $k < 11; $k++)
                            <td style="border: 1px solid #000000; background-color: #eaeaea;"></td>
                        @endfor

                        {{-- 3. TOTAL (Cuma render SEKALI di chunk pertama) --}}
                        @if($chunkIndex === 0)
                            <td rowspan="{{ $rowSpan }}" style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: top; padding-top:10px;">
                                {{ $totalTelat > 0 ? $totalTelat : '-' }}
                            </td>
                            <td rowspan="{{ $rowSpan }}" style="text-align: center; border: 1px solid #000000; font-weight: bold; vertical-align: top; padding-top:10px;">
                                {{ $totalLemburStr }}
                            </td>
                        @endif
                    </tr>

                @endforeach {{-- End Loop Chunk (1-11, 12-22, dst) --}}

                @php $no++; @endphp
            @endforeach
        </tbody>
    </table>

    {{-- Jarak antar tabel bulan --}}
    @if($groupIndex < count($dateGroups) - 1)
        <table><tr><td colspan="15" style="height: 30px;"></td></tr></table>
    @endif
@endforeach
