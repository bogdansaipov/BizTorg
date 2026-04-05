<?php

namespace App\Services;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;

class IndexService
{
    private const DISPLAYED_SLUGS = ['transport', 'nedvizhimost', 'elektronika', 'biznes-i-uslugi', 'dom-i-sad'];
    private const USD_RATE        = 12750;

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly ProductRepositoryInterface  $productRepository,
    ) {
    }

    public function getHomePageData(): array
    {
        $categories          = $this->categoryRepository->all(['subcategories']);
        $displayedCategories = $this->categoryRepository->allWithSlugs(self::DISPLAYED_SLUGS, ['subcategories']);
        $products            = $this->productRepository->getPaginated(['images', 'region', 'user'], 24, 1);

        return [
            'categories'          => $categories,
            'displayedCategories' => $displayedCategories,
            'products'            => $products,
            'usdRate'             => self::USD_RATE,
        ];
    }

    public function getPaginatedProducts(int $page, int $perPage): array
    {
        $products = $this->productRepository->getPaginated(['images', 'region', 'user'], $perPage, $page);

        return [
            'products'     => $products->items(),
            'current_page' => $products->currentPage(),
            'last_page'    => $products->lastPage(),
            'total'        => $products->total(),
        ];
    }
}
