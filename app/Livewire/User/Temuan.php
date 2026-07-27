<?php

namespace App\Livewire\User;

use App\Enums\KondisiRambu;
use App\Enums\Role;
use App\Livewire\Concerns\RejectsNonImageUploads;
use App\Models\AuditLog;
use App\Models\LaporanKondisi;
use App\Models\Notifikasi;
use App\Models\Rambu;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Laporan Temuan Kondisi')]
class Temuan extends Component
{
    use RejectsNonImageUploads;
    use WithFileUploads;

    #[Url]
    public string $rambu_id = '';

    public string $kondisi_dilaporkan = 'rusak';

    public $foto = null;

    public string $catatan = '';

    public function submit(): void
    {
        $this->validate([
            'rambu_id' => 'required|exists:rambu,id',
            'kondisi_dilaporkan' => 'required|in:baik,rusak',
            'foto' => 'required|image|max:5120',
            'catatan' => 'nullable|string|max:1000',
        ]);

        $rambu = Rambu::findOrFail($this->rambu_id);

        DB::transaction(function () use ($rambu) {
            LaporanKondisi::create([
                'rambu_id' => $rambu->id,
                'dilaporkan_oleh' => Auth::id(),
                'kondisi_dilaporkan' => $this->kondisi_dilaporkan,
                'foto' => $this->foto->store('laporan-kondisi', 'public'),
                'catatan' => $this->catatan ?: null,
            ]);

            $rambu->update(['kondisi_terkini' => $this->kondisi_dilaporkan]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'aksi' => 'laporan_kondisi_dibuat',
                'keterangan' => "Temuan kondisi ({$this->kondisi_dilaporkan}) dilaporkan untuk rambu di {$rambu->wilayah}, {$rambu->lokasi}.",
            ]);

            foreach (User::where('role', Role::Admin)->get() as $admin) {
                Notifikasi::create([
                    'user_id' => $admin->id,
                    'judul' => 'Temuan Kondisi Rambu',
                    'pesan' => "Petugas melaporkan rambu di {$rambu->wilayah}, {$rambu->lokasi} berkondisi {$this->kondisi_dilaporkan}.",
                    'dibaca' => false,
                ]);
            }
        });

        Flux::toast(variant: 'success', text: 'Temuan kondisi berhasil dilaporkan.');

        $this->reset('rambu_id', 'foto', 'catatan');
        $this->kondisi_dilaporkan = 'rusak';
    }

    public function with(): array
    {
        $rambuOptions = Rambu::with('jenisRambu')->orderBy('wilayah')->get();

        return [
            'rambuOptions' => $rambuOptions,
            'rambuSelectOptions' => $rambuOptions->map(fn ($r) => [
                'value' => (string) $r->id,
                'label' => "{$r->wilayah}, {$r->lokasi} ({$r->jenisRambu?->nama_jenis})",
            ])->values(),
            'kondisiOptions' => KondisiRambu::cases(),
        ];
    }

    public function render()
    {
        return view('pages::user.temuan');
    }
}
