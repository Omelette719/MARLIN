<?php

namespace App\Livewire\Admin\SystemErrorLog;

use App\Enums\ErrorLevel;
use App\Models\SystemErrorLog;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Log Error Sistem')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $level = '';

    public function updatedLevel(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'logs' => SystemErrorLog::query()
                ->with('user')
                ->when($this->level, fn ($q) => $q->where('level', $this->level))
                ->latest()
                ->paginate(20),
            'levelOptions' => ErrorLevel::cases(),
        ];
    }

    public function render()
    {
        return view('pages::admin.system-error-log.index');
    }
}
