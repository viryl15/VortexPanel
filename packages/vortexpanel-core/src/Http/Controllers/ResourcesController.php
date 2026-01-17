<?php

namespace VortexPanel\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VortexPanel\Core\Support\ResourceRegistry;
use VortexPanel\Core\Support\ResourceTable;

class ResourcesController extends Controller
{
    public function index(ResourceRegistry $registry)
    {
        return response()->json([
            'resources' => $registry->meta(),
        ]);
    }

    public function data(Request $request, ResourceRegistry $registry, string $slug)
    {
        $resourceClass = $registry->resolve($slug);
        abort_unless($resourceClass, 404);

        $paginator = ResourceTable::paginate($resourceClass, $request->all());

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
