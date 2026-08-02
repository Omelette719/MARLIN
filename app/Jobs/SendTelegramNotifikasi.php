<?php

namespace App\Jobs;

use App\Models\Notifikasi;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTelegramNotifikasi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Notifikasi $notifikasi) {}

    public function handle(TelegramService $telegram): void
    {
        $notifikasi = $this->notifikasi->fresh('user');

        // The user may have unlinked their Telegram account between this job
        // being queued and actually running.
        if (! $notifikasi || ! $notifikasi->user?->telegram_chat_id) {
            return;
        }

        $telegram->sendMessage(
            $notifikasi->user->telegram_chat_id,
            "{$notifikasi->judul}\n\n{$notifikasi->pesan}",
            $notifikasi->url,
        );
    }
}
