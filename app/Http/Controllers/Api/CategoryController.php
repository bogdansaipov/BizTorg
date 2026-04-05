<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Services\SearchService;
use Cache;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    public function __construct(private readonly SearchService $searchService)
    {
    }

    #[OA\Get(
        path: '/api/v1/home',
        summary: 'Get home page: all categories + paginated products (filterable)',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'categories', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Comma-separated category IDs to filter by'),
            new OA\Parameter(name: 'ad_type',    in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['all', 'new']), description: '"new" limits to last 10 days'),
            new OA\Parameter(name: 'page',       in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Home page data',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'categories', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
                    new OA\Property(property: 'products',   type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductListItem')),
                    new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
                ])
            ),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function homePage(Request $request)
    {
        try {
            Log::info('homePage called', [
                'categories' => $request->query('categories'),
                'ad_type'    => $request->query('ad_type'),
                'page'       => $request->query('page', 1),
            ]);

            $selectedCategoryIds = $request->query('categories', []);
            if (is_string($selectedCategoryIds)) {
                $selectedCategoryIds = array_filter(
                    explode(',', $selectedCategoryIds),
                    fn ($id) => is_numeric($id) && $id !== ''
                );
            } elseif (is_array($selectedCategoryIds)) {
                $selectedCategoryIds = array_filter($selectedCategoryIds, fn ($id) => is_numeric($id) && $id !== '');
            } else {
                $selectedCategoryIds = [];
            }

            $adType  = $request->query('ad_type', 'all');
            $page    = $request->input('page', 1);
            $perPage = 24;

            $categories = Category::get();
            Log::info('Categories fetched', ['count' => $categories->count()]);

            $productQuery = Product::query()
                ->with(['images', 'region' => fn ($q) => $q->with('parent'), 'user'])
                ->orderBy('created_at', 'desc');

            if ($adType === 'new') {
                $productQuery->where('created_at', '>=', Carbon::now()->subDays(10));
            }

            if (!empty($selectedCategoryIds)) {
                $productQuery->whereHas('subcategory', fn ($q) => $q->whereIn('category_id', $selectedCategoryIds));
            }

            $products = $productQuery->paginate($perPage, ['*'], 'page', $page);

            $transformedProducts = $products->map(function ($product) {
                return [
                    'id'         => $product->id,
                    'name'       => $product->name,
                    'price'      => $product->price,
                    'slug'       => $product->slug,
                    'currency'   => $product->currency,
                    'created_at' => $product->created_at->toISOString(),
                    'region'     => $product->region
                        ? ($product->region->parent
                            ? $product->region->parent->name . ', ' . $product->region->name
                            : $product->region->name)
                        : null,
                    'images'     => $product->images->map(fn ($img) => ['image_url' => $img->image_url])->toArray(),
                    'isFromShop' => $product->user ? $product->user->isShop : false,
                ];
            });

            Log::info('Products processed', ['count' => $transformedProducts->count()]);

            return response()->json([
                'categories' => $categories,
                'products'   => $transformedProducts->values(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                    'has_more'     => $products->hasMorePages(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('homePage error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Error fetching data: ' . $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/categories',
        summary: 'Get all categories',
        tags: ['Categories'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of categories',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'categories', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
                ])
            ),
        ]
    )]
    public function fetchCategories()
    {
        return response()->json(['categories' => Category::get()], 200);
    }

    #[OA\Get(
        path: '/api/v1/{categoryId}/subcategories',
        summary: 'Get subcategories for a category (cached 3 hours)',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'categoryId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of subcategories',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'subcategories', type: 'array', items: new OA\Items(ref: '#/components/schemas/Subcategory')),
                ])
            ),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function fetchSubcategories($categoryId)
    {
        try {
            $cacheKey      = 'all_subcategories' . $categoryId;
            $cacheDuration = 60 * 180;

            $subcategories = Cache::remember($cacheKey, $cacheDuration, function () use ($categoryId) {
                return Subcategory::where('category_id', $categoryId)->get();
            });

            return response()->json(['subcategories' => $subcategories], 200);
        } catch (Exception $e) {
            Log::error("Error fetching subcategories: " . $e->getMessage());

            return response()->json(['error' => 'Error fetching subcategories'], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/search-recommendations',
        summary: 'Get all subcategory names as search suggestions',
        tags: ['Categories'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search recommendations',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Subcategory')),
                ])
            ),
        ]
    )]
    public function searchRecommendations(Request $request)
    {
        $foundRecommendations = Subcategory::select('id', 'name')->get();

        return response()->json(['data' => $foundRecommendations], 200);
    }

    #[OA\Get(
        path: '/api/v1/search',
        summary: 'Full-text + trigram product search',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'query',    in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page',     in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 30)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success',    type: 'boolean'),
                    new OA\Property(property: 'products',   type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductListItem')),
                    new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
                ])
            ),
            new OA\Response(response: 500, description: 'Search error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function searchProducts(Request $request)
    {
        $query   = trim($request->query('query', ''));
        $page    = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 30);

        Log::info("Search query received: '$query'");

        if (empty($query)) {
            Log::info("Empty query, returning empty products");

            return response()->json([
                'success'    => true,
                'products'   => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page'    => 1,
                    'per_page'     => $perPage,
                    'total'        => 0,
                ],
            ], 200);
        }

        try {
            // Delegate to SearchService — deduplication & ranking are handled there
            $combinedProducts = $this->searchService->searchProducts($query, ['images', 'region.parent']);

            Log::info("Search combined results count: " . $combinedProducts->count());

            $paginated = new LengthAwarePaginator(
                $combinedProducts->forPage($page, $perPage),
                $combinedProducts->count(),
                $perPage,
                $page,
                ['path' => $request->url()]
            );

            $productsWithParentRegion = $paginated->getCollection()->map(function ($product) {
                Log::info("Product ID: " . $product->id . ", Parent Region: " . json_encode($product->parentRegion));

                return [
                    'id'       => $product->id,
                    'name'     => $product->name,
                    'price'    => $product->price,
                    'currency' => $product->currency,
                    'region'   => $product->parentRegion->name ?? $product->region->name ?? null,
                    'images'   => $product->images->map(fn ($img) => ['image_url' => $img->image_url])->toArray(),
                ];
            })->values();

            Log::info("Mapped products: " . json_encode($productsWithParentRegion));

            return response()->json([
                'success'    => true,
                'products'   => $productsWithParentRegion,
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error("Search error: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => 'An error occurred while searching products: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/find-category/subcategory/{id}',
        summary: 'Get category info by subcategory ID',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category info',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'category', type: 'object', properties: [
                        new OA\Property(property: 'id',            type: 'integer'),
                        new OA\Property(property: 'name',          type: 'string'),
                        new OA\Property(property: 'category_id',   type: 'integer'),
                        new OA\Property(property: 'category_name', type: 'string'),
                    ]),
                ])
            ),
            new OA\Response(response: 404, description: 'Subcategory or parent category not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function getCategory($id)
    {
        try {
            $subcategory = Subcategory::with('category')->find($id);

            if (!$subcategory) {
                return response()->json(['error' => 'Subcategory not found'], 404);
            }

            if (!$subcategory->category) {
                return response()->json(['error' => 'Parent category not found for this subcategory'], 404);
            }

            return response()->json([
                'category' => [
                    'id'            => $subcategory->id,
                    'name'          => $subcategory->name,
                    'category_id'   => $subcategory->category->id,
                    'category_name' => $subcategory->category->name,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while fetching the category: ' . $e->getMessage(),
            ], 500);
        }
    }
}
