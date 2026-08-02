<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private function endpoint(string $method): string
    {
        return 'https://api.telegram.org/bot'.config('services.telegram.bot_token').'/'.$method;
    }

    // Failures are swallowed (logged only) so a Telegram outage never breaks
    // the queued job that called this, or crashes the telegram:poll daemon
    // mid-loop over the rest of that batch's updates.
    public function sendMessage(string $chatId, string $text, ?string $url = null): void
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($url) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => [[
                    ['text' => 'Buka Halaman', 'url' => $url],
                ]],
            ]);
        }

        try {
            $response = Http::asForm()->post($this->endpoint('sendMessage'), $payload);

            if ($response->failed()) {
                Log::error('Telegram sendMessage failed', ['chat_id' => $chatId, 'body' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage exception', ['chat_id' => $chatId, 'message' => $e->getMessage()]);
        }
    }

    public function getUpdates(int $offset, int $timeout = 30): array
    {
        try {
            $response = Http::timeout($timeout + 10)->get($this->endpoint('getUpdates'), [
                'offset' => $offset,
                'timeout' => $timeout,
                'allowed_updates' => json_encode(['message']),
            ]);

            if ($response->failed()) {
                Log::error('Telegram getUpdates failed', ['body' => $response->body()]);

                return [];
            }

            return $response->json('result', []);
        } catch (\Throwable $e) {
            Log::error('Telegram getUpdates exception', ['message' => $e->getMessage()]);

            return [];
        }
    }
}
