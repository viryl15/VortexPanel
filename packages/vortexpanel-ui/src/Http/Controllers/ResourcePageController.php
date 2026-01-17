<?php

namespace VortexPanel\UI\Http\Controllers;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use VortexPanel\Core\Support\ResourceRegistry;

class ResourcePageController extends Controller
{
    public function __invoke(ResourceRegistry $registry, string $resource)
    {
        $resourceClass = $registry->resolve($resource);
        abort_unless($resourceClass, 404);

        return Inertia::render('VortexPanel/ResourceIndex', [
            'brand' => config('vortexpanel.ui.brand', 'VortexPanel'),
            'resource' => [
                'slug' => $resourceClass::$slug,
                'label' => $resourceClass::$label ?? $resourceClass::$slug,
                'columns' => $resourceClass::columns(),
            ],
            'resources' => $registry->meta(),
            'apiBase' => '/' . trim(config('vortexpanel.path', 'admin'), '/') . '/api',
        ]);
    }
}
