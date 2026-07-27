<?php

namespace App\Livewire\User;

use App\Enums\StatusLaporan;
use App\Enums\StatusRambuPasang;
use App\Livewire\Concerns\RejectsNonImageUploads;
use App\Models\AuditLog;
use App\Models\BarangBahan;
use App\Models\DikerjakanOleh;
use App\Models\LaporanPengerjaan;
use App\Models\Notifikasi;
use App\Models\RambuPasang;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Laporan Pengerjaan Lapangan')]
class Laporan extends Component
{
    use RejectsNonImageUploads;
    use WithFileUploads;

    #[Url]
    public ?int $rambuPasangId = null;

    public $foto_sesudah = null;

    public string $koordinat_gps = '';

    public string $catatan_lapangan = '';

    public array $barangBahan = [];

    private const WORKABLE = [StatusRambuPasang::Belum->value, StatusRambuPasang::Revisi->value];

    public function mount(): void
    {
        $this->addBarangBahan();
    }

    public function addBarangBahan(): void
    {
        $this->barangBahan[] = ['nama' => '', 'jumlah' => 1, 'satuan' => ''];
    }

    public function removeBarangBahan(int $index): void
    {
        unset($this->barangBahan[$index]);
        $this->barangBahan = array_values($this->barangBahan);
    }

    // Only the designated perwakilan for a joined SPK may submit — the whole
    // team can join to help physically, but exactly one person files reports.
    private function eligibleSpkIds()
    {
        return DikerjakanOleh::where('by_user_id', Auth::id())
            ->where('is_perwakilan', true)
            ->pluck('by_spk_id');
    }

    private function currentItem(): ?RambuPasang
    {
        if (! $this->rambuPasangId) {
            return null;
        }

        return RambuPasang::with(['rambu', 'spk'])
            ->whereIn('rambu_spk_id', $this->eligibleSpkIds())
            ->whereIn('status', self::WORKABLE)
            ->find($this->rambuPasangId);
    }

    public function selectItem(int $id): void
    {
        $this->rambuPasangId = $id;
        $this->reset('foto_sesudah', 'koordinat_gps', 'catatan_lapangan');
        $this->barangBahan = [];
        $this->addBarangBahan();
    }

    // Cancelling only ever happens from the per-rambu form, reached from that
    // rambu's SPK detail page — going back to the flat cross-SPK list instead
    // of straight to the SPK would just add an extra hop.
    public function back(): void
    {
        $item = $this->currentItem();

        if ($item) {
            $this->redirectRoute('user.spk.show', $item->rambu_spk_id, navigate: true);

            return;
        }

        $this->rambuPasangId = null;
    }

    public function submit(): void
    {
        $item = $this->currentItem();

        if (! $item) {
            $this->back();

            return;
        }

        $this->validate([
            'foto_sesudah' => 'required|image|max:5120',
            'koordinat_gps' => 'nullable|string|max:255',
            'catatan_lapangan' => 'nullable|string|max:2000',
            'barangBahan.*.nama' => 'nullable|string|max:255',
            'barangBahan.*.jumlah' => 'nullable|integer|min:1',
            'barangBahan.*.satuan' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($item) {
            $laporan = LaporanPengerjaan::create([
                'rambu_pasang_id' => $item->id,
                'dilaporkan_oleh' => Auth::id(),
                'foto_sesudah' => $this->foto_sesudah?->store('laporan-pengerjaan/sesudah', 'public'),
                'koordinat_gps' => $this->koordinat_gps ?: $item->rambu->koordinat,
                'catatan_lapangan' => $this->catatan_lapangan ?: null,
                'status' => StatusLaporan::Diajukan,
            ]);

            foreach ($this->barangBahan as $bb) {
                if (blank($bb['nama'] ?? null)) {
                    continue;
                }

                BarangBahan::create([
                    'laporan_pengerjaan_id' => $laporan->id,
                    'nama' => $bb['nama'],
                    'jumlah' => $bb['jumlah'] ?: 1,
                    'satuan' => $bb['satuan'] ?: null,
                ]);
            }

            $item->update(['status' => StatusRambuPasang::MenungguValidasi]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'spk_id' => $item->rambu_spk_id,
                'aksi' => 'laporan_dikirim',
                'keterangan' => "Laporan pengerjaan dikirim untuk rambu di {$item->rambu->wilayah}, {$item->rambu->lokasi}.",
            ]);

            Notifikasi::create([
                'user_id' => $item->spk->dibuat_oleh,
                'judul' => 'Laporan Pengerjaan Masuk',
                'pesan' => "Petugas mengirim laporan pengerjaan untuk SPK {$item->spk->nomor_surat}.",
                'dibaca' => false,
            ]);
        });

        Flux::toast(variant: 'success', text: 'Laporan pengerjaan berhasil dikirim.');

        $this->back();
    }

    public function with(): array
    {
        $item = $this->currentItem();

        return [
            'item' => $item,
            'items' => $item ? collect() : RambuPasang::with(['rambu', 'spk'])
                ->whereIn('rambu_spk_id', $this->eligibleSpkIds())
                ->whereIn('status', self::WORKABLE)
                ->get(),
        ];
    }

    public function render()
    {
        return view('pages::user.laporan');
    }
}
