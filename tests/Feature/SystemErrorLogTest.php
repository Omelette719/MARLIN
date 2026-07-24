<?php

namespace Tests\Feature;

use App\Models\SystemErrorLog;
use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class SystemErrorLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_unexpected_exceptions_are_persisted_to_system_error_log(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        app(ExceptionHandler::class)->report(new RuntimeException('Kesalahan tak terduga'));

        $this->assertDatabaseHas('system_error_log', [
            'pesan' => 'Kesalahan tak terduga',
            'level' => 'error',
            'user_id' => $admin->id,
        ]);
    }

    public function test_routine_framework_exceptions_are_not_logged(): void
    {
        app(ExceptionHandler::class)->report(ValidationException::withMessages(['field' => 'required']));
        app(ExceptionHandler::class)->report(new NotFoundHttpException());

        $this->assertSame(0, SystemErrorLog::count());
    }

    public function test_petugas_cannot_access_system_error_log_viewer(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.system-error-log.index'))->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_view_system_error_log(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        SystemErrorLog::create([
            'level' => 'critical',
            'pesan' => 'Database connection lost',
            'user_id' => $admin->id,
        ]);

        $response = $this->get(route('admin.system-error-log.index'));
        $response->assertOk();
        $response->assertSee('Database connection lost');
    }
}
