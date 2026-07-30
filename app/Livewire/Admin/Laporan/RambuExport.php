<?php

namespace App\Livewire\Admin\Laporan;

use App\Enums\StatusRambuPasang;
use App\Models\JenisRambu;
use App\Support\LaporanRambu;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Laporan Rambu')]
class RambuExport extends Component
{
    #[Url]
    public string $tanggal_dari = '';

    #[Url]
    public string $tanggal_sampai = '';

    #[Url]
    public string $jenis_rambu_id = '';

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        if (! $this->tanggal_dari) {
            $this->tanggal_dari = now()->startOfMonth()->toDateString();
        }

        if (! $this->tanggal_sampai) {
            $this->tanggal_sampai = now()->toDateString();
        }
    }

    private function filters(): array
    {
        return [
            'tanggal_dari' => $this->tanggal_dari ?: null,
            'tanggal_sampai' => $this->tanggal_sampai ?: null,
            'jenis_rambu_id' => $this->jenis_rambu_id ?: null,
            'status' => $this->status ?: null,
        ];
    }

    public function with(): array
    {
        return array_merge(LaporanRambu::build($this->filters()), [
            'jenisRambuOptions' => JenisRambu::orderBy('nama_jenis')->get(),
            'statusOptions' => StatusRambuPasang::cases(),
        ]);
    }

    public function render()
    {
        return view('pages::admin.laporan.rambu');
    }
}
