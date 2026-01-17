<?php

namespace VortexPanel\Core\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ResourceTable
{
    /** @var array<string, array> In-memory cache for resource metadata */
    private static array $metaCache = [];

    /**
     * Build a paginated dataset for a Resource.
     * Optimized for speed: selects only needed columns, uses simple pagination when possible.
     */
    public static function paginate(string $resourceClass, array $params): LengthAwarePaginator
    {
        $meta = self::getResourceMeta($resourceClass);

        /** @var Builder $query */
        $query = ($meta['model'])::query();

        // SELECT only columns we need (huge perf gain on wide tables)
        $selectColumns = array_merge(['id'], $meta['columnKeys']);
        $query->select(array_unique($selectColumns));

        // Search: prefix-only for index usage
        $q = trim((string)($params['q'] ?? ''));
        if ($q !== '' && !empty($meta['searchable'])) {
            $query->where(function ($sub) use ($meta, $q) {
                foreach ($meta['searchable'] as $col) {
                    $sub->orWhere($col, 'like', $q . '%');
                }
            });
        }

        // Sort
        $sort = (string)($params['sort'] ?? 'id');
        $dir = strtolower((string)($params['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        if (in_array($sort, $meta['sortable'], true)) {
            $query->orderBy($sort, $dir);
        } else {
            $query->orderBy('id', 'desc');
        }

        $perPage = (int)($params['perPage'] ?? 25);
        $perPage = max(1, min(100, $perPage)); // Cap at 100 for speed

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get cached resource metadata (columns, searchable fields, etc.)
     */
    private static function getResourceMeta(string $resourceClass): array
    {
        if (isset(self::$metaCache[$resourceClass])) {
            return self::$metaCache[$resourceClass];
        }

        $columns = (array) $resourceClass::columns();
        $columnKeys = [];
        $sortable = [];

        foreach ($columns as $c) {
            if (!empty($c['key'])) {
                $columnKeys[] = $c['key'];
                if (!empty($c['sortable'])) {
                    $sortable[] = $c['key'];
                }
            }
        }

        self::$metaCache[$resourceClass] = [
            'model' => $resourceClass::$model,
            'columns' => $columns,
            'columnKeys' => $columnKeys,
            'sortable' => $sortable,
            'searchable' => (array) $resourceClass::searchable(),
        ];

        return self::$metaCache[$resourceClass];
    }

    /**
     * Clear the metadata cache (useful in tests)
     */
    public static function clearCache(): void
    {
        self::$metaCache = [];
    }
}
