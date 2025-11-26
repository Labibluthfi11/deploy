<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $user->name }}</title>
    <style>
        /* [FIX 1] Reset Margin Kertas & Pakai Padding Body */
        @page {
            margin: 0px;
            size: a4 portrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            padding: 30px; /* Ini margin aman konten kamu */
        }

        /* [FIX 2] Hapus max-width fixed, ganti 100% */
        .container {
            width: 100%;
        }

        /* HEADER */
        .header {
            text-align: center;
            margin-bottom: 20px;
            background: #BDD7EE;
            padding: 10px; /* Padding dikurangi sedikit karena ada tabel di dalam */
            border: 1px solid #333;
        }

        /* Perbaikan: Style untuk teks di dalam tabel header */
        .header-title h1 { font-size: 16pt; font-weight: bold; margin-bottom: 5px; }
        .header-title h2 { font-size: 14pt; font-weight: bold; }

        /* DATA KARYAWAN & UTILS */
        .data-header {
            font-weight: bold;
            background: #E7E6E6;
            padding: 8px;
            border: 1px solid #333;
            margin-bottom: 5px;
        }

        .data-item { padding: 5px 0; font-weight: bold; }

        /* TABEL GAJI */
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .salary-table th {
            background: #BDD7EE;
            padding: 10px;
            font-weight: bold;
            text-align: left;
            border: 1px solid #333;
            font-size: 11pt;
        }

        .salary-table td {
            padding: 10px;
            border: 1px solid #333;
        }

        .salary-table td.label { font-weight: bold; }
        .salary-table td.value { text-align: right; font-weight: normal; }

        .total-row { background: #D9D9D9; }
        .total-row td { font-weight: bold; }

        /* TERBILANG */
        .terbilang {
            background: #FFFF00;
            padding: 10px;
            border: 1px solid #333;
            text-align: center;
            font-weight: bold;
            font-style: italic;
            margin-bottom: 20px;
            margin-top: 15px;
        }

        /* FOOTER */
        .footer { text-align: center; margin-top: 30px; }
        .footer .quote { font-weight: bold; font-style: italic; margin-bottom: 20px; }
        .footer .signature { font-weight: bold; margin-top: 40px; }

        /* LAYOUT TABLE HELPER */
        .flex-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .flex-table td { vertical-align: top; }
        .spacer { width: 4%; border: none; }
    </style>
</head>
<body>
    <div class="container">

        <div class="header">
            <table style="width: 100%; border-collapse: collapse; border: none;">
                <tr>
                    <td style="width: 15%; text-align: left; vertical-align: middle; border: none; padding: 0;">
                        <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="width: 80px; height: auto;">
                    </td>

                    <td style="width: 85%; text-align: center; vertical-align: middle; border: none; padding: 0;" class="header-title">
                        <h1>PT. ANSEL MUDA BERKARYA</h1>
                        <h2>SLIP GAJI KARYAWAN</h2>
                    </td>
                </tr>
            </table>
        </div>

        <table class="flex-table" style="margin-bottom: 20px;">
            <tr>
                <td style="width: 48%; border: 1px solid #333; padding: 0;">
                    <div class="data-header">DATA KARYAWAN</div>
                    <div style="padding: 10px;">
                        <div class="data-item">
                            Nama Karyawan<br>
                            : {{ $user->name }}
                        </div>
                        <div class="data-item" style="margin-top: 8px;">
                            Nomor Induk Pegawai<br>
                            : {{ $user->id_karyawan ?? '-' }}
                        </div>
                    </div>
                </td>

                <td class="spacer"></td>

                <td style="width: 48%; border: 1px solid #333; padding: 10px;">
                    <div class="data-item">
                        Periode Penggajian<br>
                        : {{ $periode }}
                    </div>
                    <div class="data-item" style="margin-top: 8px;">
                        Tipe Karyawan<br>
                        : {{ ucfirst($user->employment_type) }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="flex-table">
            <tr>
                <td style="width: 48%;">
                    <table class="salary-table">
                        <thead>
                            <tr><th colspan="2">PENGHASILAN</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="label">Upah Harian (Total)</td>
                                <td class="value">Rp {{ number_format($gajiPokok, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="label">Upah Lembur (Total)</td>
                                <td class="value">Rp {{ number_format($gajiLembur, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="label">Jumlah Jam Lembur</td>
                                <td class="value">: {{ $durasiLembur }}</td>
                            </tr>
                            <tr class="total-row">
                                <td class="label">TOTAL PENGHASILAN</td>
                                <td class="value">Rp {{ number_format($gajiPokok + $gajiLembur, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>

                <td class="spacer"></td>

                <td style="width: 48%;">
                    <table class="salary-table">
                        <thead>
                            <tr><th colspan="2">POTONGAN</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="label">Potongan Keterlambatan</td>
                                <td class="value">Rp {{ number_format($potongan, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="label">&nbsp;</td>
                                <td class="value">&nbsp;</td>
                            </tr>
                            <tr>
                                <td class="label">&nbsp;</td>
                                <td class="value">&nbsp;</td>
                            </tr>
                            <tr class="total-row">
                                <td class="label">TOTAL POTONGAN</td>
                                <td class="value">Rp {{ number_format($potongan, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <tr>
                <td style="background: #C6E0B4; padding: 12px; border: 1px solid #333; font-weight: bold; font-size: 12pt; width: 70%;">
                    PENGHASILAN BERSIH (TAKE HOME PAY)
                </td>
                <td style="background: #C6E0B4; padding: 12px; border: 1px solid #333; font-weight: bold; font-size: 12pt; text-align: right; width: 30%;">
                    Rp {{ number_format($gajiBersih, 0, ',', '.') }}
                </td>
            </tr>
        </table>

        <div class="terbilang">
            Terbilang: {{ $terbilang }}
        </div>

        <div class="footer">
            <div class="quote">"Keep Up The Good Work"</div>
            <div class="signature">
                <div style="font-weight: bold;">HRGA Division</div>
                <div style="font-weight: bold; margin-top: 5px;">PT. ANSEL MUDA BERKARYA</div>
            </div>
        </div>

    </div>
</body>
</html>
