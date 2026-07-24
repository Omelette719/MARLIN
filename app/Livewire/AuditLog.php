<?php

namespace App\Livewire;

use App\Models\AuditLog as AuditLogModel;
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

    public function updatedAksi(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('pages::audit-log');
    }

    public function with(): array
    {
        $query = AuditLogModel::visibleTo(Auth::user())
            ->with(['user', 'spk'])
            ->latest();

        if ($this->aksi) {
            $query->where('aksi', $this->aksi);
        }

        return [
            'logs' => $query->paginate(20),
            'aksiOptions' => AuditLogModel::visibleTo(Auth::user())->distinct()->pluck('aksi'),
        ];
    }
}
