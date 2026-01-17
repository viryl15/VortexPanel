<?php

namespace VortexPanel\Core\Support;

use Illuminate\Support\Arr;

class ResourceRegistry
{
    /** @var array<string,class-string> */
    private array $bySlug = [];

    /** @var array<int,class-string> */
    private array $resources = [];

    /**
     * @param class-string $resourceClass
     */
    public function register(string $resourceClass): void
    {
        if (!class_exists($resourceClass)) {
            return;
        }

        $slug = $resourceClass::$slug ?? null;
        if (!is_string($slug) || $slug === '') {
            return;
        }

        $this->bySlug[$slug] = $resourceClass;
        $this->resources = array_values(array_unique([...$this->resources, $resourceClass]));
    }

    /** @return array<int,class-string> */
    public function all(): array
    {
        return $this->resources;
    }

    /** @return class-string|null */
    public function resolve(string $slug): ?string
    {
        return $this->bySlug[$slug] ?? null;
    }

    public function meta(): array
    {
        return array_map(function (string $cls) {
            return [
                'slug' => $cls::$slug,
                'label' => $cls::$label ?? $cls::$slug,
                'model' => $cls::$model ?? null,
            ];
        }, $this->resources);
    }
}
