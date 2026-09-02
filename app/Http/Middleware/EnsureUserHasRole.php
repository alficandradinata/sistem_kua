<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * [SISTEM KUA] Batasi akses route berdasarkan peran user.
 * Pakai: ->middleware('role:admin') atau 'role:petugas,admin'. Lihat PROGRESS.md.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user()?->hasRole($roles), 403, 'Anda tidak punya akses ke halaman ini.');

        return $next($request);
    }
}
