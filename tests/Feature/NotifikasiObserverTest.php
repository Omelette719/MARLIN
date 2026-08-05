<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramNotifikasi;
use App\Models\Notifikasi;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotifikasiObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_notifikasi_dispatches_telegram_job_when_user_has_linked_telegram(): void
    {
        Queue::fake();

        $user = User::factory()->create(['telegram_chat_id' => '999']);

        $notif = Notifikasi::create(['user_id' => $user->id, 'judul' => 'Judul', 'pesan' => 'isi', 'dibaca' => false]);

        Queue::assertPushed(SendTelegramNotifikasi::class, fn ($job) => $job->notifikasi->is($notif));
    }

    public function test_creating_notifikasi_does_not_dispatch_telegram_job_when_user_has_no_linked_telegram(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Notifikasi::create(['user_id' => $user->id, 'judul' => 'Judul', 'pesan' => 'isi', 'dibaca' => false]);

        Queue::assertNotPushed(SendTelegramNotifikasi::class);
    }

    public function test_telegram_job_sends_message_and_skips_when_unlinked_before_it_runs(): void
    {
        $user = User::factory()->create(['telegram_chat_id' => '999']);
        $notif = Notifikasi::create(['user_id' => $user->id, 'judul' => 'Judul', 'pesan' => 'isi', 'url' => '/dashboard', 'dibaca' => false]);

        Http::fake();

        (new SendTelegramNotifikasi($notif))->handle(new TelegramService);

        Http::assertSentCount(1);

        // Unlink, then the job should no-op instead of erroring.
        $user->update(['telegram_chat_id' => null]);
        Http::fake();

        (new SendTelegramNotifikasi($notif))->handle(new TelegramService);

        Http::assertNothingSent();
    }

    public function test_telegram_job_sends_photo_instead_of_plain_message_when_notifikasi_has_foto(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('kendala/contoh.jpg', 'fake-image-bytes');

        $user = User::factory()->create(['telegram_chat_id' => '999']);
        $notif = Notifikasi::create([
            'user_id' => $user->id,
            'judul' => 'Kendala Dilaporkan',
            'pesan' => 'isi',
            'foto' => 'kendala/contoh.jpg',
            'dibaca' => false,
        ]);

        Http::fake();

        (new SendTelegramNotifikasi($notif))->handle(new TelegramService);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendPhoto'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/sendMessage'));
    }
}
