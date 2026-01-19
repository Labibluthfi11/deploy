{{-- resources/views/exports/bulk_fix.blade.php --}}

<table>
    {{-- HEADER JUDUL KUNING (SAMA KEK YG LU PUNYA) --}}
    <thead>
        <tr>
            <td colspan="15" style="font-weight: bold; font-size: 16px; text-align: center; height: 40px; vertical-align: middle; background-color: #FFFF00; border: 1px solid #000000;">
                ABSENSI KARYAWAN FREELANCE
            </td>
        </tr>
        <tr>
            <td colspan="15" style="font-weight: bold; text-align: center; background-color: #FFFF00; border: 1px solid #000000;">
                {{ $monthLabel ?? 'Bulan Ini' }}
            </td>
        </tr>
        <tr>
            <td colspan="15" style="text-align: center; border: 1px solid #000000;">
                {{ $periodeStr ?? '' }}
            </td>
        </tr>
        <tr><td colspan="15"></td></tr>

        {{-- HEADER TABEL UTAMA (GREY) --}}
        {{-- Ini header untuk 11 hari pertama (01-Jan s/d 11-Jan) --}}
        <tr style="background-color: #D9D9D9;">
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 200px; vertical-align: middle;">Nama</th>

            {{-- Ambil 11 tanggal pertama untuk Header Utama --}}
            @php
                // Pastikan $dates adalah array/collection semua tanggal dalam periode (1-31)
                $firstChunk = collect($dates)->take(11);
            @endphp

            @foreach($firstChunk as $date)
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">
                    {{ \Carbon\Carbon::parse($date)->format('d-M') }}
                </th>
            @endforeach

            {{-- Kalau tanggal kurang dari 11, isi cell kosong biar rapi --}}
            @for($i = $firstChunk->count(); $i < 11; $i++)
                <th style="border: 1px solid #000000; background-color: #D9D9D9;"></th>
            @endfor

            <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px; vertical-align: middle;">Total Telat</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px; vertical-align: middle;">Total Lembur</th>
        </tr>
    </thead>

    {{-- BODY DATA --}}
    <tbody>
        @foreach($users as $user)
            @php
                // 1. FILTER DATA ABSENSI USER INI
                $userAbsensi = $absensiData->where('user_id', $user->id);

                // 2. HITUNG TOTAL DULU (BIAR BISA DI ROWSPAN DI KANAN)
                $totalTelat = 0; // dalam jumlah hari/kali
                $totalMenitLembur = 0;

                foreach ($dates as $date) {
                    $absen = $userAbsensi->first(function($item) use ($date) {
                        return \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                    });
                    if ($absen) {
                        if (($absen->late_minutes ?? 0) > 0) $totalTelat++;
                        $totalMenitLembur += $absen->overtime_minutes ?? 0;
                    }
                }

                // Format String Lembur
                $lemburJam = floor($totalMenitLembur / 60);
                $lemburMenit = $totalMenitLembur % 60;
                $totalLemburStr = $totalMenitLembur > 0 ? sprintf('%dj %dm', $lemburJam, $lemburMenit) : '';

                // 3. BAGI TANGGAL JADI CHUNK (KELOMPOK) ISI 11
                $dateChunks = collect($dates)->chunk(11);

                // 4. HITUNG ROWSPAN
                // Rumus: Chunk pertama butuh 1 baris (karena headernya ikut Header Utama tabel)
                // Chunk selanjutnya butuh 2 baris (1 baris header tanggal, 1 baris data)
                // Jadi: 1 + ((TotalChunk - 1) * 2)
                $rowSpan = 1 + (($dateChunks->count() - 1) * 2);
            @endphp

            {{-- LOOP SETIAP CHUNK TANGGAL (1-11, 12-22, 23-31) --}}
            @foreach($dateChunks as $chunkIndex => $chunk)

                {{-- LOGIC BARIS HEADER TANGGAL (Hanya untuk chunk ke-2 dst, karena chunk 1 pake header tabel utama) --}}
                @if($chunkIndex > 0)
                    <tr>
                        {{-- Header Tanggal (01-Jan, dll) --}}
                        @foreach($chunk as $date)
                            <td style="font-weight: bold; text-align: center; border: 1px solid #000000; background-color: #f2f2f2;">
                                {{ \Carbon\Carbon::parse($date)->format('d-M') }}
                            </td>
                        @endforeach

                        {{-- Isi sisa kolom jika chunk terakhir kurang dari 11 hari --}}
                        @for($k = $chunk->count(); $k < 11; $k++)
                            <td style="border: 1px solid #000000; background-color: #f2f2f2;"></td>
                        @endfor
                    </tr>
                @endif

                {{-- LOGIC BARIS DATA --}}
                <tr>
                    {{-- KOLOM NAMA (Cuma muncul di baris pertama chunk pertama, lalu di-rowspan) --}}
                    @if($chunkIndex === 0)
                        <td rowspan="{{ $rowSpan }}" style="border: 1px solid #000000; vertical-align: top; padding: 5px; font-weight: bold;">
                            {{ $user->name }}
                        </td>
                    @endif

                    {{-- LOOP DATA ABSEN PER HARI DI CHUNK INI --}}
                    @foreach($chunk as $date)
                        @php
                            $absen = $userAbsensi->first(function($item) use ($date) {
                                return \Carbon\Carbon::parse($item->check_in_at)->isSameDay($date);
                            });

                            $cellText = '-';
                            $bgStyle = ''; // Default putih

                            $isWeekend = \Carbon\Carbon::parse($date)->isWeekend(); // Sabtu/Minggu

                            if ($absen) {
                                // JAM MASUK - PULANG
                                $in = \Carbon\Carbon::parse($absen->check_in_at)->format('H:i');
                                $out = $absen->check_out_at ? \Carbon\Carbon::parse($absen->check_out_at)->format('H:i') : '-';

                                // STATUS LOGIC
                                $status = strtolower($absen->status);
                                if ($status == 'hadir') {
                                    $cellText = "$in - $out";

                                    // Append Telat
                                    if (($absen->late_minutes ?? 0) > 0) {
                                        $cellText .= " Telat: {$absen->late_minutes}m";
                                    }
                                    // Append Lembur
                                    if (($absen->overtime_minutes ?? 0) > 0) {
                                        $j = floor($absen->overtime_minutes / 60);
                                        $m = $absen->overtime_minutes % 60;
                                        $cellText .= " Lembur: {$j}j {$m}m";
                                    }
                                } elseif ($status == 'izin') {
                                    $cellText = 'Izin';
                                    $bgStyle = 'background-color: #FFFACD;'; // Kuning muda
                                } elseif ($status == 'sakit') {
                                    $cellText = 'Sakit';
                                    $bgStyle = 'background-color: #FFFACD;';
                                } else {
                                    $cellText = ucfirst($status);
                                }
                            } else {
                                // TIDAK ADA DATA ABSEN
                                if (!$isWeekend) {
                                    $cellText = 'Tidak Masuk';
                                    $bgStyle = 'background-color: #FFC7CE; color: #9C0006;'; // Pink Merah text
                                }
                            }
                        @endphp

                        <td style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-size: 10px; {{ $bgStyle }}">
                            {{ $cellText }}
                        </td>
                    @endforeach

                    {{-- Isi Sisa Kolom Data Kosong (biar tabel rapi 11 kolom) --}}
                    @for($k = $chunk->count(); $k < 11; $k++)
                        <td style="border: 1px solid #000000; background-color: #eaeaea;"></td>
                    @endfor

                    {{-- KOLOM TOTAL (Cuma muncul di baris pertama chunk pertama, lalu di-rowspan) --}}
                    @if($chunkIndex === 0)
                        <td rowspan="{{ $rowSpan }}" style="border: 1px solid #000000; text-align: center; vertical-align: top; font-weight: bold;">
                            {{ $totalTelat > 0 ? "Total Telat: " . $totalTelat : '' }}
                        </td>
                        <td rowspan="{{ $rowSpan }}" style="border: 1px solid #000000; text-align: center; vertical-align: top; font-weight: bold;">
                            {{ $totalLemburStr }}
                        </td>
                    @endif
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
