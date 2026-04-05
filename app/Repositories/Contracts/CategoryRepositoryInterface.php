<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Support\Collection;

interface CategoryRepositoryInterface
{
    public function all(array $with = []): Collection;
    public function allWithSlugs(array $slugs, array $with = []): Collection;
    public function findBySlug(string $slug, array $with = []): ?Category;
    public function findById(int $id, array $with = []): ?Category;
}
