<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Membatasi route yang hanya boleh diakses role admin.
     * Kasir yang mencoba masuk ditolak dengan 403.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Halaman ini hanya dapat diakses oleh Admin.');
        }

        return $next($request);
    }
}
