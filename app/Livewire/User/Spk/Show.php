<?php

namespace App\Livewire\User\Spk;

use App\Enums\Role;
use App\Enums\StatusRambuPasang;
use App\Models\AuditLog;
use App\Models\DikerjakanOleh;
use App\Models\Notifikasi;
use App\Models\Spk;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Detail Surat')]
class Show extends Component
{
    public Spk $spk;

    public array $anggotaIds = [];

    public function mount(Spk $spk): void
    {
        $this->spk = $spk;
    }

    public function getSudahBergabungProperty(): bool
    {
        return DikerjakanOleh::where('by_spk_id', $this->spk->id)
            ->where('by_user_id', Auth::id())
            ->exists();
    }

    public function getSayaPerwakilanProperty(): bool
    {
        return DikerjakanOleh::where('by_spk_id', $this->spk->id)
            ->where('by_user_id', Auth::id())
            ->where('is_perwakilan', true)
            ->exists();
    }

    private function existingTeamUserIds(): array
    {
        return DikerjakanOleh::where('by_spk_id', $this->spk->id)->pluck('by_user_id')->all();
    }

    // Registering is not "joining a task" — it's the perwakilan registering
    // their whole team for this surat in one go. Nobody else can join
    // themselves; the perwakilan adds everyone up front (or later).
    public function daftarkanTim(): void
    {
        if ($this->sudahBergabung || DikerjakanOleh::where('by_spk_id', $this->spk->id)->exists()) {
            return;
        }

        $this->validate([
            'anggotaIds' => 'array',
            'anggotaIds.*' => 'exists:users,id',
        ]);

        DikerjakanOleh::create([
            'by_spk_id' => $this->spk->id,
            'by_user_id' => Auth::id(),
            'is_perwakilan' => true,
        ]);

        foreach (array_unique($this->anggotaIds) as $userId) {
            if ((int) $userId === Auth::id()) {
                continue;
            }

            DikerjakanOleh::create([
                'by_spk_id' => $this->spk->id,
                'by_user_id' => $userId,
                'is_perwakilan' => false,
            ]);
        }

        $this->reset('anggotaIds');

        Flux::modal('daftarkan-tim')->close();
        Flux::toast(variant: 'success', text: 'Tim berhasil didaftarkan untuk surat ini.');
    }

    public function tambahAnggota(): void
    {
        if (! $this->sayaPerwakilan) {
            return;
        }

        $this->validate([
            'anggotaIds' => 'array',
            'anggotaIds.*' => 'exists:users,id',
        ]);

        $existing = $this->existingTeamUserIds();

        foreach (array_unique($this->anggotaIds) as $userId) {
            if (in_array((int) $userId, $existing, true)) {
                continue;
            }

            DikerjakanOleh::create([
                'by_spk_id' => $this->spk->id,
                'by_user_id' => $userId,
                'is_perwakilan' => false,
            ]);
        }

        $this->reset('anggotaIds');

        Flux::modal('tambah-anggota')->close();
        Flux::toast(variant: 'success', text: 'Anggota tim berhasil ditambahkan.');
    }

    // Perwakilan-only, and the perwakilan row itself can never be removed
    // this way (that would leave the SPK with no accountable reporter) —
    // they'd need to be swapped out by re-registering the team, which isn't
    // something this action does.
    public function hapusAnggota(int $dikerjakanOlehId): void
    {
        if (! $this->sayaPerwakilan) {
            return;
        }

        $anggota = DikerjakanOleh::where('by_spk_id', $this->spk->id)
            ->where('id', $dikerjakanOlehId)
            ->where('is_perwakilan', false)
            ->with('user')
            ->first();

        if (! $anggota) {
            return;
        }

        $nama = $anggota->user->name;
        $userId = $anggota->by_user_id;

        $anggota->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'spk_id' => $this->spk->id,
            'aksi' => 'anggota_tim_dihapus',
            'keterangan' => "{$nama} dikeluarkan dari tim SPK {$this->spk->nomor_surat}.",
        ]);

        Notifikasi::create([
            'user_id' => $userId,
            'judul' => 'Dikeluarkan dari Tim',
            'pesan' => "Kamu dikeluarkan dari tim SPK {$this->spk->nomor_surat} oleh perwakilan tim.",
            'url' => route('user.spk.show', $this->spk),
            'dibaca' => false,
        ]);

        Flux::toast(variant: 'success', text: "{$nama} berhasil dihapus dari tim.");
    }

    // "Ready" means: nothing is still untouched this round (Belum/Urgent), and
    // nothing sent back for rework has been ignored (Revisi) — but rambu that
    // are already Selesai from an earlier accepted validation round DO count
    // as settled, they just don't need to be resubmitted. Without allowing
    // Selesai here, an SPK that goes through even one partial reject/redo
    // cycle could never be marked ready again: once any rambu reaches Selesai
    // while another is still being redone, this would always evaluate false,
    // permanently locking the SPK out of ever reaching admin's validation
    // queue again. Requiring at least one Tertunda/MenungguValidasi rambu
    // stops an SPK where literally everything is already Selesai from being
    // considered "ready" with nothing new to actually submit.
    public function getSiapDiajukanProperty(): bool
    {
        $statuses = $this->spk->rambuPasang()
            ->get()
            ->pluck('status')
            ->reject(fn ($s) => $s === StatusRambuPasang::Batal);

        $adaYangBaruDiajukan = $statuses->contains(
            fn ($s) => in_array($s, [StatusRambuPasang::Tertunda, StatusRambuPasang::MenungguValidasi], true)
        );

        return $adaYangBaruDiajukan && $statuses->every(
            fn ($s) => in_array($s, [
                StatusRambuPasang::Tertunda,
                StatusRambuPasang::MenungguValidasi,
                StatusRambuPasang::Selesai,
            ], true)
        );
    }

    public function ajukanLaporanAkhir(): void
    {
        if (! $this->sayaPerwakilan || ! $this->siapDiajukan || $this->spk->laporan_akhir_diajukan_at) {
            return;
        }

        $this->spk->update(['laporan_akhir_diajukan_at' => now()]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'spk_id' => $this->spk->id,
            'aksi' => 'laporan_akhir_diajukan',
            'keterangan' => "Laporan akhir diajukan untuk SPK {$this->spk->nomor_surat}.",
        ]);

        Notifikasi::create([
            'user_id' => $this->spk->dibuat_oleh,
            'judul' => 'Laporan Akhir Masuk',
            'pesan' => "Laporan akhir untuk SPK {$this->spk->nomor_surat} siap divalidasi.",
            'url' => route('admin.validasi.show', $this->spk),
            'dibaca' => false,
        ]);

        Flux::modal('ajukan-laporan-akhir')->close();
        Flux::toast(variant: 'success', text: 'Laporan akhir berhasil diajukan ke admin.');
    }

    public function with(): array
    {
        $tim = $this->spk->dikerjakanOleh()->with('user')->get();
        $existingIds = $tim->pluck('by_user_id')->all();

        $userOptions = User::where('role', Role::User)
            ->where('aktif', true)
            ->whereNotIn('id', $existingIds)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'value' => (string) $u->id,
                'label' => "{$u->name} (NIP {$u->nip})",
            ])
            ->values();

        return [
            'tim' => $tim,
            'perwakilan' => $tim->firstWhere('is_perwakilan', true),
            'userOptions' => $userOptions,
            'rambuPasang' => $this->spk->rambuPasang()
                ->with(['rambu.jenisRambu', 'laporanPengerjaan' => fn ($q) => $q->latest()])
                ->get(),
        ];
    }

    public function render()
    {
        return view('pages::user.spk.show');
    }
}
