<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== Role::from($role)) {
            return redirect()->to($user?->isAdmin() ? route('admin.dashboard') : route('dashboard'));
        }

        return $next($request);
    }
}
