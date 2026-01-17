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

    public function store(Request $request, ResourceRegistry $registry, string $slug)
    {
        $resourceClass = $registry->resolve($slug);
        abort_unless($resourceClass, 404);

        $model = new ($resourceClass::$model)();
        $model->fill($request->all());

        // TODO: Add validation from resource rules()
        // TODO: Check authorization via policy

        $model->save();

        return response()->json($model, 201);
    }

    public function update(Request $request, ResourceRegistry $registry, string $slug, $id)
    {
        $resourceClass = $registry->resolve($slug);
        abort_unless($resourceClass, 404);

        $model = ($resourceClass::$model)::findOrFail($id);

        // TODO: Check authorization via policy

        $model->fill($request->all());
        $model->save();

        return response()->json($model);
    }

    public function destroy(ResourceRegistry $registry, string $slug, $id)
    {
        $resourceClass = $registry->resolve($slug);
        abort_unless($resourceClass, 404);

        $model = ($resourceClass::$model)::findOrFail($id);

        // TODO: Check authorization via policy

        $model->delete();

        return response()->json(['status' => 'deleted'], 200);
    }
}
