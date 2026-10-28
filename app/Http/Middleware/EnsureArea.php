<?php

namespace App\Http\Middleware;

use App\Enums\Area;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** route middleware: area:solicitations — the signed-in role must work in that back-office area. */
class EnsureArea
{
    public function handle(Request $request, Closure $next, string $area): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->works(Area::from($area)), 403);

        return $next($request);
    }
}
