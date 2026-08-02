<?php
    use App\Support\PetaData;

    $jenisLabel = $filters['jenis_rambu_id'] && isset($jenisRambuNama) ? $jenisRambuNama : 'Semua Jenis';
    $tingkatLabel = $filters['tingkat'] ? PetaData::TINGKAT_LABELS[$filters['tingkat']] ?? $filters['tingkat'] : 'Semua Tingkat';
    $periodeLabel = ($filters['tanggal_dari'] ? \Carbon\Carbon::parse($filters['tanggal_dari'])->translatedFormat('d M Y') : 'Awal')
        .' - '
        .($filters['tanggal_sampai'] ? \Carbon\Carbon::parse($filters['tanggal_sampai'])->translatedFormat('d M Y') : 'Sekarang');
?>
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
        .filter-info { text-align: center; font-size: 10px; margin-bottom: 14px; color: #444; }

        h2.section { font-size: 12px; color: #004655; border-bottom: 1px solid #bfc8cc; padding-bottom: 3px; margin: 16px 0 6px; }

        table.ringkasan { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.ringkasan td { border: 1px solid #bfc8cc; padding: 6px 8px; }
        table.ringkasan .angka { font-size: 15px; font-weight: bold; color: #004655; }
        table.ringkasan .label { font-size: 9px; color: #6f797c; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data th, table.data td { border: 1px solid #bfc8cc; padding: 5px 6px; font-size: 10px; text-align: left; vertical-align: top; }
        table.data th { background: #f3f4f5; font-weight: bold; }

        .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 4px; }

        .peta-gambar { width: 100%; max-height: 320px; border: 1px solid #bfc8cc; border-radius: 4px; object-fit: contain; margin-bottom: 10px; }

        .footer-note { margin-top: 20px; font-size: 9px; color: #6f797c; }
    </style>
</head>
<body>
    <div class="kop">
        <p class="instansi">PEMERINTAH KOTA BANJARMASIN</p>
        <p class="dinas">DINAS PERHUBUNGAN</p>
        <p class="alamat">Jl. RE. Martadinata, Banjarmasin, Kalimantan Selatan</p>
    </div>

    <h1 class="judul">PETA SEBARAN RAMBU LALU LINTAS</h1>
    <p class="filter-info">
        Jenis: {{ $jenisLabel }} &nbsp;|&nbsp; Tingkat: {{ $tingkatLabel }} &nbsp;|&nbsp; Periode Tugas: {{ $periodeLabel }}
    </p>

    @if ($gambarPetaDataUri)
        <img src="{{ $gambarPetaDataUri }}" class="peta-gambar" />
    @endif

    <h2 class="section">Analitik</h2>
    <table class="ringkasan">
        <tr>
            @php $kolomLebar = 100 / (count($perTingkat) + 1); @endphp
            <td style="width: {{ $kolomLebar }}%;"><span class="angka">{{ $total }}</span><br><span class="label">Total Rambu</span></td>
            @foreach ($perTingkat as $tingkatValue => $count)
                <td style="width: {{ $kolomLebar }}%;"><span class="angka">{{ $count }}</span><br><span class="label">{{ PetaData::TINGKAT_LABELS[$tingkatValue] ?? $tingkatValue }}</span></td>
            @endforeach
        </tr>
    </table>

    <h2 class="section">Sebaran per Wilayah</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Wilayah</th>
                <th style="width: 60px;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($perWilayah->sortDesc() as $wilayah => $count)
                <tr>
                    <td>{{ $wilayah }}</td>
                    <td>{{ $count }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Tidak ada data untuk filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="section">Sebaran per Jenis Rambu</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Jenis Rambu</th>
                <th style="width: 60px;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($perJenis->sortDesc() as $jenis => $count)
                <tr>
                    <td>{{ $jenis ?: 'Tidak diketahui' }}</td>
                    <td>{{ $count }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Tidak ada data untuk filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="section">Daftar Rambu ({{ $total }})</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 18px;">No</th>
                <th>Jenis Rambu</th>
                <th>Lokasi</th>
                <th style="width: 90px;">Koordinat</th>
                <th style="width: 90px;">Tingkat</th>
                <th>SPK Terkait</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pins as $i => $pin)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $pin['jenis_rambu'] ?? '-' }}</td>
                    <td>{{ $pin['wilayah'] }}, {{ $pin['lokasi'] }}</td>
                    <td>{{ number_format($pin['lat'], 5) }}, {{ number_format($pin['lng'], 5) }}</td>
                    <td>{{ PetaData::TINGKAT_LABELS[$pin['tingkat']] ?? $pin['tingkat'] }}</td>
                    <td>{{ $pin['spk']['nomor_surat'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Tidak ada data untuk filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer-note">
        @if ($gambarPetaDataUri)
            Gambar peta di atas adalah cuplikan tampilan peta interaktif saat PDF ini diunduh.
        @else
            Gambar peta tidak berhasil disertakan saat PDF ini dibuat — data di bawah tetap lengkap sesuai filter yang dipilih.
        @endif
        Dokumen ini dibuat otomatis oleh Sistem MARLIN pada {{ now()->translatedFormat('d M Y H:i') }}.
    </p>
</body>
</html>
