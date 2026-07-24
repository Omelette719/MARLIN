<?php

namespace App\Livewire;

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
        ];
    }

    public function render()
    {
        return view('pages::peta');
    }
}
