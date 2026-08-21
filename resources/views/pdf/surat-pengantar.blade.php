<?php
// Pemasangan wins whenever it's present at all — even in a surat that
// mixes both — only a surat that's entirely Perbaikan is worded that way.
$aksiKata = $spk->rambuPasang->every(fn($rp) => $rp->jenis_pekerjaan === \App\Enums\JenisPekerjaan::Perbaikan) ? 'perbaikan' : 'pemasangan';

$jenisRambuList = $spk->rambuPasang->map(fn($rp) => $rp->rambu->jenisRambu?->nama_jenis)->filter()->unique()->map(fn($nama) => mb_strtolower($nama))->values();

$ringkasanJenisRambu = match ($jenisRambuList->count()) {
    0 => 'rambu',
    1 => $jenisRambuList->first(),
    default => $jenisRambuList->slice(0, -1)->implode(', ') . ' dan ' . $jenisRambuList->last(),
};

$perihalText = $spk->perihal ?: "{$aksiKata} {$ringkasanJenisRambu}";

$surveiNote = $spk->tanggal_survei ? ' (DISURVEI TGL ' . mb_strtoupper($spk->tanggal_survei->translatedFormat('d F Y')) . ')' : '';

$contactPersonPertama = $spk->contactPerson->first();

$dikerjakanBarisMinimal = 5;
$jumlahBarisDikerjakan = max($spk->dikerjakanOleh->count(), $dikerjakanBarisMinimal);
?>

<!doctype html>
<html>

