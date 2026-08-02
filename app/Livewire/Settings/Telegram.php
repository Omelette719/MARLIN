<?php

namespace App\Livewire\Settings;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Notifikasi Telegram')]
class Telegram extends Component
{
    // Regenerated on every "Hubungkan Telegram" click and consumed once by
    // telegram:poll when the user sends /start <token> to the bot — after
    // that it's cleared, whether it succeeded or a stale link is retried.
    public function hubungkan(): void
    {
        Auth::user()->update(['telegram_link_token' => Str::random(32)]);
    }

    public function putuskan(): void
    {
        Auth::user()->update(['telegram_chat_id' => null, 'telegram_link_token' => null]);

        Flux::toast(variant: 'success', text: 'Koneksi Telegram diputuskan.');
    }

    public function with(): array
    {
        $user = Auth::user()->fresh();

        return [
            'terhubung' => (bool) $user->telegram_chat_id,
            'linkUrl' => $user->telegram_link_token
                ? 'https://t.me/'.config('services.telegram.bot_username').'?start='.$user->telegram_link_token
                : null,
        ];
    }

    public function render()
    {
        return view('pages::settings.telegram');
    }
}
