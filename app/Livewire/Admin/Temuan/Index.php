<?php

namespace App\Livewire\Admin\Temuan;

use App\Enums\StatusTindakLanjut;
use App\Models\AuditLog;
use App\Models\LaporanKondisi;
use App\Models\Notifikasi;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Temuan Lapangan')]
class Index extends Component
{
    public function tolak(int $id): void
    {
        $laporan = LaporanKondisi::with('rambu')->findOrFail($id);
        $laporan->update(['status_tindak_lanjut' => StatusTindakLanjut::Ditolak]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'aksi' => 'temuan_ditolak',
            'keterangan' => "Temuan kondisi untuk rambu di {$laporan->rambu->wilayah}, {$laporan->rambu->lokasi} ditolak.",
        ]);

        Notifikasi::create([
            'user_id' => $laporan->dilaporkan_oleh,
            'judul' => 'Temuan Ditolak',
            'pesan' => "Temuan kondisi yang kamu laporkan untuk rambu di {$laporan->rambu->wilayah}, {$laporan->rambu->lokasi} ditolak admin.",
            // No dedicated "temuan saya" page for petugas to land on, so this
            // points at the rambu itself — its Riwayat Temuan Kondisi section
            // shows this same report in context.
            'url' => route('rambu.show', $laporan->rambu),
            'dibaca' => false,
        ]);

        Flux::modal("tolak-temuan-{$id}")->close();
        Flux::toast(variant: 'success', text: 'Temuan ditandai sebagai ditolak.');
    }

    public function with(): array
    {
        return [
            'temuan' => LaporanKondisi::where('status_tindak_lanjut', StatusTindakLanjut::Baru)
                ->with(['rambu.jenisRambu', 'pelapor'])
                ->latest()
                ->get(),
        ];
    }

    public function render()
    {
        return view('pages::admin.temuan.index');
    }
}
