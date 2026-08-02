<?php

namespace App\Livewire\User;

use App\Enums\StatusLaporan;
use App\Enums\StatusRambuPasang;
use App\Livewire\Concerns\RejectsNonImageUploads;
use App\Models\AuditLog;
use App\Models\DikerjakanOleh;
use App\Models\Kendala as KendalaModel;
use App\Models\Notifikasi;
use App\Models\RambuPasang;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Laporan Kendala Lapangan')]
class Kendala extends Component
{
    use RejectsNonImageUploads;
    use WithFileUploads;

    #[Url]
    public ?int $rambuPasangId = null;

    public string $alasan = '';

    public $foto = null;

    private const WORKABLE = [StatusRambuPasang::Belum->value, StatusRambuPasang::Revisi->value];

    // Once a rambu already has a Kendala (Tertunda) or a LaporanPengerjaan
    // (MenungguValidasi), it's still reachable here so it can be edited in
    // place or swapped for the other report type — but only until the SPK's
    // laporan akhir is submitted for admin validation (see currentItem()).
    private const EDITABLE = [StatusRambuPasang::Tertunda->value, StatusRambuPasang::MenungguValidasi->value];

    public function mount(): void
    {
        if ($this->rambuPasangId) {
            $this->selectItem($this->rambuPasangId);
        }
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
            ->whereIn('status', [...self::WORKABLE, ...self::EDITABLE])
            ->whereHas('spk', fn ($q) => $q->whereNull('laporan_akhir_diajukan_at'))
            ->find($this->rambuPasangId);
    }

    public function selectItem(int $id): void
    {
        $this->rambuPasangId = $id;
        $this->reset('alasan', 'foto');

        $item = $this->currentItem();

        if ($item && $item->status === StatusRambuPasang::Tertunda) {
            $this->alasan = $item->kendala()->latest()->first()?->alasan ?? '';
        }
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
        $this->reset('alasan', 'foto');
    }

    public function submit(): void
    {
        $item = $this->currentItem();

        if (! $item) {
            $this->back();

            return;
        }

        $editingInPlace = $item->status === StatusRambuPasang::Tertunda;

        $this->validate([
            'alasan' => 'required|string|max:1000',
            'foto' => $editingInPlace ? 'nullable|image|max:5120' : 'required|image|max:5120',
        ]);

        DB::transaction(function () use ($item, $editingInPlace) {
            if ($item->status === StatusRambuPasang::MenungguValidasi) {
                // Switching from a completion report back to a kendala: the
                // old laporan (and its barang_bahan, via cascade) no longer applies.
                $item->laporanPengerjaan()->delete();
            }

            $existing = $editingInPlace ? $item->kendala()->latest()->first() : null;

            if ($existing) {
                $existing->update([
                    'alasan' => $this->alasan,
                    'foto' => $this->foto ? $this->foto->store('kendala', 'public') : $existing->foto,
                ]);
            } else {
                KendalaModel::create([
                    'rambu_pasang_id' => $item->id,
                    'dilaporkan_oleh' => Auth::id(),
                    'alasan' => $this->alasan,
                    'foto' => $this->foto->store('kendala', 'public'),
                ]);
            }

            $item->update(['status' => StatusRambuPasang::Tertunda]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'spk_id' => $item->rambu_spk_id,
                'aksi' => 'kendala_diajukan',
                'keterangan' => "Kendala diajukan/diperbarui untuk rambu di {$item->rambu->wilayah}, {$item->rambu->lokasi}.",
            ]);

            Notifikasi::create([
                'user_id' => $item->spk->dibuat_oleh,
                'judul' => 'Kendala Dilaporkan',
                'pesan' => "Petugas melaporkan kendala untuk SPK {$item->spk->nomor_surat} di {$item->rambu->wilayah}, {$item->rambu->lokasi}: {$this->alasan}",
                'dibaca' => false,
            ]);
        });

        Flux::toast(variant: 'success', text: 'Kendala berhasil disimpan. Setelah semua rambu ditangani, ajukan laporan akhir dari halaman Detail Surat.');

        $this->back();
    }

    public function with(): array
    {
        $item = $this->currentItem();

        $catatanPenolakanSebelumnya = $item && $item->status === StatusRambuPasang::Revisi
            ? $item->laporanPengerjaan()->where('status', StatusLaporan::Ditolak->value)->latest()->value('catatan_penolakan')
            : null;

        return [
            'item' => $item,
            'catatanPenolakanSebelumnya' => $catatanPenolakanSebelumnya,
            'items' => $item ? collect() : RambuPasang::with(['rambu', 'spk'])
                ->whereIn('rambu_spk_id', $this->eligibleSpkIds())
                ->whereIn('status', self::WORKABLE)
                ->get(),
        ];
    }

    public function render()
    {
        return view('pages::user.kendala');
    }
}
