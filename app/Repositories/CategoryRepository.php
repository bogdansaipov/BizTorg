<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(private Category $model)
    {
    }

    public function all(array $with = []): Collection
    {
        return $this->model->with($with)->get();
    }

    public function allWithSlugs(array $slugs, array $with = []): Collection
    {
        return $this->model->whereIn('slug', $slugs)->with($with)->get();
    }

    public function findBySlug(string $slug, array $with = []): ?Category
    {
        return $this->model->where('slug', $slug)->with($with)->first();
    }

    public function findById(int $id, array $with = []): ?Category
    {
        return $this->model->with($with)->find($id);
    }
}
