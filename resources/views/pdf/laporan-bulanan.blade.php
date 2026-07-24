<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <style>
        @page { margin: 28px 32px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #191c1d; }

        .kop { text-align: center; border-bottom: 2px solid #004655; padding-bottom: 8px; margin-bottom: 14px; }
        .kop .instansi { font-size: 15px; font-weight: bold; color: #004655; margin: 0; }
        .kop .dinas { font-size: 13px; margin: 2px 0; }
        .kop .alamat { font-size: 9px; color: #555; margin: 0; }

        h1.judul { text-align: center; font-size: 13px; text-decoration: underline; margin: 10px 0 4px; }
        .periode { text-align: center; font-size: 11px; margin-bottom: 14px; }

        h2.section { font-size: 12px; color: #004655; border-bottom: 1px solid #bfc8cc; padding-bottom: 3px; margin: 16px 0 6px; }

        table.ringkasan { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.ringkasan td { border: 1px solid #bfc8cc; padding: 6px 8px; width: 25%; }
        table.ringkasan .angka { font-size: 15px; font-weight: bold; color: #004655; }
        table.ringkasan .label { font-size: 9px; color: #6f797c; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data th, table.data td { border: 1px solid #bfc8cc; padding: 5px 6px; font-size: 10px; text-align: left; vertical-align: top; }
        table.data th { background: #f3f4f5; font-weight: bold; }

        .footer-note { margin-top: 20px; font-size: 9px; color: #6f797c; }
    </style>
</head>
<body>
    <div class="kop">
        <p class="instansi">PEMERINTAH KOTA BANJARMASIN</p>
        <p class="dinas">DINAS PERHUBUNGAN</p>
        <p class="alamat">Jl. RE. Martadinata, Banjarmasin, Kalimantan Selatan</p>
    </div>

    <h1 class="judul">LAPORAN RIWAYAT PEKERJAAN RAMBU LALU LINTAS</h1>
    <p class="periode">Periode: {{ $periodeLabel }}</p>

    <h2 class="section">Ringkasan Aset Rambu (per {{ now()->translatedFormat('d M Y') }})</h2>
    <table class="ringkasan">
        <tr>
            <td><span class="angka">{{ $rambu['total'] }}</span><br><span class="label">Total Rambu</span></td>
            <td><span class="angka">{{ $rambu['terpasang'] }}</span><br><span class="label">Sudah Terpasang</span></td>
            <td><span class="angka">{{ $rambu['belum_terpasang'] }}</span><br><span class="label">Belum Terpasang</span></td>
            <td><span class="angka">{{ $rambu['kondisi_rusak'] }}</span><br><span class="label">Kondisi Rusak</span></td>
        </tr>
    </table>

    <h2 class="section">Ringkasan SPK Bulan Ini</h2>
    <table class="ringkasan">
        <tr>
            <td><span class="angka">{{ $spk['dibuat_bulan_ini'] }}</span><br><span class="label">SPK Dibuat</span></td>
            <td><span class="angka">{{ $spk['dibuat_selesai'] }}</span><br><span class="label">Sudah Selesai</span></td>
            <td><span class="angka">{{ $spk['dibuat_aktif'] }}</span><br><span class="label">Masih Aktif</span></td>
            <td><span class="angka">{{ $kendalaBulanIni }}</span><br><span class="label">Kendala Diajukan</span></td>
        </tr>
    </table>

    <h2 class="section">Daftar SPK Selesai Bulan Ini ({{ $spkSelesaiBulanIni->count() }})</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 18px;">No</th>
                <th>Nomor Surat</th>
                <th>Wilayah</th>
                <th>Jenis</th>
                <th style="width: 60px;">Jumlah Rambu</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($spkSelesaiBulanIni as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->nomor_surat }}</td>
                    <td>{{ $item->wilayah }}</td>
                    <td>{{ $item->jenis_spk->label() }}</td>
                    <td>{{ $item->rambu_pasang_count }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Tidak ada SPK yang selesai pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="section">Daftar SPK Belum Selesai ({{ $spkAktif->count() }})</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 18px;">No</th>
                <th>Nomor Surat</th>
                <th>Wilayah</th>
                <th style="width: 70px;">Deadline</th>
                <th style="width: 60px;">Progres</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($spkAktif as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->nomor_surat }}</td>
                    <td>{{ $item->wilayah }}</td>
                    <td>{{ $item->deadline->translatedFormat('d M Y') }}</td>
                    <td>{{ $item->selesai_count }}/{{ $item->rambu_pasang_count }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Tidak ada SPK aktif saat ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer-note">Dokumen ini dibuat otomatis oleh Sistem MARLIN pada {{ now()->translatedFormat('d M Y H:i') }}.</p>
</body>
</html>
