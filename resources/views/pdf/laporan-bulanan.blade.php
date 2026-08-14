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
           INFORMASI FILTER / PERIODE
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
           SECTION
           ========================================================= */

        h2.section {
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
           TABEL SPK SELESAI
           ========================================================= */

        table.spk-selesai th.no,
        table.spk-selesai td.no {
            width: 28px;
            text-align: center;
        }

        table.spk-selesai th.nomor-surat,
        table.spk-selesai td.nomor-surat {
            width: 18%;
        }

        table.spk-selesai th.wilayah,
        table.spk-selesai td.wilayah {
            width: 17%;
        }

        table.spk-selesai th.jenis,
        table.spk-selesai td.jenis {
            width: 13%;
        }

        table.spk-selesai th.jumlah,
        table.spk-selesai td.jumlah {
            width: 11%;
            text-align: center;
        }

        table.spk-selesai th.durasi,
        table.spk-selesai td.durasi {
            width: 11%;
            text-align: center;
        }

        table.spk-selesai th.deadline,
        table.spk-selesai td.deadline {
            width: 18%;
        }

        /* =========================================================
           TABEL SPK AKTIF
           ========================================================= */

        table.spk-aktif th.no,
        table.spk-aktif td.no {
            width: 28px;
            text-align: center;
        }

        table.spk-aktif th.nomor-surat,
        table.spk-aktif td.nomor-surat {
            width: 25%;
        }

        table.spk-aktif th.wilayah,
        table.spk-aktif td.wilayah {
            width: 29%;
        }

        table.spk-aktif th.deadline,
        table.spk-aktif td.deadline {
            width: 18%;
            text-align: center;
        }

        table.spk-aktif th.progres,
        table.spk-aktif td.progres {
            width: 15%;
            text-align: center;
        }

        /* =========================================================
           TABEL DETAIL RAMBU
           ========================================================= */

        table.detail-rambu th.no,
        table.detail-rambu td.no {
            width: 28px;
            text-align: center;
        }

        table.detail-rambu th.nomor-surat,
        table.detail-rambu td.nomor-surat {
            width: 19%;
        }

        table.detail-rambu th.jenis-rambu,
        table.detail-rambu td.jenis-rambu {
            width: 17%;
        }

        table.detail-rambu th.lokasi,
        table.detail-rambu td.lokasi {
            width: 29%;
        }

        table.detail-rambu th.tanggal,
        table.detail-rambu td.tanggal {
            width: 13%;
            text-align: center;
        }

        table.detail-rambu th.status,
        table.detail-rambu td.status {
            width: 14%;
            text-align: center;
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
        LAPORAN RIWAYAT PEKERJAAN RAMBU LALU LINTAS
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
                        {{ $jenisRambuId && isset($jenisRambuNama) ? $jenisRambuNama : 'Semua Jenis' }}
                    </span>

                </td>

                <td style="width: 33.33%;">

                    <span class="filter-label">
                        Status Rambu
                    </span>

                    <span class="filter-value">
                        {{ $status ? \App\Enums\StatusRambuPasang::from($status)->label() : 'Semua Status' }}
                    </span>

                </td>

            </tr>

        </table>

    </div>


    {{-- =========================================================
         RINGKASAN ASET RAMBU
         ========================================================= --}}

    <h2 class="section">
        Ringkasan Aset Rambu (per {{ now()->translatedFormat('d M Y') }})
    </h2>

    <table class="ringkasan">

        <tr>

            <td style="width: 25%;">

                <span class="angka">
                    {{ $rambu['total'] }}
                </span>

                <span class="label">
                    Total Rambu
                </span>

            </td>

            <td style="width: 25%;">

                <span class="angka">
                    {{ $rambu['terpasang'] }}
                </span>

                <span class="label">
                    Sudah Terpasang
                </span>

            </td>

            <td style="width: 25%;">

                <span class="angka">
                    {{ $rambu['belum_terpasang'] }}
                </span>

                <span class="label">
                    Belum Terpasang
                </span>

            </td>

            <td style="width: 25%;">

                <span class="angka">
                    {{ $rambu['kondisi_rusak'] }}
                </span>

                <span class="label">
                    Kondisi Rusak
                </span>

            </td>

        </tr>

    </table>


    {{-- =========================================================
         RINGKASAN SPK
         ========================================================= --}}

    <h2 class="section">
        Ringkasan SPK Periode Ini
    </h2>

    <table class="ringkasan">

        <tr>

            <td style="width: 25%;">

                <span class="angka">
                    {{ $spk['dibuat_periode'] }}
                </span>

                <span class="label">
                    SPK Dibuat
                </span>

            </td>

            <td style="width: 25%;">

                <span class="angka">
                    {{ $spk['dibuat_selesai'] }}
                </span>

                <span class="label">
                    Sudah Selesai
                </span>

            </td>

            <td style="width: 25%;">

                <span class="angka">
                    {{ $spk['dibuat_aktif'] }}
                </span>

                <span class="label">
                    Masih Aktif
                </span>

            </td>

            <td style="width: 25%;">

                <span class="angka">
                    {{ $kendalaPeriode }}
                </span>

                <span class="label">
                    Kendala Diajukan
                </span>

            </td>

        </tr>

    </table>


    {{-- =========================================================
         SPK SELESAI - ANALITIK
         ========================================================= --}}

    <h2 class="section">
        Daftar SPK Selesai Periode Ini ({{ $spkSelesaiPeriode->count() }})
    </h2>

    <table class="ringkasan">

        <tr>

            <td style="width: 25%;">

                <span class="angka">
                    {{ $analitikSelesai['rata_rata_durasi_hari'] ?? '-' }}
                </span>

                <span class="label">
                    Rata-rata Durasi (Hari)
                </span>

            </td>

            <td style="width: 25%;">

                <span class="angka">
                    {{ $analitikSelesai['rata_rata_selisih_deadline_hari'] ?? '-' }}
                </span>

                <span class="label">
                    Rata-rata Selisih Deadline (Hari)
                </span>

            </td>

            <td style="width: 25%;">

                <span class="angka">
                    {{ $analitikSelesai['tepat_waktu_count'] }}
                </span>

                <span class="label">
                    Tepat Waktu/Lebih Cepat
                </span>

            </td>

            <td style="width: 25%;">

                <span class="angka">
                    {{ $analitikSelesai['terlambat_count'] }}
                </span>

                <span class="label">
                    Terlambat
                </span>

            </td>

        </tr>

    </table>


    {{-- =========================================================
         DAFTAR SPK SELESAI
         ========================================================= --}}

    <table class="data spk-selesai">

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

                <th class="jenis">
                    Jenis
                </th>

                <th class="jumlah">
                    Jumlah Rambu
                </th>

                <th class="durasi">
                    Durasi
                </th>

                <th class="deadline">
                    Selisih Deadline
                </th>

            </tr>

        </thead>

        <tbody>

            @forelse ($spkSelesaiPeriode as $i => $item)
                <tr>

                    <td class="no">
                        {{ $i + 1 }}
                    </td>

                    <td class="nomor-surat">
                        {{ $item->nomor_surat }}
                    </td>

                    <td class="wilayah">
                        {{ $item->wilayah }}
                    </td>

                    <td class="jenis">
                        {{ $item->jenisRingkasan()?->label() ?? 'Pemasangan & Perbaikan' }}
                    </td>

                    <td class="jumlah">
                        {{ $item->rambu_pasang_count }}
                    </td>

                    <td class="durasi">
                        {{ $item->durasiPengerjaanHari() !== null ? $item->durasiPengerjaanHari() . ' hari' : '-' }}
                    </td>

                    <td class="deadline">
                        {{ $item->selisihDeadlineLabel() ?? '-' }}
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="7">
                        Tidak ada SPK yang selesai pada periode ini.
                    </td>
                </tr>
            @endforelse

        </tbody>

    </table>


    {{-- =========================================================
         SPK BELUM SELESAI
         ========================================================= --}}

    <h2 class="section">
        Daftar SPK Belum Selesai ({{ $spkAktif->count() }})
    </h2>

    <table class="data spk-aktif">

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

                <th class="progres">
                    Progres
                </th>

            </tr>

        </thead>

        <tbody>

            @forelse ($spkAktif as $i => $item)
                <tr>

                    <td class="no">
                        {{ $i + 1 }}
                    </td>

                    <td class="nomor-surat">
                        {{ $item->nomor_surat }}
                    </td>

                    <td class="wilayah">
                        {{ $item->wilayah }}
                    </td>

                    <td class="deadline">
                        {{ $item->deadline->translatedFormat('d M Y') }}
                    </td>

                    <td class="progres">
                        {{ $item->selesai_count }}/{{ $item->rambu_pasang_count }}
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="5">
                        Tidak ada SPK aktif saat ini.
                    </td>
                </tr>
            @endforelse

        </tbody>

    </table>


    {{-- =========================================================
         DETAIL RAMBU
         ========================================================= --}}

    <h2 class="section">
        Detail Rambu ({{ $rambuDetail['total'] }})
    </h2>

    <table class="data detail-rambu">

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

            </tr>

        </thead>

        <tbody>

            @forelse ($rambuDetail['items'] as $i => $item)
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
         FOOTER
         ========================================================= --}}

    <p class="footer-note">
        Dokumen ini dibuat otomatis oleh Sistem MARLIN pada
        {{ now()->translatedFormat('d M Y H:i') }}.
    </p>

</body>

</html>
