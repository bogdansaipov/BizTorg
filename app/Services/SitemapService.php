<?php

namespace App\Services;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Carbon\Carbon;

class SitemapService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly ProductRepositoryInterface  $productRepository,
    ) {
    }

    public function generate(): string
    {
        $sitemap  = '<?xml version="1.0" encoding="UTF-8"?>';
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $sitemap .= '
        <url>
            <loc>https://biztorg.uz/</loc>
            <lastmod>' . Carbon::now()->toDateString() . '</lastmod>
            <priority>1.0</priority>
        </url>';

        foreach ($this->categoryRepository->all() as $category) {
            $sitemap .= '
            <url>
                <loc>' . htmlspecialchars('https://biztorg.uz/category/' . $category->slug, ENT_QUOTES, 'UTF-8') . '</loc>
                <lastmod>' . $category->updated_at->toDateString() . '</lastmod>
                <priority>0.9</priority>
            </url>';
        }

        foreach ($this->productRepository->getPaginated([], 10000, 1)->items() as $product) {
            $sitemap .= '
            <url>
                <loc>' . htmlspecialchars('https://biztorg.uz/obyavlenie/' . $product->slug, ENT_QUOTES, 'UTF-8') . '</loc>
                <lastmod>' . $product->updated_at->toDateString() . '</lastmod>
                <priority>0.9</priority>
            </url>';
        }

        $sitemap .= '</urlset>';

        return trim($sitemap);
    }
}
