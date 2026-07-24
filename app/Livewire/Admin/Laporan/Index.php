<?php

namespace App\Livewire\Admin\Laporan;

use App\Support\LaporanBulanan;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Laporan Bulanan')]
class Index extends Component
{
    #[Url]
    public string $bulan = '';

    public function mount(): void
    {
        if (! $this->bulan) {
            $this->bulan = now()->format('Y-m');
        }
    }

    public function with(): array
    {
        return LaporanBulanan::build($this->bulan);
    }

    public function render()
    {
        return view('pages::admin.laporan.index');
    }
}
