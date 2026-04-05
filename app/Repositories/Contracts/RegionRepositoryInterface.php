<?php

namespace App\Repositories\Contracts;

use App\Models\Region;
use Illuminate\Support\Collection;

interface RegionRepositoryInterface
{
    public function getParents(): Collection;
    public function getChildren(int $parentId): Collection;
    public function findBySlug(string $slug): ?Region;
}
