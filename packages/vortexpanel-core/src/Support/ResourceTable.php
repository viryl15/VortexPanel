<?php

namespace VortexPanel\Core\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ResourceTable
{
    /**
     * Build a paginated dataset for a Resource.
     *
     * Resource must define:
     * - static string $model
     * - static function searchable(): array
     * - static function columns(): array (with sortable keys)
     */
    public static function paginate(string $resourceClass, array $params): LengthAwarePaginator
    {
        /** @var class-string $model */
        $model = $resourceClass::$model;

        /** @var Builder $query */
        $query = $model::query();

        $q = trim((string)($params['q'] ?? ''));
        if ($q !== '') {
            $searchable = (array) $resourceClass::searchable();
            $query->where(function ($sub) use ($searchable, $q) {
                foreach ($searchable as $col) {
                    // Prefer prefix search for performance (term%)
                    $sub->orWhere($col, 'like', $q . '%');
                }
            });
        }

        $columns = (array) $resourceClass::columns();
        $allowedSorts = [];
        foreach ($columns as $c) {
            if (!empty($c['sortable']) && !empty($c['key'])) {
                $allowedSorts[] = $c['key'];
            }
        }

        $sort = (string)($params['sort'] ?? 'id');
        $dir = strtolower((string)($params['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        if (in_array($sort, $allowedSorts, true)) {
            $query->orderBy($sort, $dir);
        }

        $perPage = (int)($params['perPage'] ?? 25);
        $perPage = max(1, min(200, $perPage));

        return $query->paginate($perPage)->withQueryString();
    }
}
