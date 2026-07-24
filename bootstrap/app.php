<?php

use App\Enums\ErrorLevel;
use App\Http\Middleware\EnsureUserHasRole;
use App\Models\SystemErrorLog;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $e) {
            // system_error_log is for unexpected technical failures. Routine
            // framework flow (validation, auth redirects, 404s) is not an "error".
            if ($e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof AuthorizationException
                || $e instanceof ModelNotFoundException
                || $e instanceof NotFoundHttpException
                || ($e instanceof HttpException && $e->getStatusCode() < 500)) {
                return;
            }

            SystemErrorLog::create([
                'level' => $e instanceof \Error ? ErrorLevel::Critical : ErrorLevel::Error,
                'pesan' => $e->getMessage() ?: get_class($e),
                'detail' => (string) $e,
                'endpoint' => request()?->fullUrl(),
                'user_id' => Auth::id(),
            ]);
        });
    })->create();
