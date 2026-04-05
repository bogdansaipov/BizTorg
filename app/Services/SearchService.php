<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SearchService
{
    public function __construct(private readonly ProductRepositoryInterface $productRepository)
    {
    }

    /**
     * Build a full-text + trigram product search query and return matching products as a Collection.
     * Results are deduplicated and sorted by combined ts_rank + trigram similarity score.
     *
     * @param  string        $query  Raw search input from the user
     * @param  array<string> $with   Eager-load relations on the returned products
     * @return Collection<Product>
     */
    public function searchProducts(string $query, array $with = ['images', 'region.parent']): Collection
    {
        $query = trim($query);

        if (empty($query)) {
            return collect();
        }

        $words   = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
        $tsQuery = $this->buildTsQuery($words);

        Log::info("SearchService: Full-text query: '$tsQuery'");

        $fullTextProducts = $this->productRepository->searchByFullText($tsQuery, $with);
        Log::info("SearchService: Full-text results count: " . $fullTextProducts->count());

        $trigramProducts = $this->productRepository->searchByTrigram($words, $with);
        Log::info("SearchService: Trigram results count: " . $trigramProducts->count());

        return $fullTextProducts
            ->merge($trigramProducts)
            ->unique('id')
            ->sortByDesc(function (Product $product) use ($query) {
                $tsRank   = $product->ts_rank ?? 0;
                $simScore = max(
                    $this->productRepository->similarity($product->name, $query),
                    $this->productRepository->similarity($product->description ?? '', $query),
                    $this->productRepository->similarity($product->slug ?? '', $query)
                );
                return $tsRank + $simScore;
            })
            ->values();
    }

    /**
     * Returns the IDs of matching products so the caller can use whereIn().
     *
     * @param  string     $query
     * @return array<int>|null  null means "no search applied", empty array means "no results"
     */
    public function getMatchingProductIds(string $query): ?array
    {
        $query = trim($query);

        if (empty($query)) {
            return null;
        }

        return $this->searchProducts($query, [])->pluck('id')->all();
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function buildTsQuery(array $words): string
    {
        $tsQueryTerms = [];

        foreach ($words as $word) {
            $word      = str_replace(['&', '|', '!', ':', '*'], '', $word);
            $truncated = [];
            $minLength = 3;
            for ($len = mb_strlen($word); $len >= $minLength; $len--) {
                $truncated[] = mb_substr($word, 0, $len) . ':*';
            }
            if (!empty($truncated)) {
                $tsQueryTerms[] = '(' . implode(' | ', $truncated) . ')';
            }
        }

        if (empty($tsQueryTerms)) {
            return implode(' | ', array_map(fn ($w) => $w . ':*', $words));
        }

        return implode(' | ', $tsQueryTerms);
    }
}
