<?php
    $aksiKata = $spk->jenis_spk === \App\Enums\JenisPekerjaan::Perbaikan ? 'perbaikan' : 'pemasangan';

    $jenisRambuList = $spk->rambuPasang
        ->map(fn ($rp) => $rp->rambu->jenisRambu?->nama_jenis)
        ->filter()
        ->unique()
        ->map(fn ($nama) => mb_strtolower($nama))
        ->values();

    $ringkasanJenisRambu = match ($jenisRambuList->count()) {
        0 => 'rambu',
        1 => $jenisRambuList->first(),
        default => $jenisRambuList->slice(0, -1)->implode(', ').' dan '.$jenisRambuList->last(),
    };

    $perihalText = $spk->perihal ?: "{$aksiKata} {$ringkasanJenisRambu}";

    $surveiNote = $spk->tanggal_survei
        ? ' (DISURVEI TGL '.mb_strtoupper($spk->tanggal_survei->translatedFormat('d F Y')).')'
        : '';

    $rtPertama = $spk->rtPerwakilan->first();

    $dikerjakanBarisMinimal = 5;
    $jumlahBarisDikerjakan = max($spk->dikerjakanOleh->count(), $dikerjakanBarisMinimal);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <style>
        @page { margin: 22px 26px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #000; }

        .surat { border: 1px solid #000; padding: 14px 18px; min-height: 740px; }

        h1.judul { text-align: center; font-size: 14px; margin: 0 0 12px; }

        p.perihal { margin: 0 0 8px; text-align: justify; }

        h2.section { font-size: 11px; font-weight: bold; margin: 16px 0 6px; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data th, table.data td { border: 1px solid #000; padding: 5px 6px; font-size: 10px; text-align: left; vertical-align: top; }
        table.data th { font-weight: bold; }

        .foto { width: 70px; max-height: 55px; }
        .foto-placeholder { width: 60px; height: 45px; border: 1px dashed #999; text-align: center; font-size: 7px; color: #666; padding-top: 15px; }

        .rt-box { border: 1px solid #000; padding: 6px 8px; font-size: 10px; font-weight: bold; margin-bottom: 10px; }

        table.ttd { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.ttd th, table.ttd td { border: 1px solid #000; padding: 6px; font-size: 10px; }
        table.ttd td.tanda-tangan { height: 50px; }

        .mengetahui { margin-top: 22px; font-size: 10px; }
        .mengetahui .ttd-space { height: 46px; }

        .catatan { margin-top: 18px; font-size: 9px; }
        .catatan p { margin: 0 0 3px; font-weight: bold; }
        .catatan ol { margin: 0; padding-left: 14px; }
        .catatan li { margin-bottom: 2px; }

    </style>
</head>
<body>
<div class="surat">
    <h1 class="judul">SURAT PENGANTAR {{ mb_strtoupper($aksiKata) }}</h1>

    <p class="perihal">
        Berdasarkan kiriman surat dari: {{ $spk->wilayah }}, perihal: Permohonan Bantuan {{ $perihalText }}{{ $surveiNote }}
    </p>
    <p class="perihal">
        Dengan ini DISHUB BANJARMASIN melakukan {{ $aksiKata }} sesuai dengan rencana pemasangan yang sudah diketahui.
    </p>
    @if ($spk->catatan_pekerja_tambahan)
        <p class="perihal">{{ $spk->catatan_pekerja_tambahan }}</p>
    @endif

    <table class="data">
        <thead>
            <tr>
                <th style="width: 18px;">No</th>
                <th>Lokasi</th>
                <th style="width: 45px;">Keperluan</th>
                <th style="width: 90px;">Koordinat</th>
                <th>Dokumentasi</th>
                <th>Info</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($spk->rambuPasang as $i => $rp)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $rp->rambu->wilayah }}, {{ $rp->rambu->lokasi }}, {{ mb_strtoupper($rp->rambu->jenisRambu?->nama_jenis ?? '') }}</td>
                    <td>{{ $rp->jumlah }}</td>
                    <td>{{ $rp->rambu->koordinat }}</td>
                    <td>
                        @php $fotoPath = $rp->foto_survei ? public_path('storage/'.$rp->foto_survei) : null; @endphp
                        @if ($fotoPath && file_exists($fotoPath))
                            <img src="{{ $fotoPath }}" class="foto" />
                        @else
                            <div class="foto-placeholder">Belum ada foto</div>
                        @endif
                    </td>
                    <td>
                        @if ($rp->status === \App\Enums\StatusRambuPasang::Batal)
                            DIBATALKAN: {{ $rp->catatan_pembatalan ?: 'Tidak ada alasan tercatat' }}
                        @else
                            {{ $rp->catatan_instruksi ?: 'Tidak ada catatan khusus' }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($rtPertama)
        <div class="rt-box">{{ $rtPertama->nama_lengkap }}{{ $rtPertama->no_telepon ? ' ('.$rtPertama->no_telepon.')' : '' }}</div>
    @endif

    <h2 class="section">Dikerjakan Oleh (Nama, TTD)</h2>
    <table class="ttd">
        <thead>
            <tr>
                <th style="width: 18px;">No</th>
                <th>Nama Petugas</th>
                <th style="width: 160px;">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < $jumlahBarisDikerjakan; $i++)
                @php $d = $spk->dikerjakanOleh->get($i); @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $d?->user->name }}</td>
                    <td class="tanda-tangan"></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="mengetahui">
        Mengetahui,<br>
        RT/Perwakilan
        <div class="ttd-space"></div>
        (....................................................)
    </div>

    <div class="catatan">
        <p>CATATAN:</p>
        <ol>
            <li>HUBUNGI DAN TEMUI RT SEBELUM MELAKUKAN PEMASANGAN</li>
            <li>DISKUSIKAN KESEPAKATAN JIKA PEMASANGAN SESUAI LOKASI AWAL ATAU ADA PERUBAHAN</li>
            <li>CATAT DI INFO JIKA ADA PERUBAHAN ATAU ADA HAL YANG INGIN DISAMPAIKAN</li>
            <li>MEMINTA TTD DARI RT SEBELUM PEMASANGAN UNTUK PERSETUJUAN</li>
            <li>CATAT PADA INFO JUMLAH BARANG/METER YANG TERPASANG, JIKA MEMUNGKINKAN</li>
            <li>MELAKUKAN SWAFOTO DENGAN RT/YANG MENDAMPINGI DI AKHIR KEGIATAN JIKA MEMUNGKINKAN (DENGAN KERTAS)</li>
        </ol>
    </div>
</div>
</body>
</html>
