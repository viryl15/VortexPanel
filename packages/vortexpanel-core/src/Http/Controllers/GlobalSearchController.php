<?php

namespace VortexPanel\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VortexPanel\Core\Support\ResourceRegistry;

class GlobalSearchController
{
    public function __invoke(Request $request, ResourceRegistry $registry): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $results = [];

        foreach ($registry->all() as $resourceClass) {
            $model = $resourceClass::$model ?? null;
            if (!is_string($model) || $model === '' || !class_exists($model)) {
                continue;
            }

            $cols = (array) $resourceClass::searchable();
            if (empty($cols)) {
                continue;
            }

            $query = $model::query();
            $query->where(function ($sub) use ($cols, $q) {
                foreach ($cols as $col) {
                    $sub->orWhere($col, 'like', $q . '%');
                }
            });

            $rows = $query->limit(5)->get();
            foreach ($rows as $row) {
                $title = $row->name ?? $row->title ?? $row->email ?? (string) $row->getKey();
                $results[] = [
                    'resource' => $resourceClass::$slug,
                    'resource_label' => $resourceClass::$label ?? $resourceClass::$slug,
                    'id' => $row->getKey(),
                    'title' => (string) $title,
                ];
            }
        }

        return response()->json(['data' => $results]);
    }
}
