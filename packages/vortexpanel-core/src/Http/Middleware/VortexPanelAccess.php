<?php

namespace VortexPanel\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VortexPanelAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        // Policy-first: if host defines a global gate for "accessVortexPanel", use it.
        // Otherwise fallback to permission.
        if (method_exists($user, 'can') && $user->can('accessVortexPanel')) {
            return $next($request);
        }

        $perm = config('vortexpanel.access_permission', 'access admin');
        if (method_exists($user, 'can') && $user->can($perm)) {
            return $next($request);
        }

        abort(403);
    }
}