<head>
    <meta charset="utf-8">

    <style>
        /* =========================================================
           UKURAN KERTAS
           F4 / Folio
           ========================================================= */

        @page {
            size: 215mm 330mm;
            margin: 12mm 14mm;
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
        }

        /* =========================================================
           JUDUL
           ========================================================= */

        .judul {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 12px 0;
        }

        /* =========================================================
           PARAGRAF PEMBUKA
           ========================================================= */

        .perihal {
            margin: 0;
            padding: 0;
            font-size: 12px;
            line-height: 1.35;
            text-align: left;
        }

        /*
         * Jarak khusus antara paragraf pertama dan kedua
         */
        .perihal+.perihal {
            margin-top: 10px;
        }

        /*
         * Kalau ada catatan tambahan, beri jarak sedikit
         */
        .perihal.catatan-tambahan {
            margin-top: 8px;
        }

        /* =========================================================
           TABEL DAFTAR RAMBU
           ========================================================= */

        table.data {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 12px;
        }

        table.data th,
        table.data td {
            border: 1px solid #000;
            padding: 5px;
            font-size: 10px;
            vertical-align: middle;
        }

        table.data th {
            text-align: center;
            font-weight: normal;
            line-height: 1.05;
        }

        table.data td {
            line-height: 1.25;
        }

        /* ---------------------------------------------------------
           NO — diperkecil
           --------------------------------------------------------- */

        table.data th.no,
        table.data td.no {
            width: 25px;
            text-align: center;
        }

        /* ---------------------------------------------------------
           LOKASI — diperkecil sedikit
           --------------------------------------------------------- */

        table.data th.lokasi,
        table.data td.lokasi {
            width: 21%;
        }

        table.data td.lokasi {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* ---------------------------------------------------------
           KEPERLUAN — diperkecil
           --------------------------------------------------------- */

        table.data th.keperluan,
        table.data td.keperluan {
            width: 38px;
        }

        table.data td.keperluan {
            text-align: center;
        }

        /* ---------------------------------------------------------
           KOORDINAT — diperkecil agar foto bisa lebih besar
           --------------------------------------------------------- */

        table.data th.koordinat,
        table.data td.koordinat {
            width: 20%;
        }

        table.data td.koordinat {
            /*
             * Supaya lintang dan bujur otomatis turun ke baris berikutnya
             * jika tidak cukup lebar.
             */
            word-break: break-all;
            overflow-wrap: anywhere;
            line-height: 1.2;
        }

        /* ---------------------------------------------------------
           DOKUMENTASI — DIPERBESAR
           --------------------------------------------------------- */

        table.data th.dokumentasi,
        table.data td.dokumentasi {
            width: 25%;
        }

        table.data td.dokumentasi {
            padding: 0;
            text-align: center;
        }

        .foto {
            display: block;
            width: 100%;
            height: 90px;
            object-fit: cover;
        }

        .foto-placeholder {
            width: 80px;
            height: 55px;
            border: 1px dashed #777;
            text-align: center;
            font-size: 9px;
            color: #666;
            padding-top: 19px;
            margin: auto;
        }

        /* ---------------------------------------------------------
           INFO
           --------------------------------------------------------- */

        table.data th.info,
        table.data td.info {
            width: 20%;
        }

        table.data td.info {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* =========================================================
           BARIS RT
           ========================================================= */

        .contact-person-row td {
            height: 22px;
            padding: 3px 5px !important;
            font-size: 10px !important;
            vertical-align: middle;
        }

        /* =========================================================
   DIKERJAKAN OLEH

   1 kolom
   2 row:
   - row 1 = judul
   - row 2 = daftar pekerja
   ========================================================= */

        .dikerjakan {
            width: 55%;
            margin: 10px auto 0 auto;
        }

        table.ttd {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.ttd td {
            border: 1px solid #000;
            padding: 5px 7px;
            font-size: 12px;
            vertical-align: middle;
        }

        /* Row pertama */
        table.ttd .judul-pekerja {
            height: 24px;
            text-align: center;
            font-weight: normal;
        }

        /* Row kedua */
        table.ttd .daftar-pekerja {
            height: 110px;
            vertical-align: top;
            line-height: 1.8;
        }

        /* Setiap nama pekerja */
        .pekerja-item {
            display: block;
            white-space: nowrap;
        }

        .garis-ttd {
            margin-left: 15px;
            letter-spacing: 1px;
        }

        /* =========================================================
           MENGETAHUI RT / PERWAKILAN
           
           Diletakkan di bawah Dikerjakan Oleh,
           menempel di sebelah kiri.
           ========================================================= */

        .mengetahui {
            width: 100%;
            margin-top: 8px;
            text-align: left;
            font-size: 12px;
        }

        .mengetahui-title {
            margin-bottom: 2px;
            margin-left: 55px;
        }

        .rt-label {
            margin-bottom: 0;
        }

        .ttd-space {
            height: 45px;
        }

        .ttd-line {
            font-size: 12px;
        }

        /* =========================================================
           CATATAN
           
           Paling bawah dan berada di tengah.
           ========================================================= */

        .catatan {
            width: 80%;
            margin: 15px auto 0 auto;
            font-size: 8px;
            line-height: 1.35;
            text-align: left;
        }

        .catatan-title {
            text-align: left;
            font-size: 8px;
            font-weight: normal;
            margin-bottom: 5px;
        }

        .catatan ol {
            margin: 0;
            padding-left: 22px;
        }

        .catatan li {
            margin-bottom: 3px;
            padding-left: 3px;
        }

        /* =========================================================
           CETAK
           ========================================================= */

        tr {
            page-break-inside: avoid;
        }

        .avoid-break {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    {{-- =========================================================
         JUDUL
         ========================================================= --}}

    <h1 class="judul">
        SURAT PENGANTAR {{ mb_strtoupper($aksiKata) }}
    </h1>


    {{-- =========================================================
         PARAGRAF PEMBUKA
         ========================================================= --}}

    <p class="perihal">
        Berdasarkan kiriman surat dari:
        {{ $spk->wilayah }},
        perihal: Permohonan Bantuan
        {{ $perihalText }}{{ $surveiNote }}
    </p>

    <br>

    <p class="perihal">
        Dengan ini DISHUB BANJARMASIN melakukan
        {{ $aksiKata }}
        sesuai dengan rencana pemasangan yang sudah diketahui.
    </p>

    @if ($spk->catatan_pekerja_tambahan)
        <p class="perihal catatan-tambahan">
            {{ $spk->catatan_pekerja_tambahan }}
        </p>
    @endif


    {{-- =========================================================
         TABEL DAFTAR LOKASI
         ========================================================= --}}

    <table class="data">

        <thead>
            <tr>
                <th class="no">
                    DAF<br>TAR
                </th>

                <th class="lokasi">
                    LOKASI
                </th>

                <th class="keperluan">
                    KEPER<br>LUAN
                </th>

                <th class="koordinat">
                    KOORDINAT
                </th>

                <th class="dokumentasi">
                    DOKUMENTASI
                </th>

                <th class="info">
                    INFO
                </th>
            </tr>
        </thead>

        <tbody>

            @foreach ($spk->rambuPasang as $i => $rp)
                <tr>

                    <td class="no">
                        {{ $i + 1 }}
                    </td>

                    <td class="lokasi">
                        {{ $rp->rambu->wilayah }},
                        {{ $rp->rambu->lokasi }},
                        {{ mb_strtoupper($rp->rambu->jenisRambu?->nama_jenis ?? '') }}
                    </td>

                    <td class="keperluan">
                        {{ $rp->jumlah }}
                    </td>

                    <td class="koordinat">
                        {{ $rp->rambu->koordinat }}
                    </td>

                    <td class="dokumentasi">

                        @php
                            $fotoPath = $rp->foto_survei ? public_path('storage/' . $rp->foto_survei) : null;
                        @endphp

                        @if ($fotoPath && file_exists($fotoPath))
                            <img src="{{ $fotoPath }}" class="foto" />
                        @else
                            <div class="foto-placeholder">
                                Belum ada foto
                            </div>
                        @endif

                    </td>

                    <td class="info">

                        @if ($rp->status === \App\Enums\StatusRambuPasang::Batal)
                            DIBATALKAN:
                            {{ $rp->catatan_pembatalan ?: 'Tidak ada alasan tercatat' }}
                        @else
                            {{ $rp->catatan_instruksi ?: 'Tidak ada catatan khusus' }}
                        @endif

                    </td>

                </tr>
            @endforeach


            {{-- =================================================
                 CONTACT PERSON
                 ================================================= --}}

            @if ($contactPersonPertama)
                <tr class="contact-person-row">

                    <td colspan="6">
                        {{ $contactPersonPertama->nama_lengkap }}{{ $contactPersonPertama->no_telepon ? ' (' . $contactPersonPertama->no_telepon . ')' : '' }}
                    </td>

                </tr>
            @endif

        </tbody>

    </table>


    {{-- =========================================================
         DIKERJAKAN OLEH
         DITENGAH, ANTARA TABEL DAN MENGETAHUI
         ========================================================= --}}

    <div class="dikerjakan avoid-break">

        <table class="ttd">

            <tbody>

                {{-- ROW 1: JUDUL --}}
                <tr>
                    <td class="judul-pekerja">
                        Dikerjakan Oleh (Nama, TTD)
                    </td>
                </tr>

                {{-- ROW 2: DAFTAR PEKERJA --}}
                <tr>
                    <td class="daftar-pekerja"><br>

                        @for ($i = 0; $i < $jumlahBarisDikerjakan; $i++)
                            @php
                                $d = $spk->dikerjakanOleh->get($i);
                            @endphp

                            <span class="pekerja-item">
                                {{ $i + 1 }}.
                                {{ $d?->user->name }}
                            </span><br>
                        @endfor

                    </td>
                </tr>

            </tbody>

        </table>

        <br>

    </div>

    {{-- =========================================================
         MENGETAHUI
         DI BAWAH PEKERJA DAN MENEMPEL KE KIRI
         ========================================================= --}}

    <div class="mengetahui avoid-break">

        <div class="mengetahui-title">
            Mengetahui
        </div>

        <div class="rt-label">
            RT/PERWAKILAN......................
        </div>

        <br>
        <br>
        <br>

        <div class="ttd-space"></div>

        <div class="ttd-line">
            (....................................................)
        </div>

    </div>


    {{-- =========================================================
         CATATAN
         PALING BAWAH DAN TENGAH
         ========================================================= --}}

    <div class="catatan avoid-break">

        <div class="catatan-title">
            CATATAN:
        </div>

        <ol>

            <li>
                HUBUNGI DAN TEMUI RT SEBELUM MELAKUKAN PEMASANGAN
            </li>

            <li>
                DISKUSIKAN KESEPAKATAN JIKA PEMASANGAN SESUAI
                LOKASI AWAL ATAU ADA PERUBAHAN
            </li>

            <li>
                CATAT DI INFO JIKA ADA PERUBAHAN ATAU ADA HAL YANG
                INGIN DISAMPAIKAN
            </li>

            <li>
                MEMINTA TTD DARI RT SEBELUM PEMASANGAN UNTUK PERSETUJUAN
            </li>

            <li>
                CATAT PADA INFO JUMLAH BARANG/METER YANG TERPASANG,
                JIKA MEMUNGKINKAN
            </li>

            <li>
                MELAKUKAN SWAFOTO DENGAN RT/YANG MENDAMPINGI DI AKHIR
                KEGIATAN JIKA MEMUNGKINKAN (DENGAN KERTAS)
            </li>

        </ol>

    </div>

</body>

</html>
