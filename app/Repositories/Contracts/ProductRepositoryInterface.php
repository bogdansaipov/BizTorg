<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface
{
    // ── Search ────────────────────────────────────────────────────────────────
    public function searchByFullText(string $tsQuery, array $with): Collection;
    public function searchByTrigram(array $words, array $with): Collection;
    public function similarity(string $a, string $b): float;

    // ── CRUD ──────────────────────────────────────────────────────────────────
    public function create(array $data): Product;
    public function update(Product $product, array $data): void;
    public function findById(int $id, array $with = []): Product;
    public function findBySlug(string $slug, array $with = []): ?Product;
    public function findOwnedById(int $productId, int $userId): ?Product;
    public function delete(Product $product): void;

    // ── Listings ──────────────────────────────────────────────────────────────
    public function getPaginated(array $with, int $perPage, int $page): LengthAwarePaginator;
    public function getBySubcategory(int $subcategoryId, array $with, int $perPage, int $page): LengthAwarePaginator;
    public function getByCategory(int $categoryId, array $with, int $perPage, int $page): LengthAwarePaginator;
    public function getByUser(int $userId, array $with, int $excludeId, int $limit): Collection;
    public function getSameProducts(int $subcategoryId, int $excludeId, array $excludeIds, array $with, int $limit): Collection;
    public function getFilteredPaginated(array $filters, array $with, int $perPage, int $page): LengthAwarePaginator;

    // ── Attributes ────────────────────────────────────────────────────────────
    public function syncAttributes(Product $product, array $attributeIds): void;

    // ── Images ────────────────────────────────────────────────────────────────
    public function createImage(int $productId, string $path): ProductImage;
    public function getImages(int $productId): Collection;
    public function findImageById(int $id): ProductImage;
    public function findImageForUser(int $imageId, int $userId): ProductImage;
    public function deleteImage(ProductImage $image): void;
}
