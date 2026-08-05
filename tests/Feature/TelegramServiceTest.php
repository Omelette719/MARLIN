<?php

namespace Tests\Feature;

use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TelegramServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.telegram.bot_token' => 'test-token']);
    }

    public function test_send_message_posts_chat_id_and_text(): void
    {
        Http::fake();

        (new TelegramService)->sendMessage('12345', 'Halo dunia');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
                && $request['chat_id'] === '12345'
                && $request['text'] === 'Halo dunia'
                && ! isset($request['reply_markup']);
        });
    }

    public function test_send_message_attaches_inline_button_when_url_given(): void
    {
        Http::fake();

        (new TelegramService)->sendMessage('12345', 'Halo', 'https://example.test/spk/1');

        Http::assertSent(function ($request) {
            $markup = json_decode($request['reply_markup'], true);

            return $markup['inline_keyboard'][0][0]['url'] === 'https://example.test/spk/1'
                && $markup['inline_keyboard'][0][0]['text'] === 'Buka Halaman';
        });
    }

    public function test_send_message_failure_does_not_throw(): void
    {
        Http::fake(['*' => Http::response('error', 500)]);

        (new TelegramService)->sendMessage('12345', 'Halo');

        Http::assertSentCount(1);
    }

    public function test_get_updates_sends_offset_and_timeout(): void
    {
        Http::fake(['*' => Http::response(['ok' => true, 'result' => [['update_id' => 1]]])]);

        $result = (new TelegramService)->getUpdates(offset: 42, timeout: 5);

        $this->assertSame([['update_id' => 1]], $result);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://api.telegram.org/bottest-token/getUpdates')
                && $request->data()['offset'] === 42
                && $request->data()['timeout'] === 5;
        });
    }

    public function test_get_updates_returns_empty_array_on_failure(): void
    {
        Http::fake(['*' => Http::response('error', 500)]);

        $result = (new TelegramService)->getUpdates(0);

        $this->assertSame([], $result);
    }

    public function test_send_photo_uploads_the_file_with_a_caption(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('kendala/contoh.jpg', 'fake-image-bytes');
        Http::fake();

        (new TelegramService)->sendPhoto('12345', 'kendala/contoh.jpg', 'Ada kendala baru');

        // Multipart request bodies aren't exposed via array access like
        // form/json ones are — assert against the raw body instead.
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendPhoto')
                && str_contains($request->body(), 'name="chat_id"')
                && str_contains($request->body(), '12345')
                && str_contains($request->body(), 'name="caption"')
                && str_contains($request->body(), 'Ada kendala baru')
                && str_contains($request->body(), 'name="photo"')
                && str_contains($request->body(), 'fake-image-bytes');
        });
    }

    public function test_send_photo_attaches_inline_button_when_url_given(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('kendala/contoh.jpg', 'fake-image-bytes');
        Http::fake();

        (new TelegramService)->sendPhoto('12345', 'kendala/contoh.jpg', 'Ada kendala baru', 'https://example.test/spk/1');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendPhoto')
                && str_contains($request->body(), 'name="reply_markup"')
                && str_contains($request->body(), 'https:\/\/example.test\/spk\/1');
        });
    }

    public function test_send_photo_falls_back_to_plain_message_when_file_missing(): void
    {
        Storage::fake('public');
        Http::fake();

        (new TelegramService)->sendPhoto('12345', 'kendala/tidak-ada.jpg', 'Ada kendala baru');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendMessage')
                && $request['text'] === 'Ada kendala baru';
        });
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/sendPhoto'));
    }
}
