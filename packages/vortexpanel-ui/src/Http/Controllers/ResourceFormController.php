<?php

namespace VortexPanel\UI\Http\Controllers;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use VortexPanel\Core\Support\ResourceRegistry;

class ResourceFormController extends Controller
{
    public function create(ResourceRegistry $registry, string $resource)
    {
        $resourceClass = $registry->resolve($resource);
        abort_unless($resourceClass, 404);

        return Inertia::render('VortexPanel/ResourceCreate', [
            'brand' => config('vortexpanel.ui.brand', 'VortexPanel'),
            'resource' => [
                'slug' => $resourceClass::$slug,
                'label' => $resourceClass::$label ?? $resourceClass::$slug,
                'form' => $resourceClass::form(),
            ],
            'resources' => $registry->meta(),
            'apiBase' => '/' . trim(config('vortexpanel.path', 'admin'), '/') . '/api',
        ]);
    }

    public function edit(ResourceRegistry $registry, string $resource, $id)
    {
        $resourceClass = $registry->resolve($resource);
        abort_unless($resourceClass, 404);

        $model = ($resourceClass::$model)::findOrFail($id);

        return Inertia::render('VortexPanel/ResourceEdit', [
            'brand' => config('vortexpanel.ui.brand', 'VortexPanel'),
            'resource' => [
                'slug' => $resourceClass::$slug,
                'label' => $resourceClass::$label ?? $resourceClass::$slug,
                'form' => $resourceClass::form(),
            ],
            'item' => $model->toArray(),
            'resources' => $registry->meta(),
            'apiBase' => '/' . trim(config('vortexpanel.path', 'admin'), '/') . '/api',
        ]);
    }
}
