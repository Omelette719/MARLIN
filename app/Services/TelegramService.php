<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramService
{
    private function endpoint(string $method): string
    {
        return 'https://api.telegram.org/bot'.config('services.telegram.bot_token').'/'.$method;
    }

    private function replyMarkup(?string $url): ?string
    {
        if (! $url || ! $this->isPubliclyReachable($url)) {
            return null;
        }

        return json_encode([
            'inline_keyboard' => [[
                ['text' => 'Buka Halaman', 'url' => $url],
            ]],
        ]);
    }

    // Telegram validates inline keyboard button URLs server-side and rejects
    // the ENTIRE sendMessage/sendPhoto call (not just the button) with a 400
    // if the host isn't a real, publicly-resolvable one — localhost, 127.*,
    // and .test/.local dev domains all get "Wrong HTTP URL". Since the
    // message text is worth delivering even without a working button, this
    // just drops the button instead of losing the whole notification.
    private function isPubliclyReachable(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            return false;
        }

        return ! preg_match('/\.(test|local|localhost|internal)$/i', $host);
    }

    // Failures are swallowed (logged only) so a Telegram outage never breaks
    // the queued job that called this, or crashes the telegram:poll daemon
    // mid-loop over the rest of that batch's updates.
    public function sendMessage(string $chatId, string $text, ?string $url = null): void
    {
        $payload = array_filter([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $this->replyMarkup($url),
        ]);

        try {
            $response = Http::asForm()->post($this->endpoint('sendMessage'), $payload);

            if ($response->failed()) {
                Log::error('Telegram sendMessage failed', ['chat_id' => $chatId, 'body' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage exception', ['chat_id' => $chatId, 'message' => $e->getMessage()]);
        }
    }

    // Uploads the file's actual bytes (multipart) rather than passing a URL
    // for Telegram to fetch itself — this app's storage disk isn't
    // necessarily publicly reachable (e.g. still on a local/dev APP_URL), so
    // fetching it ourselves and pushing the bytes is the only approach that
    // works regardless of deployment. Falls back to a plain text message if
    // the referenced file no longer exists on disk.
    public function sendPhoto(string $chatId, string $fotoPath, string $caption, ?string $url = null): void
    {
        if (! Storage::disk('public')->exists($fotoPath)) {
            $this->sendMessage($chatId, $caption, $url);

            return;
        }

        // Telegram caption limit is 1024 chars, well short of sendMessage's 4096.
        $fields = array_filter([
            'chat_id' => $chatId,
            'caption' => mb_substr($caption, 0, 1024),
            'reply_markup' => $this->replyMarkup($url),
        ]);

        try {
            $response = Http::attach('photo', Storage::disk('public')->get($fotoPath), basename($fotoPath))
                ->post($this->endpoint('sendPhoto'), $fields);

            if ($response->failed()) {
                Log::error('Telegram sendPhoto failed', ['chat_id' => $chatId, 'body' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram sendPhoto exception', ['chat_id' => $chatId, 'message' => $e->getMessage()]);
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
