<?php

namespace App\Livewire\Admin\Spk;

use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Models\AuditLog;
use App\Models\Spk;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Detail Surat')]
class Show extends Component
{
    public Spk $spk;

    public function mount(Spk $spk): void
    {
        $this->spk = $spk;
    }

    public function batalkan(): void
    {
        if ($this->spk->status !== StatusSpk::Aktif) {
            return;
        }

        DB::transaction(function () {
            $this->spk->rambuPasang()
                ->whereNotIn('status', [StatusRambuPasang::Selesai->value, StatusRambuPasang::Batal->value])
                ->update(['status' => StatusRambuPasang::Batal->value]);

            $this->spk->update(['status' => StatusSpk::Dibatalkan]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'spk_id' => $this->spk->id,
                'aksi' => 'spk_dibatalkan',
                'keterangan' => "SPK {$this->spk->nomor_surat} dibatalkan.",
            ]);
        });

        $this->spk->refresh();

        Flux::toast(variant: 'success', text: "Surat {$this->spk->nomor_surat} berhasil dibatalkan.");
    }

    public function with(): array
    {
        return [
            'tim' => $this->spk->dikerjakanOleh()->with('user')->get(),
            'rambuPasang' => $this->spk->rambuPasang()->with('rambu.jenisRambu')->get(),
        ];
    }

    public function render()
    {
        return view('pages::admin.spk.show');
    }
}
