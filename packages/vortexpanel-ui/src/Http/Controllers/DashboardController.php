<?php

namespace VortexPanel\UI\Http\Controllers;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use VortexPanel\Core\Support\ResourceRegistry;

class DashboardController extends Controller
{
    public function __invoke(ResourceRegistry $registry)
    {
        return Inertia::render('VortexPanel/Dashboard', [
            'brand' => config('vortexpanel.ui.brand', 'VortexPanel'),
            'resources' => $registry->meta(),
            'apiBase' => '/' . trim(config('vortexpanel.path', 'admin'), '/') . '/api',
        ]);
    }
}
