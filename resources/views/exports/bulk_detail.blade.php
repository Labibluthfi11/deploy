<table>
    @foreach($users as $user)
        {{-- Header Karyawan --}}
        <thead>
            <tr><td colspan="14" style="font-weight: bold; font-size: 14px; text-align: center; height: 30px; vertical-align: middle;">REKAP ABSENSI KARYAWAN</td></tr>
            <tr><td colspan="14" style="font-weight: bold; text-align: center;">Nama: {{ $user->name }} (ID: {{ $user->id_karyawan }})</td></tr>
            <tr><td colspan="14" style="font-weight: bold; text-align: center;">Periode: {{ $periodeStr }}</td></tr>
            <tr><td colspan="14"></td></tr> {{-- Spasi --}}

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
            @php $totalGajiAll = 0; $no = 1; @endphp

            {{-- Kita filter data absensi si user ini di sini --}}
            @foreach($absensiData->where('user_id', $user->id) as $item)
                @php
                    $totalGajiAll += $item->final_salary;
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
                    <td style="text-align: right; border: 1px solid #000000; background-color: #E2EFDA; font-weight: bold;">Rp {{ number_format($item->final_salary, 0, ',', '.') }}</td>
                    <td style="text-align: center; border: 1px solid #000000;">{{ ucfirst($item->status_approval) }}</td>
                </tr>
            @endforeach

            @if($absensiData->where('user_id', $user->id)->isEmpty())
                <tr><td colspan="14" style="text-align: center; border: 1px solid #000000;">Tidak ada data absensi.</td></tr>
            @else
                {{-- Baris Total --}}
                <tr>
                    <td colspan="11" style="text-align: right; font-weight: bold; border: 1px solid #000000;">TOTAL DITERIMA:</td>
                    <td colspan="2" style="text-align: right; font-weight: bold; border: 1px solid #000000; background-color: #C6E0B4; font-size: 12px;">
                        Rp {{ number_format($totalGajiAll, 0, ',', '.') }}
                    </td>
                    <td style="border: 1px solid #000000;"></td>
                </tr>
            @endif

            {{-- Jarak Antar Karyawan --}}
            <tr><td colspan="14"></td></tr>
            <tr><td colspan="14"></td></tr>
        </tbody>
    @endforeach
</table>
