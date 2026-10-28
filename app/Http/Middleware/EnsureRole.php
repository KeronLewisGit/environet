<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** route middleware: role:admin */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->active && in_array($user->role, array_map(fn (string $r) => Role::from($r), $roles), true), 403);

        return $next($request);
    }
}
