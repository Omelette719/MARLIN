<?php
use App\Support\PetaData;

$jenisLabel = $filters['jenis_rambu_id'] && isset($jenisRambuNama) ? $jenisRambuNama : 'Semua Jenis';
$tingkatLabel = $filters['tingkat'] ? PetaData::TINGKAT_LABELS[$filters['tingkat']] ?? $filters['tingkat'] : 'Semua Tingkat';
$wilayahLabel = $filters['kelurahan'] ?? $filters['kecamatan'] ?? 'Semua Kelurahan';
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
           KOP / IDENTITAS INSTANSI
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
            margin: 8px 0 8px 0;
        }

        /* =========================================================
           INFORMASI FILTER
           ========================================================= */

        .filter-box {
            width: 100%;
            border: 1px solid #000;
            margin-bottom: 12px;
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
            font-size: 11px;
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
           PETA
           ========================================================= */

        .section-title {
            font-size: 12px;
            font-weight: normal;
            margin: 14px 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #000;
        }

        .peta-wrapper {
            width: 100%;
            border: 1px solid #000;
            padding: 4px;
            margin-bottom: 12px;
        }

        .peta-gambar {
            display: block;
            width: 100%;
            max-height: 125mm;
            object-fit: contain;
        }

        .peta-caption {
            font-size: 9px;
            text-align: center;
            margin-top: 4px;
        }

        /* =========================================================
           ANALITIK
           ========================================================= */

        table.ringkasan {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 12px;
        }

        table.ringkasan td {
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            padding: 8px 5px;
            height: 55px;
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
           TABEL DATA UMUM
           ========================================================= */

        table.data {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 12px;
        }

        table.data th,
        table.data td {
            border: 1px solid #000;
            padding: 6px 7px;
            font-size: 11px;
            vertical-align: middle;
        }

        table.data th {
            text-align: center;
            font-weight: normal;
        }

        table.data td {
            line-height: 1.3;
        }

        table.data td.jumlah {
            text-align: center;
        }

        /* =========================================================
           DAFTAR RAMBU
           ========================================================= */

        table.daftar-rambu th,
        table.daftar-rambu td {
            font-size: 10px;
        }

        table.daftar-rambu th.no,
        table.daftar-rambu td.no {
            width: 5%;
            text-align: center;
        }

        table.daftar-rambu th.jenis,
        table.daftar-rambu td.jenis {
            width: 17%;
        }

        table.daftar-rambu th.lokasi,
        table.daftar-rambu td.lokasi {
            width: 25%;
        }

        table.daftar-rambu th.koordinat,
        table.daftar-rambu td.koordinat {
            width: 20%;
        }

        table.daftar-rambu th.kondisi,
        table.daftar-rambu td.kondisi {
            width: 13%;
        }

        table.daftar-rambu th.spk,
        table.daftar-rambu td.spk {
            width: 20%;
        }

        table.daftar-rambu td.no {
            text-align: center;
        }

        table.daftar-rambu td.koordinat {
            word-break: break-all;
            overflow-wrap: anywhere;
        }

        /* =========================================================
           DAFTAR SPK
           ========================================================= */

        table.daftar-spk th,
        table.daftar-spk td {
            font-size: 10px;
        }

        table.daftar-spk th.no,
        table.daftar-spk td.no {
            width: 6%;
            text-align: center;
        }

        table.daftar-spk th.nomor-surat,
        table.daftar-spk td.nomor-surat {
            width: 22%;
        }

        table.daftar-spk th.wilayah,
        table.daftar-spk td.wilayah {
            width: 40%;
        }

        table.daftar-spk th.deadline,
        table.daftar-spk td.deadline {
            width: 16%;
        }

        table.daftar-spk th.tingkat,
        table.daftar-spk td.tingkat {
            width: 16%;
        }

        /* =========================================================
           FOOTER / CATATAN
           ========================================================= */

        .footer-note {
            margin-top: 14px;
            padding-top: 7px;
            border-top: 1px solid #000;
            font-size: 9px;
            line-height: 1.4;
        }

        /* =========================================================
           CETAK
           ========================================================= */

        .avoid-break {
            page-break-inside: avoid;
        }

        tr {
            page-break-inside: avoid;
        }

        thead {
            display: table-header-group;
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
        PETA SEBARAN RAMBU LALU LINTAS
    </h1>


    {{-- =========================================================
         INFORMASI FILTER
         ========================================================= --}}

    <div class="filter-box">

        <table class="filter-table">

            <tr>

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
                        Tingkat
                    </span>

                    <span class="filter-value">
                        {{ $tingkatLabel }}
                    </span>
                </td>

                <td style="width: 33.33%;">
                    <span class="filter-label">
                        Kelurahan / Kecamatan
                    </span>

                    <span class="filter-value">
                        {{ $wilayahLabel }}
                    </span>
                </td>

            </tr>

        </table>

    </div>


    {{-- =========================================================
         PETA
         ========================================================= --}}

    @if ($gambarPetaDataUri)
        <div class="section-title">
            Peta Sebaran
        </div>

        <div class="peta-wrapper">

            <img src="{{ $gambarPetaDataUri }}" class="peta-gambar" />

            <div class="peta-caption">
                Peta sebaran lokasi rambu lalu lintas berdasarkan filter yang dipilih.
            </div>

        </div>
    @endif


    {{-- =========================================================
         ANALITIK
         ========================================================= --}}

    <div class="section-title">
        Analitik
    </div>

    <table class="ringkasan">

        <tr>

            @php
                $kolomLebar = 100 / (count($perTingkat) + 1);
            @endphp

            <td style="width: {{ $kolomLebar }}%;">
                <span class="angka">
                    {{ $total }}
                </span>

                <span class="label">
                    Total Rambu
                </span>
            </td>

            @foreach ($perTingkat as $tingkatValue => $count)
                <td style="width: {{ $kolomLebar }}%;">

                    <span class="angka">
                        {{ $count }}
                    </span>

                    <span class="label">
                        {{ PetaData::TINGKAT_LABELS[$tingkatValue] ?? $tingkatValue }}
                    </span>

                </td>
            @endforeach

        </tr>

    </table>


    {{-- =========================================================
         SEBARAN PER KECAMATAN
         ========================================================= --}}

    <div class="section-title">
        Sebaran per Kecamatan
    </div>

    <table class="data">

        <thead>
            <tr>

                <th>
                    Kecamatan
                </th>

                <th style="width: 80px;">
                    Jumlah
                </th>

            </tr>
        </thead>

        <tbody>

            @forelse ($perKecamatan->sortDesc() as $kecamatan => $count)
                <tr>

                    <td>
                        {{ $kecamatan }}
                    </td>

                    <td class="jumlah">
                        {{ $count }}
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="2">
                        Tidak ada data untuk filter ini.
                    </td>
                </tr>
            @endforelse

        </tbody>

    </table>


    {{-- =========================================================
         SEBARAN PER JENIS RAMBU
         ========================================================= --}}

    <div class="section-title">
        Sebaran per Jenis Rambu
    </div>

    <table class="data">

        <thead>
            <tr>

                <th>
                    Jenis Rambu
                </th>

                <th style="width: 80px;">
                    Jumlah
                </th>

            </tr>
        </thead>

        <tbody>

            @forelse ($perJenis->sortDesc() as $jenis => $count)
                <tr>

                    <td>
                        {{ $jenis ?: 'Tidak diketahui' }}
                    </td>

                    <td class="jumlah">
                        {{ $count }}
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="2">
                        Tidak ada data untuk filter ini.
                    </td>
                </tr>
            @endforelse

        </tbody>

    </table>


    {{-- =========================================================
         DAFTAR SPK
         ========================================================= --}}

    <div class="section-title">
        Daftar SPK Aktif Terkait ({{ $spkTerkait->count() }})
    </div>

    <table class="data daftar-spk">

        <thead>

            <tr>

                <th class="no">
                    No
                </th>

                <th class="nomor-surat">
                    Nomor Surat
                </th>

                <th class="wilayah">
                    Wilayah
                </th>

                <th class="deadline">
                    Deadline
                </th>

                <th class="tingkat">
                    Tingkat
                </th>

            </tr>

        </thead>

        <tbody>

            @forelse ($spkTerkait as $i => $spk)
                <tr>

                    <td class="no">
                        {{ $i + 1 }}
                    </td>

                    <td class="nomor-surat">
                        {{ $spk->nomor_surat }}
                    </td>

                    <td class="wilayah">
                        {{ $spk->wilayah }}
                    </td>

                    <td class="deadline">
                        {{ $spk->deadline->translatedFormat('d M Y') }}
                    </td>

                    <td class="tingkat">
                        {{ $spk->urgensiSaatIni()->label() }}
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="5">
                        Tidak ada SPK aktif untuk filter ini.
                    </td>
                </tr>
            @endforelse

        </tbody>

    </table>


    {{-- =========================================================
         DAFTAR RAMBU
         ========================================================= --}}

    <div class="section-title">
        Daftar Rambu ({{ $total }})
    </div>

    <table class="data daftar-rambu">

        <thead>

            <tr>

                <th class="no">
                    No
                </th>

                <th class="jenis">
                    Jenis Rambu
                </th>

                <th class="lokasi">
                    Lokasi
                </th>

                <th class="koordinat">
                    Koordinat
                </th>

                <th class="kondisi">
                    Kondisi
                </th>

                <th class="spk">
                    SPK Terkait
                </th>

            </tr>

        </thead>

        <tbody>

            @forelse ($pins as $i => $pin)
                <tr>

                    <td class="no">
                        {{ $i + 1 }}
                    </td>

                    <td class="jenis">
                        {{ $pin['jenis_rambu'] ?? '-' }}
                    </td>

                    <td class="lokasi">
                        {{ $pin['wilayah'] }},
                        {{ $pin['lokasi'] }}
                    </td>

                    <td class="koordinat">
                        {{ number_format($pin['lat'], 5) }},
                        {{ number_format($pin['lng'], 5) }}
                    </td>

                    <td class="kondisi">
                        @if (! $pin['sudah_terpasang'])
                            N/A
                        @else
                            {{ $pin['kondisi_terkini'] === 'rusak' ? 'Rusak' : 'Baik' }}
                        @endif
                    </td>

                    <td class="spk">
                        {{ $pin['spk']['nomor_surat'] ?? '-' }}
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="6">
                        Tidak ada data untuk filter ini.
                    </td>
                </tr>
            @endforelse

        </tbody>

    </table>


    {{-- =========================================================
         CATATAN / FOOTER
         ========================================================= --}}

    <p class="footer-note">

        @if ($gambarPetaDataUri)
            Gambar peta di atas adalah cuplikan tampilan peta interaktif
            saat PDF ini diunduh.
        @else
            Gambar peta tidak berhasil disertakan saat PDF ini dibuat —
            data di bawah tetap lengkap sesuai filter yang dipilih.
        @endif

        Dokumen ini dibuat otomatis oleh Sistem MARLIN pada
        {{ now()->translatedFormat('d M Y H:i') }}.

    </p>

</body>

</html>
