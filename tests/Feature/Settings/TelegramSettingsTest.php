<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Telegram as TelegramComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TelegramSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.telegram.bot_username' => 'marlin_bot']);
    }

    public function test_telegram_settings_page_is_displayed(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('telegram.edit'))->assertOk();
    }

    public function test_not_linked_user_sees_hubungkan_button(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('telegram.edit'))
            ->assertSee('Hubungkan Telegram')
            ->assertDontSee('Terhubung');
    }

    public function test_not_linked_user_can_generate_a_link_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(TelegramComponent::class)->call('hubungkan');

        $user->refresh();
        $this->assertNotNull($user->telegram_link_token);
        $this->assertNull($user->telegram_chat_id);
    }

    public function test_link_url_uses_the_generated_token_and_configured_bot_username(): void
    {
        $user = User::factory()->create(['telegram_link_token' => 'abc123']);
        $this->actingAs($user);

        $this->get(route('telegram.edit'))
            ->assertSee('https://t.me/marlin_bot?start=abc123', false);
    }

    public function test_linked_user_sees_terhubung_status(): void
    {
        $this->actingAs(User::factory()->create(['telegram_chat_id' => '999']));

        $this->get(route('telegram.edit'))
            ->assertSee('Terhubung')
            ->assertDontSee('Hubungkan Telegram');
    }

    public function test_user_can_putuskan_koneksi(): void
    {
        $user = User::factory()->create(['telegram_chat_id' => '999', 'telegram_link_token' => null]);
        $this->actingAs($user);

        Livewire::test(TelegramComponent::class)->call('putuskan');

        $user->refresh();
        $this->assertNull($user->telegram_chat_id);
        $this->assertNull($user->telegram_link_token);
    }
}
