<?php

namespace App\Services;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\RegionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly ProductRepositoryInterface  $productRepository,
        private readonly RegionRepositoryInterface   $regionRepository,
        private readonly CurrencyService             $currencyService,
    ) {
    }

    /**
     * Build all data needed for the web category page.
     */
    public function getCategoryPage(string $slug, Request $request): array
    {
        $usdRate = $this->currencyService->getDollarRate() ?: 12900;

        $category = $this->categoryRepository->findBySlug($slug, [
            'subcategories.products.images',
            'subcategories.products.attributeValues.attributes',
        ]);

        if (!$category) {
            abort(404);
        }

        $mainRegions        = $this->regionRepository->getParents();
        $selectedSubcategory = null;
        $attributes          = collect();
        $attributeValues     = [];
        $selectedRegion      = null;
        $selectedCity        = null;
        $regionChildren      = collect();

        // Resolve subcategory
        $subcategoryIds = $category->subcategories->pluck('id')->toArray();
        if ($request->filled('subcategory')) {
            $selectedSubcategory = $category->subcategories->where('slug', $request->input('subcategory'))->first();
            if ($selectedSubcategory) {
                $subcategoryIds = [$selectedSubcategory->id];
                $attributes     = $selectedSubcategory->attributes()->with('attributeValues')->get();
            }
        }

        foreach ($attributes as $attribute) {
            $attributeValues[$attribute->id] = $attribute->attributeValues()->get();
        }

        // Resolve region / city
        if ($request->filled('region') && $request->input('region') !== 'whole') {
            $selectedRegion = $this->regionRepository->findBySlug($request->input('region'));
            if ($selectedRegion) {
                $regionChildren = $selectedRegion->children;
            }
        }

        if ($request->filled('city')) {
            $selectedCity = $this->regionRepository->findBySlug($request->input('city'));
        }

        // Build filters array for the repository
        $attributeSlugFilters = Arr::except($request->query(), [
            'subcategory', 'currency', 'page', 'city', 'region',
            'price_from', 'price_to', 'type', 'date_filter', 'with_images_only', 'search',
        ]);

        $sort = match ($request->input('date_filter')) {
            'expensive' => 'expensive',
            'cheap'     => 'cheap',
            default     => 'new',
        };

        $filters = [
            'subcategory_ids'        => $subcategoryIds,
            'attribute_slug_filters' => $attributeSlugFilters,
            'currency'               => $request->input('currency', 'usd'),
            'usd_rate'               => $usdRate,
            'price_from'             => $request->input('price_from') !== '' ? $request->input('price_from') : null,
            'price_to'               => $request->input('price_to') !== '' ? $request->input('price_to') : null,
            'region_ids'             => $selectedRegion
                ? array_merge([$selectedRegion->id], $selectedRegion->children()->pluck('id')->toArray())
                : [],
            'city_id'                => $selectedCity?->id,
            'type'                   => $request->input('type'),
            'with_images_only'       => $request->input('with_images_only') === 'yes',
            'search'                 => $request->input('search'),
            'sort'                   => $sort,
        ];

        $products = $this->productRepository->getFilteredPaginated(
            $filters, ['images', 'attributeValues.attributes'], 12, (int) $request->input('page', 1)
        );

        Log::info('CategoryService: Products retrieved', ['total' => $products->total()]);

        return compact(
            'category', 'usdRate', 'mainRegions', 'selectedRegion', 'selectedCity',
            'regionChildren', 'selectedSubcategory', 'attributes', 'attributeValues', 'products'
        );
    }
}
