<?php

namespace App\Observers;

use App\Jobs\SendTelegramNotifikasi;
use App\Models\Notifikasi;

class NotifikasiObserver
{
    // Hooking in here means every existing (and future) Notifikasi::create()
    // call site gets Telegram delivery for free, with no changes to any of
    // them.
    public function created(Notifikasi $notifikasi): void
    {
        if ($notifikasi->user?->telegram_chat_id) {
            SendTelegramNotifikasi::dispatch($notifikasi);
        }
    }
}
