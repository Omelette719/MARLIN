<?php
$jenisLabel = $filters['jenis_rambu_id'] && isset($jenisRambuNama) ? $jenisRambuNama : 'Semua Jenis';
$statusLabel = $filters['status'] ? \App\Enums\StatusRambuPasang::from($filters['status'])->label() : 'Semua Status';
$periodeLabel = ($filters['tanggal_dari'] ? \Carbon\Carbon::parse($filters['tanggal_dari'])->translatedFormat('d M Y') : 'Awal') . ' - ' . ($filters['tanggal_sampai'] ? \Carbon\Carbon::parse($filters['tanggal_sampai'])->translatedFormat('d M Y') : 'Sekarang');
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8" />

    <style>
        /* =========================================================
           UKURAN KERTAS
           F4 / FOLIO
           ========================================================= */

        @page {
            size: 215mm 330mm;
            margin: 14mm 16mm 15mm 16mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Calibri', 'Arial', sans-serif;
            font-size: 12px;
            color: #000;
            line-height: 1.35;
        }

        /* =========================================================
           KOP
           ========================================================= */

        .kop {
            text-align: center;
            padding-bottom: 8px;
            margin-bottom: 12px;
            border-bottom: 1px solid #000;
        }

        .kop .instansi {
            font-size: 14px;
            font-weight: normal;
            margin: 0;
        }

        .kop .dinas {
            font-size: 13px;
            margin: 2px 0 0 0;
        }

        .kop .alamat {
            font-size: 10px;
            margin: 3px 0 0 0;
        }

        /* =========================================================
           JUDUL
           ========================================================= */

        h1.judul {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            margin: 8px 0 9px 0;
        }

        /* =========================================================
           FILTER
           ========================================================= */

        .filter-box {
            width: 100%;
            border: 1px solid #000;
            margin-bottom: 14px;
        }

        table.filter-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.filter-table td {
            border-right: 1px solid #000;
            padding: 6px 8px;
            vertical-align: middle;
        }

        table.filter-table td:last-child {
            border-right: none;
        }

        .filter-label {
            display: block;
            font-size: 9px;
            margin-bottom: 2px;
        }

        .filter-value {
            display: block;
            font-size: 11px;
        }

        /* =========================================================
           JUDUL SECTION
           ========================================================= */

        .section-title {
            font-size: 12px;
            font-weight: normal;
            margin: 14px 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #000;
        }

        /* =========================================================
           RINGKASAN
           ========================================================= */

        table.ringkasan {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 14px;
        }

        table.ringkasan td {
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            padding: 8px 5px;
            height: 58px;
        }

        table.ringkasan .angka {
            display: block;
            font-size: 16px;
            margin-bottom: 3px;
        }

        table.ringkasan .label {
            display: block;
            font-size: 10px;
        }

        /* =========================================================
           TABEL DATA
           ========================================================= */

        table.data {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 14px;
        }

        table.data th,
        table.data td {
            border: 1px solid #000;
            padding: 6px 6px;
            font-size: 10px;
            vertical-align: middle;
        }

        table.data th {
            text-align: center;
            font-weight: normal;
        }

        table.data td {
            line-height: 1.3;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* =========================================================
           KOLOM TABEL
           ========================================================= */

        table.data th.no,
        table.data td.no {
            width: 28px;
            text-align: center;
        }

        table.data th.nomor-surat,
        table.data td.nomor-surat {
            width: 18%;
        }

        table.data th.jenis-rambu,
        table.data td.jenis-rambu {
            width: 16%;
        }

        table.data th.lokasi,
        table.data td.lokasi {
            width: 24%;
        }

        table.data th.tanggal,
        table.data td.tanggal {
            width: 12%;
            text-align: center;
        }

        table.data th.status,
        table.data td.status {
            width: 12%;
            text-align: center;
        }

        table.data th.keterangan,
        table.data td.keterangan {
            width: 18%;
        }

        /* =========================================================
           FOOTER
           ========================================================= */

        .footer-note {
            margin-top: 16px;
            padding-top: 7px;
            border-top: 1px solid #000;
            font-size: 9px;
            line-height: 1.4;
        }

        /* =========================================================
           CETAK
           ========================================================= */

        tr {
            page-break-inside: avoid;
        }

        thead {
            display: table-header-group;
        }

        .avoid-break {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    {{-- =========================================================
         KOP
         ========================================================= --}}

    <div class="kop">

        <p class="instansi">
            PEMERINTAH KOTA BANJARMASIN
        </p>

        <p class="dinas">
            DINAS PERHUBUNGAN
        </p>

        <p class="alamat">
            Jl. Karya Bakti No.54, Kuin Cerucuk, Kec. Banjarmasin Bar., Kota Banjarmasin, Kalimantan Selatan 70128
        </p>

    </div>


    {{-- =========================================================
         JUDUL
         ========================================================= --}}

    <h1 class="judul">
        LAPORAN RAMBU LALU LINTAS
    </h1>


    {{-- =========================================================
         FILTER
         ========================================================= --}}

    <div class="filter-box">

        <table class="filter-table">

            <tr>

                <td style="width: 33.33%;">

                    <span class="filter-label">
                        Periode
                    </span>

                    <span class="filter-value">
                        {{ $periodeLabel }}
                    </span>

                </td>

                <td style="width: 33.33%;">

                    <span class="filter-label">
                        Jenis Rambu
                    </span>

                    <span class="filter-value">
                        {{ $jenisLabel }}
                    </span>

                </td>

                <td style="width: 33.33%;">

                    <span class="filter-label">
                        Status
                    </span>

                    <span class="filter-value">
                        {{ $statusLabel }}
                    </span>

                </td>

            </tr>

        </table>

    </div>


    {{-- =========================================================
         RINGKASAN
         ========================================================= --}}

    <div class="section-title">
        Ringkasan
    </div>

    <table class="ringkasan">

        <tr>

            @php
                $kolomLebar = 100 / (count($perStatus) + 1);
            @endphp

            <td style="width: {{ $kolomLebar }}%;">

                <span class="angka">
                    {{ $total }}
                </span>

                <span class="label">
                    Total
                </span>

            </td>

            @foreach ($perStatus as $statusValue => $count)
                <td style="width: {{ $kolomLebar }}%;">

                    <span class="angka">
                        {{ $count }}
                    </span>

                    <span class="label">
                        {{ \App\Enums\StatusRambuPasang::from($statusValue)->label() }}
                    </span>

                </td>
            @endforeach

        </tr>

    </table>


    {{-- =========================================================
         DAFTAR RAMBU
         ========================================================= --}}

    <div class="section-title">
        Daftar Rambu ({{ $total }})
    </div>

    <table class="data">

        <thead>

            <tr>

                <th class="no">
                    No
                </th>

                <th class="nomor-surat">
                    Nomor Surat
                </th>

                <th class="jenis-rambu">
                    Jenis Rambu
                </th>

                <th class="lokasi">
                    Lokasi
                </th>

                <th class="tanggal">
                    Tanggal
                </th>

                <th class="status">
                    Status
                </th>

                <th class="keterangan">
                    Keterangan
                </th>

            </tr>

        </thead>

        <tbody>

            @forelse ($items as $i => $item)
                <tr>

                    <td class="no">
                        {{ $i + 1 }}
                    </td>

                    <td class="nomor-surat">
                        {{ $item->spk->nomor_surat }}
                    </td>

                    <td class="jenis-rambu">
                        {{ $item->rambu->jenisRambu?->nama_jenis }}
                    </td>

                    <td class="lokasi">
                        {{ $item->rambu->wilayah }},
                        {{ $item->rambu->lokasi }}
                    </td>

                    <td class="tanggal">
                        {{ $item->created_at->translatedFormat('d M Y') }}
                    </td>

                    <td class="status">
                        {{ $item->status->label() }}
                    </td>

                    <td class="keterangan">

                        @if ($item->status === \App\Enums\StatusRambuPasang::Batal)
                            Dibatalkan:
                            {{ $item->catatan_pembatalan ?: 'Tidak ada alasan tercatat' }}
                        @endif

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="7">
                        Tidak ada data untuk filter ini.
                    </td>

                </tr>
            @endforelse

        </tbody>

    </table>


    {{-- =========================================================
         FOOTER
         ========================================================= --}}

    <p class="footer-note">
        Dokumen ini dibuat otomatis oleh Sistem MARLIN pada
        {{ now()->translatedFormat('d M Y H:i') }}.
    </p>

</body>

</html>
