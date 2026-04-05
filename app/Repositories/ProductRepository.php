<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Subcategory;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(private Product $model)
    {
    }

    // ── Search ────────────────────────────────────────────────────────────────

    public function searchByFullText(string $tsQuery, array $with): Collection
    {
        return $this->model->query()
            ->whereRaw(
                "(name_tsvector @@ to_tsquery('simple', ?) OR description_tsvector @@ to_tsquery('simple', ?) OR slug_tsvector @@ to_tsquery('simple', ?))",
                [$tsQuery, $tsQuery, $tsQuery]
            )
            ->orderByRaw(
                "(ts_rank(name_tsvector, to_tsquery('simple', ?)) + ts_rank(description_tsvector, to_tsquery('simple', ?)) + ts_rank(slug_tsvector, to_tsquery('simple', ?))) DESC",
                [$tsQuery, $tsQuery, $tsQuery]
            )
            ->with($with)
            ->get();
    }

    public function searchByTrigram(array $words, array $with): Collection
    {
        $similarityThreshold = 0.05;
        $conditions          = [];
        $conditionBindings   = [];
        $simExpressions      = [];
        $simBindings         = [];
        $orderBindings       = [];

        foreach ($words as $word) {
            $conditions[]      = "(name % ? OR description % ? OR slug % ?)";
            $conditionBindings = array_merge($conditionBindings, [$word, $word, $word]);
            $simExpressions[]  = "GREATEST(similarity(name, ?), similarity(description, ?), similarity(slug, ?))";
            $simBindings       = array_merge($simBindings, [$word, $word, $word]);
            $orderBindings     = array_merge($orderBindings, [$word, $word, $word]);
        }

        $conditionString = implode(' OR ', $conditions);
        $maxSimExpr      = 'GREATEST(' . implode(', ', $simExpressions) . ')';

        return $this->model->query()
            ->whereRaw("($conditionString)", $conditionBindings)
            ->whereRaw("$maxSimExpr >= ?", array_merge($simBindings, [$similarityThreshold]))
            ->orderByRaw("$maxSimExpr DESC", $orderBindings)
            ->with($with)
            ->get();
    }

    public function similarity(string $a, string $b): float
    {
        $a = strtolower(trim($a));
        $b = strtolower(trim($b));

        if (empty($a) || empty($b)) {
            return 0.0;
        }

        return (float) (DB::selectOne('SELECT similarity(?, ?) as sim', [$a, $b])->sim ?? 0);
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────

    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    public function update(Product $product, array $data): void
    {
        $product->update($data);
    }

    public function findById(int $id, array $with = []): Product
    {
        return $this->model->with($with)->findOrFail($id);
    }

    public function findBySlug(string $slug, array $with = []): ?Product
    {
        return $this->model->with($with)->where('slug', $slug)->first();
    }

    public function findOwnedById(int $productId, int $userId): ?Product
    {
        return $this->model->where('id', $productId)->where('user_id', $userId)->first();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    // ── Listings ──────────────────────────────────────────────────────────────

    public function getPaginated(array $with, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->model->with($with)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getBySubcategory(int $subcategoryId, array $with, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->model->where('subcategory_id', $subcategoryId)
            ->with($with)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getByCategory(int $categoryId, array $with, int $perPage, int $page): LengthAwarePaginator
    {
        $subcategoryIds = Subcategory::where('category_id', $categoryId)->pluck('id');

        return $this->model->whereIn('subcategory_id', $subcategoryIds)
            ->with($with)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getByUser(int $userId, array $with, int $excludeId, int $limit): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('id', '!=', $excludeId)
            ->with($with)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getSameProducts(int $subcategoryId, int $excludeId, array $excludeIds, array $with, int $limit): Collection
    {
        return $this->model->where('subcategory_id', $subcategoryId)
            ->where('id', '!=', $excludeId)
            ->whereNotIn('id', $excludeIds)
            ->with($with)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getFilteredPaginated(array $filters, array $with, int $perPage, int $page): LengthAwarePaginator
    {
        $query = $this->model->query()->with($with);

        // Subcategory / category scoping
        if (!empty($filters['subcategory_id'])) {
            $query->where('subcategory_id', $filters['subcategory_id']);
        } elseif (!empty($filters['subcategory_ids'])) {
            $query->whereIn('subcategory_id', $filters['subcategory_ids']);
        } elseif (!empty($filters['category_id'])) {
            $subcategoryIds = Subcategory::where('category_id', $filters['category_id'])->pluck('id');
            $query->whereIn('subcategory_id', $subcategoryIds);
        }

        // Attribute values
        if (!empty($filters['attribute_values'])) {
            $values = is_string($filters['attribute_values'])
                ? explode(',', $filters['attribute_values'])
                : $filters['attribute_values'];
            $query->whereHas('attributeValues', fn ($q) => $q->whereIn('attribute_values.id', $values));
        }

        // Attribute slug filters (web category page: ?{slug}={valueId})
        if (!empty($filters['attribute_slug_filters'])) {
            foreach ($filters['attribute_slug_filters'] as $slug => $valueId) {
                if (is_numeric($valueId)) {
                    $query->whereHas('attributeValues', function ($q) use ($slug, $valueId) {
                        $q->whereHas('attributes', fn ($s) => $s->where('attributes.slug', $slug))
                          ->where('attribute_values.id', $valueId);
                    });
                }
            }
        }

        // Region / city
        if (!empty($filters['parent_region_id'])) {
            $query->whereHas('region', function ($q) use ($filters) {
                $q->where('regions.parent_id', $filters['parent_region_id']);
                if (!empty($filters['child_region_id'])) {
                    $q->where('regions.id', $filters['child_region_id']);
                }
            });
        }

        if (!empty($filters['region_ids'])) {
            $query->whereIn('region_id', $filters['region_ids']);
        }

        if (!empty($filters['city_id'])) {
            $query->where('region_id', $filters['city_id']);
        }

        // Price
        if (!empty($filters['price_from']) || !empty($filters['price_to'])) {
            $currency  = $filters['currency'] ?? 'usd';
            $usdRate   = $filters['usd_rate'] ?? 12900;
            $priceFrom = (float) ($filters['price_from'] ?? 0);
            $priceTo   = (float) ($filters['price_to'] ?? PHP_INT_MAX);

            $query->where(function ($q) use ($priceFrom, $priceTo, $currency, $usdRate) {
                if ($currency === 'usd') {
                    $q->where(fn ($u) => $u->where('currency', 'доллар')->whereBetween('price', [$priceFrom, $priceTo]))
                      ->orWhere(fn ($u) => $u->where('currency', 'сум')->whereBetween('price', [$priceFrom * $usdRate, $priceTo * $usdRate]));
                } elseif ($currency === 'uzs') {
                    $q->where(fn ($u) => $u->where('currency', 'сум')->whereBetween('price', [$priceFrom, $priceTo]))
                      ->orWhere(fn ($u) => $u->where('currency', 'доллар')->whereBetween('price', [$priceFrom / $usdRate, $priceTo / $usdRate]));
                }
            });
        }

        // Type
        if (!empty($filters['type']) && in_array($filters['type'], ['sale', 'purchase'])) {
            $query->where('type', $filters['type']);
        }

        // With images only
        if (!empty($filters['with_images_only'])) {
            $query->whereHas('images');
        }

        // Text search (simple LIKE for web)
        if (!empty($filters['search'])) {
            $words = explode(' ', strtolower(trim($filters['search'])));
            $query->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->where('name', 'LIKE', "%$word%")
                      ->orWhere('description', 'LIKE', "%$word%");
                }
            });
        }

        // Sorting
        $sort = $filters['sort'] ?? null;
        match ($sort) {
            'expensive'  => $query->orderBy('price', 'desc'),
            'cheap'      => $query->orderBy('price', 'asc'),
            default      => $query->orderBy('created_at', 'desc'),
        };

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    // ── Attributes ────────────────────────────────────────────────────────────

    public function syncAttributes(Product $product, array $attributeIds): void
    {
        $product->attributeValues()->sync($attributeIds);
    }

    // ── Images ────────────────────────────────────────────────────────────────

    public function createImage(int $productId, string $path): ProductImage
    {
        return ProductImage::create(['product_id' => $productId, 'image_url' => $path]);
    }

    public function getImages(int $productId): Collection
    {
        return ProductImage::where('product_id', $productId)->get();
    }

    public function findImageById(int $id): ProductImage
    {
        return ProductImage::findOrFail($id);
    }

    public function findImageForUser(int $imageId, int $userId): ProductImage
    {
        return ProductImage::where('id', $imageId)
            ->whereHas('product', fn ($q) => $q->where('user_id', $userId))
            ->firstOrFail();
    }

    public function deleteImage(ProductImage $image): void
    {
        $image->delete();
    }
}
