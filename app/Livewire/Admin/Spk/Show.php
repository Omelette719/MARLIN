<?php

namespace App\Livewire\Admin\Spk;

use App\Models\Spk;
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
