<?php

namespace App\Livewire\Admin\JenisRambu;

use App\Livewire\Concerns\RejectsNonImageUploads;
use App\Models\JenisRambu;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Jenis Rambu')]
class Index extends Component
{
    use RejectsNonImageUploads;
    use WithFileUploads;

    public ?int $editingId = null;

    public string $nama_jenis = '';

    public string $spesifikasi_standar = '';

    public string $bentuk_ikon = 'bulat';

    public $gambar_referensi = null;

    public function tambahBaru(): void
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $this->reset('editingId', 'nama_jenis', 'spesifikasi_standar', 'gambar_referensi');
        $this->bentuk_ikon = 'bulat';
        $this->resetErrorBag();

        Flux::modal('jenis-rambu-form')->show();
    }

    public function edit(int $id): void
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $jenis = JenisRambu::findOrFail($id);

        $this->editingId = $jenis->id;
        $this->nama_jenis = $jenis->nama_jenis;
        $this->spesifikasi_standar = (string) $jenis->spesifikasi_standar;
        $this->bentuk_ikon = $jenis->bentuk_ikon->value;
        $this->gambar_referensi = null;
        $this->resetErrorBag();

        Flux::modal('jenis-rambu-form')->show();
    }

    public function save(): void
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $validated = $this->validate([
            'nama_jenis' => 'required|string|max:255',
            'spesifikasi_standar' => 'nullable|string|max:1000',
            'bentuk_ikon' => 'required|in:bulat,kotak',
            'gambar_referensi' => 'nullable|image|max:5120',
        ]);

        if ($this->gambar_referensi) {
            $validated['gambar_referensi'] = $this->gambar_referensi->store('jenis-rambu', 'public');
        } else {
            unset($validated['gambar_referensi']);
        }

        if ($this->editingId) {
            JenisRambu::findOrFail($this->editingId)->update($validated);
            Flux::toast(variant: 'success', text: 'Jenis rambu berhasil diperbarui.');
        } else {
            JenisRambu::create($validated);
            Flux::toast(variant: 'success', text: 'Jenis rambu berhasil ditambahkan.');
        }

        Flux::modal('jenis-rambu-form')->close();
        $this->reset('editingId', 'nama_jenis', 'spesifikasi_standar', 'gambar_referensi');
        $this->bentuk_ikon = 'bulat';
    }

    public function hapus(int $id): void
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        try {
            JenisRambu::findOrFail($id)->delete();
            Flux::toast(variant: 'success', text: 'Jenis rambu berhasil dihapus.');
        } catch (QueryException $e) {
            Flux::toast(variant: 'danger', text: 'Tidak bisa dihapus, masih dipakai oleh data rambu yang ada.');
        }
    }

    public function lihatRambu(int $id): void
    {
        $route = Auth::user()->isAdmin() ? 'admin.rambu.index' : 'rambu.index';

        $this->redirectRoute($route, ['jenis' => $id], navigate: true);
    }

    public function with(): array
    {
        return [
            'jenisRambu' => JenisRambu::withCount('rambu')->orderBy('nama_jenis')->get(),
            'isAdmin' => Auth::user()->isAdmin(),
        ];
    }

    public function render()
    {
        return view('pages::admin.jenis-rambu.index');
    }
}
