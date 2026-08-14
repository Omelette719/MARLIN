<?php

namespace App\Livewire;

use App\Models\AuditLog as AuditLogModel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Riwayat Aktivitas')]
class AuditLog extends Component
{
    use WithPagination;

    #[Url]
    public string $aksi = '';

    // Only meaningful for admin — petugas already only ever see their own
    // rows via AuditLogModel::visibleTo(), so a "pengguna" filter for them
    // would just be a dropdown with one useless option (themselves).
    #[Url]
    public string $pengguna = '';

    #[Url]
    public string $tanggal_dari = '';

    #[Url]
    public string $tanggal_sampai = '';

    public function updatedAksi(): void
    {
        $this->resetPage();
    }

    public function updatedPengguna(): void
    {
        $this->resetPage();
    }

    public function updatedTanggalDari(): void
    {
        $this->resetPage();
    }

    public function updatedTanggalSampai(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('pages::audit-log');
    }

    public function with(): array
    {
        $user = Auth::user();

        $query = AuditLogModel::visibleTo($user)
            ->with(['user', 'spk'])
            ->latest();

        if ($this->aksi) {
            $query->where('aksi', $this->aksi);
        }

        if ($user->isAdmin() && $this->pengguna) {
            $query->where('user_id', $this->pengguna);
        }

        if ($this->tanggal_dari) {
            $query->whereDate('created_at', '>=', $this->tanggal_dari);
        }

        if ($this->tanggal_sampai) {
            $query->whereDate('created_at', '<=', $this->tanggal_sampai);
        }

        return [
            'logs' => $query->paginate(20),
            'aksiOptions' => AuditLogModel::visibleTo($user)->distinct()->pluck('aksi'),
            'penggunaOptions' => $user->isAdmin() ? User::orderBy('name')->get(['id', 'name']) : [],
        ];
    }
}
