<?php

namespace App\Livewire\User;

use App\Enums\StatusRambuPasang;
use App\Models\AuditLog;
use App\Models\DikerjakanOleh;
use App\Models\Kendala as KendalaModel;
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
    use WithFileUploads;

    #[Url]
    public ?int $rambuPasangId = null;

    public string $alasan = '';

    public $foto = null;

    private const WORKABLE = [StatusRambuPasang::Belum->value, StatusRambuPasang::Revisi->value];

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
        $this->reset('alasan', 'foto');
    }

    public function back(): void
    {
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

        $this->validate([
            'alasan' => 'required|string|max:1000',
            'foto' => 'required|image|max:5120',
        ]);

        DB::transaction(function () use ($item) {
            KendalaModel::create([
                'rambu_pasang_id' => $item->id,
                'dilaporkan_oleh' => Auth::id(),
                'alasan' => $this->alasan,
                'foto' => $this->foto->store('kendala', 'public'),
            ]);

            $item->update(['status' => StatusRambuPasang::Tertunda]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'spk_id' => $item->rambu_spk_id,
                'aksi' => 'kendala_diajukan',
                'keterangan' => "Kendala diajukan untuk rambu di {$item->rambu->wilayah}, {$item->rambu->lokasi}.",
            ]);
        });

        Flux::toast(variant: 'success', text: 'Kendala berhasil diajukan. Setelah semua rambu ditangani, ajukan laporan akhir dari halaman Detail Surat.');

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
        return view('pages::user.kendala');
    }
}
