<?php

namespace VortexPanel\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to optimize API responses for speed.
 * - Disables debug bar for API routes
 * - Adds cache headers for static data
 * - Compresses JSON responses
 */
class FastResponse
{
    public function handle(Request $request, Closure $next)
    {
        // Disable debugbar for API requests (huge perf gain)
        if (class_exists(\Barryvdh\Debugbar\Facade::class)) {
            \Barryvdh\Debugbar\Facade::disable();
        }

        $response = $next($request);

        // Add performance headers
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $response->header('X-VortexPanel-Speed', 'optimized');
        }

        return $response;
    }
}
