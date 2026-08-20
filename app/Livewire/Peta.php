<?php

namespace App\Livewire;

use App\Models\JenisRambu;
use App\Support\PetaData;
use App\Support\WilayahBanjarmasin;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Peta Rambu')]
class Peta extends Component
{
    #[Url]
    public ?int $focus = null;

    public function with(): array
    {
        return [
            'isAdmin' => Auth::user()->isAdmin(),
            'jenisRambuOptions' => JenisRambu::orderBy('nama_jenis')->get(),
            // Unlike the dashboard widgets' "perlu perhatian" bias (which
            // hides the Selesai tingkat from its own dropdown since it's
            // excluded from view by default), this is the full explorable
            // map — every tingkat, including Selesai / Kondisi Baik, is a
            // legitimate thing to filter down to here.
            'tingkatOptions' => PetaData::TINGKAT_LABELS,
            'kecamatanOptions' => WilayahBanjarmasin::kecamatanOptions(),
            'kelurahanOptions' => WilayahBanjarmasin::kelurahanOptions(),
        ];
    }

    public function render()
    {
        return view('pages::peta');
    }
}
