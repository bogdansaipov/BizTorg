<?php

namespace App\Repositories;

use App\Models\Region;
use App\Repositories\Contracts\RegionRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RegionRepository implements RegionRepositoryInterface
{
    public function __construct(private Region $model)
    {
    }

    public function getParents(): Collection
    {
        return Cache::remember('parent_regions', 60 * 180, fn () =>
            $this->model->whereNull('parent_id')->get()
        );
    }

    public function getChildren(int $parentId): Collection
    {
        return Cache::remember("child_regions_{$parentId}", 60 * 180, fn () =>
            $this->model->where('parent_id', $parentId)->get()
        );
    }

    public function findBySlug(string $slug): ?Region
    {
        return $this->model->where('slug', $slug)->first();
    }
}
