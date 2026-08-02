<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramPollCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('telegram_offset');
        config(['services.telegram.bot_token' => 'test-token']);
    }

    private function fakeGetUpdates(array $messages): void
    {
        Http::fake([
            '*getUpdates*' => Http::response(['ok' => true, 'result' => $messages]),
            '*sendMessage*' => Http::response(['ok' => true]),
        ]);
    }

    public function test_valid_start_token_links_the_user_and_clears_the_token(): void
    {
        $user = User::factory()->create(['telegram_link_token' => 'abc123']);

        $this->fakeGetUpdates([[
            'update_id' => 1,
            'message' => ['text' => '/start abc123', 'chat' => ['id' => 555]],
        ]]);

        $this->artisan('telegram:poll', ['--once' => true])->assertSuccessful();

        $user->refresh();
        $this->assertSame('555', $user->telegram_chat_id);
        $this->assertNull($user->telegram_link_token);
        $this->assertSame(2, Cache::get('telegram_offset'));
    }

    public function test_unknown_token_does_not_link_anyone(): void
    {
        $user = User::factory()->create(['telegram_link_token' => 'real-token']);

        $this->fakeGetUpdates([[
            'update_id' => 1,
            'message' => ['text' => '/start bogus-token', 'chat' => ['id' => 555]],
        ]]);

        $this->artisan('telegram:poll', ['--once' => true])->assertSuccessful();

        $user->refresh();
        $this->assertNull($user->telegram_chat_id);
        $this->assertSame('real-token', $user->telegram_link_token);
    }

    public function test_chat_id_already_linked_to_another_user_is_rejected(): void
    {
        User::factory()->create(['telegram_chat_id' => '555']);
        $newcomer = User::factory()->create(['telegram_link_token' => 'abc123']);

        $this->fakeGetUpdates([[
            'update_id' => 1,
            'message' => ['text' => '/start abc123', 'chat' => ['id' => 555]],
        ]]);

        $this->artisan('telegram:poll', ['--once' => true])->assertSuccessful();

        $newcomer->refresh();
        $this->assertNull($newcomer->telegram_chat_id);
        $this->assertSame('abc123', $newcomer->telegram_link_token);
    }

    public function test_non_start_message_gets_a_generic_reply_and_links_nobody(): void
    {
        User::factory()->create();

        $this->fakeGetUpdates([[
            'update_id' => 1,
            'message' => ['text' => 'halo bot', 'chat' => ['id' => 555]],
        ]]);

        $this->artisan('telegram:poll', ['--once' => true])->assertSuccessful();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage'));
    }
}
