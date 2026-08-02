<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramPoll extends Command
{
    protected $signature = 'telegram:poll {--once : Run a single getUpdates cycle then exit, for tests}';

    protected $description = 'Long-poll the Telegram Bot API to receive /start account-linking confirmations';

    public function handle(TelegramService $telegram): int
    {
        $this->info('Listening for Telegram updates'.($this->option('once') ? ' (single cycle)...' : '... (Ctrl+C to stop)'));

        do {
            $this->cycle($telegram);
        } while (! $this->option('once'));

        return self::SUCCESS;
    }

    private function cycle(TelegramService $telegram): void
    {
        $offset = (int) Cache::get('telegram_offset', 0);

        try {
            $updates = $telegram->getUpdates($offset);

            foreach ($updates as $update) {
                $this->processUpdate($telegram, $update);
                Cache::forever('telegram_offset', $update['update_id'] + 1);
            }
        } catch (\Throwable $e) {
            // One bad update/API hiccup should never kill the whole daemon.
            Log::error('telegram:poll cycle failed', ['message' => $e->getMessage()]);
        }
    }

    private function processUpdate(TelegramService $telegram, array $update): void
    {
        $message = $update['message'] ?? null;
        $text = trim($message['text'] ?? '');
        $chatId = (string) ($message['chat']['id'] ?? '');

        if ($chatId === '') {
            return;
        }

        if (! str_starts_with($text, '/start')) {
            $telegram->sendMessage($chatId, 'Perintah tidak dikenali. Gunakan link dari halaman Settings > Telegram di Sistem MARLIN untuk menghubungkan akun.');

            return;
        }

        $token = trim(substr($text, strlen('/start')));

        if ($token === '') {
            $telegram->sendMessage($chatId, 'Silakan hubungkan akun Sistem MARLIN kamu dari halaman Settings > Telegram terlebih dahulu.');

            return;
        }

        $user = User::where('telegram_link_token', $token)->first();

        if (! $user) {
            $telegram->sendMessage($chatId, 'Link tidak valid atau sudah kedaluwarsa. Buat link baru dari halaman Settings > Telegram di Sistem MARLIN.');

            return;
        }

        $sudahDipakai = User::where('telegram_chat_id', $chatId)->where('id', '!=', $user->id)->exists();

        if ($sudahDipakai) {
            $telegram->sendMessage($chatId, 'Akun Telegram ini sudah terhubung ke akun Sistem MARLIN lain. Putuskan koneksi itu dulu sebelum menghubungkan akun baru.');

            return;
        }

        $user->update(['telegram_chat_id' => $chatId, 'telegram_link_token' => null]);

        $telegram->sendMessage($chatId, "Berhasil! Notifikasi Sistem MARLIN untuk {$user->name} akan dikirim ke chat ini.");
    }
}
