{{-- Ini adalah file HTML yang bakal jadi Excel --}}
<table>
    {{-- Loop per Karyawan --}}
    @foreach($users as $user)
        {{-- Header Karyawan --}}
        <thead>
            <tr>
                <td colspan="14" style="font-weight: bold; font-size: 16px; text-align: center; height: 30px; vertical-align: middle; background-color: #BDD7EE;">
                    REKAP ABSENSI KARYAWAN
                </td>
            </tr>
            <tr>
                <td colspan="7" style="font-weight: bold; text-align: left;">Nama: {{ $user->name }}</td>
                <td colspan="7" style="font-weight: bold; text-align: left;">ID: {{ $user->id_karyawan }}</td>
            </tr>
            <tr>
                <td colspan="14" style="font-weight: bold; text-align: left;">Periode: {{ $periodeStr }}</td>
            </tr>
            <tr>
                <td colspan="14"></td> {{-- Spasi --}}
            </tr>

            {{-- Header Tabel --}}
            <tr style="background-color: #4F46E5; color: #FFFFFF;">
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 50px;">No</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">Tanggal</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 80px;">Check-in</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 80px;">Check-out</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 80px;">Status</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 80px;">Tipe</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Telat</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Menit Lembur</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">Gaji Lembur</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">Gaji Pokok</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">Potongan</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px;">Gaji Bersih</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 120px; background-color: #C6E0B4;">TOTAL GAJI</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; width: 100px;">Approval</th>
            </tr>
        </thead>

        {{-- Body Tabel --}}
        <tbody>
            @php
                $totalGajiAll = 0;
                $no = 1;
                // Ambil data absensi SI USER INI AJA dari koleksi besar
                $userAbsensi = $absensiData->where('user_id', $user->id);
            @endphp

            @forelse($userAbsensi as $item)
                @php
                    // ⬇️ INI RUMUS TOTAL GAJI PER HARI LO ⬇️
                    $totalGajiHari = ($item->base_salary + $item->overtime_pay) - $item->late_penalty;
                    $totalGajiAll += $totalGajiHari; // Tambahin ke total si user
                @endphp
                <tr>
                    <td style="text-align: center; border: 1px solid #000000;">{{ $no++ }}</td>
                    <td style="text-align: center; border: 1px solid #000000;">{{ \Carbon\Carbon::parse($item->check_in_at)->translatedFormat('d M Y') }}</td>
                    <td style="text-align: center; border: 1px solid #000000;">{{ \Carbon\Carbon::parse($item->check_in_at)->format('H:i') }}</td>
                    <td style="text-align: center; border: 1px solid #000000;">{{ $item->check_out_at ? \Carbon\Carbon::parse($item->check_out_at)->format('H:i') : '-' }}</td>
                    <td style="text-align: center; border: 1px solid #000000;">{{ ucfirst($item->status) }}</td>
                    <td style="text-align: center; border: 1px solid #000000;">{{ ucfirst($item->tipe ?? '-') }}</td>
                    <td style="text-align: center; border: 1px solid #000000;">{{ $item->late_minutes }} Menit</td>
                    <td style="text-align: center; border: 1px solid #000000;">{{ $item->overtime_minutes }} Menit</td>
                    <td style="text-align: right; border: 1px solid #000000;">Rp {{ number_format($item->overtime_pay, 0, ',', '.') }}</td>
                    <td style="text-align: right; border: 1px solid #000000;">Rp {{ number_format($item->base_salary, 0, ',', '.') }}</td>
                    <td style="text-align: right; border: 1px solid #000000;">Rp {{ number_format($item->late_penalty, 0, ',', '.') }}</td>
                    <td style="text-align: right; border: 1px solid #000000;">Rp {{ number_format($item->final_salary, 0, ',', '.') }}</td>
                    <td style="text-align: right; border: 1px solid #000000; background-color: #E2EFDA; font-weight: bold;">Rp {{ number_format($totalGajiHari, 0, ',', '.') }}</td>
                    <td style="text-align: center; border: 1px solid #000000;">{{ ucfirst($item->status_approval) }}</td>
                </tr>
            @empty
                <tr><td colspan="14" style="text-align: center; border: 1px solid #000000;">Tidak ada data absensi.</td></tr>
            @endforelse

            {{-- Baris Total per Karyawan --}}
            <tr>
                <td colspan="11" style="text-align: right; font-weight: bold; border: 1px solid #000000;">TOTAL DITERIMA:</td>
                <td colspan="2" style="text-align: right; font-weight: bold; border: 1px solid #000000; background-color: #C6E0B4; font-size: 12px;">
                    Rp {{ number_format($totalGajiAll, 0, ',', '.') }}
                </td>
                <td style="border: 1px solid #000000;"></td>
            </tr>

            {{-- Jarak Antar Karyawan --}}
            <tr><td colspan="14"></td></tr>
            <tr><td colspan="14"></td></tr>
        </tbody>
    @endforeach

    {{-- 🔥 GRAND TOTAL SELURUH KARYAWAN (3 BARIS AJA) 🔥 --}}
    <tbody>
        <tr><td colspan="14"></td></tr> {{-- Spasi ekstra --}}

        {{-- Header Grand Total --}}
        <tr>
            <td colspan="14" style="font-weight: bold; font-size: 16px; text-align: center; background-color: #4F46E5; color: #FFFFFF; border: 2px solid #000000;">
                TOTAL KESELURUHAN SEMUA KARYAWAN
            </td>
        </tr>

        {{-- Baris Total Gaji Lembur --}}
        <tr>
            <td colspan="8" style="text-align: right; font-weight: bold; border: 1px solid #000000; background-color: #FFF2CC;">TOTAL GAJI LEMBUR:</td>
            <td colspan="6" style="text-align: right; font-weight: bold; border: 1px solid #000000; background-color: #FFF2CC; font-size: 14px;">
                Rp {{ number_format($grandTotalGajiLembur, 0, ',', '.') }}
            </td>
        </tr>

        {{-- Baris Total Gaji Pokok --}}
        <tr>
            <td colspan="8" style="text-align: right; font-weight: bold; border: 1px solid #000000; background-color: #E2EFDA;">TOTAL GAJI POKOK:</td>
            <td colspan="6" style="text-align: right; font-weight: bold; border: 1px solid #000000; background-color: #E2EFDA; font-size: 14px;">
                Rp {{ number_format($grandTotalGajiPokok, 0, ',', '.') }}
            </td>
        </tr>

        {{-- Baris Total Gaji Bersih (FINAL) --}}
        <tr>
            <td colspan="8" style="text-align: right; font-weight: bold; border: 2px solid #000000; background-color: #C6E0B4; font-size: 14px;">TOTAL GAJI BERSIH:</td>
            <td colspan="6" style="text-align: right; font-weight: bold; border: 2px solid #000000; background-color: #C6E0B4; font-size: 16px;">
                Rp {{ number_format($grandTotalGajiBersih, 0, ',', '.') }}
            </td>
        </tr>
    </tbody>
</table>
