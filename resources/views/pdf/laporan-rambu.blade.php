<?php
    $jenisLabel = $filters['jenis_rambu_id'] && isset($jenisRambuNama) ? $jenisRambuNama : 'Semua Jenis';
    $statusLabel = $filters['status'] ? \App\Enums\StatusRambuPasang::from($filters['status'])->label() : 'Semua Status';
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

        table.ringkasan { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.ringkasan td { border: 1px solid #bfc8cc; padding: 6px 8px; }
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

    <h1 class="judul">LAPORAN RAMBU LALU LINTAS</h1>
    <p class="filter-info">
        Periode: {{ $periodeLabel }} &nbsp;|&nbsp; Jenis: {{ $jenisLabel }} &nbsp;|&nbsp; Status: {{ $statusLabel }}
    </p>

    <table class="ringkasan">
        <tr>
            @php $kolomLebar = 100 / (count($perStatus) + 1); @endphp
            <td style="width: {{ $kolomLebar }}%;"><span class="angka">{{ $total }}</span><br><span class="label">Total</span></td>
            @foreach ($perStatus as $statusValue => $count)
                <td style="width: {{ $kolomLebar }}%;"><span class="angka">{{ $count }}</span><br><span class="label">{{ \App\Enums\StatusRambuPasang::from($statusValue)->label() }}</span></td>
            @endforeach
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 18px;">No</th>
                <th>Nomor Surat</th>
                <th>Jenis Rambu</th>
                <th>Lokasi</th>
                <th style="width: 65px;">Tanggal</th>
                <th style="width: 70px;">Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->spk->nomor_surat }}</td>
                    <td>{{ $item->rambu->jenisRambu?->nama_jenis }}</td>
                    <td>{{ $item->rambu->wilayah }}, {{ $item->rambu->lokasi }}</td>
                    <td>{{ $item->created_at->translatedFormat('d M Y') }}</td>
                    <td>{{ $item->status->label() }}</td>
                    <td>
                        @if ($item->status === \App\Enums\StatusRambuPasang::Batal)
                            Dibatalkan: {{ $item->catatan_pembatalan ?: 'Tidak ada alasan tercatat' }}
                        @elseif ($item->status === \App\Enums\StatusRambuPasang::Tertunda)
                            Kendala: {{ $item->kendala->first()?->alasan ?: 'Tidak ada alasan tercatat' }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Tidak ada data untuk filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer-note">Dokumen ini dibuat otomatis oleh Sistem MARLIN pada {{ now()->translatedFormat('d M Y H:i') }}.</p>
</body>
</html>
