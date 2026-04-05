<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateProductRequest;
use App\Models\Subcategory;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\ProductService;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService            $productService,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SearchService             $searchService,
    ) {
    }

    #[OA\Get(
        path: '/api/v1/{subcategoryId}/products',
        summary: 'Get paginated products for a subcategory',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'subcategoryId', in: 'path',  required: true,  schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page',          in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page',      in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 30)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Products list',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success',    type: 'boolean'),
                    new OA\Property(property: 'products',   type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductListItem')),
                    new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
                ])
            ),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function getProducts($subcategoryId)
    {
        $page    = request()->query('page', 1);
        $perPage = request()->query('per_page', 30);

        try {
            $products = $this->productRepository->getBySubcategory((int) $subcategoryId, ['images', 'region'], (int) $perPage, (int) $page);

            return response()->json([
                'success'    => true,
                'products'   => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching products by subcategory: ' . $e->getMessage());

            return response()->json(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/{subcategoryId}/attributes',
        summary: 'Get attributes with values for a subcategory (cached 5 hours)',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'subcategoryId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Attributes data',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'attributes',    type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'categoryId',    type: 'integer'),
                    new OA\Property(property: 'categoryName',  type: 'string'),
                    new OA\Property(property: 'categoryImage', type: 'string', nullable: true),
                ])
            ),
            new OA\Response(response: 404, description: 'Subcategory not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function getAttributes($subcategoryId)
    {
        try {
            $cacheKey = "attributes_{$subcategoryId}";

            return Cache::remember($cacheKey, 60 * 300, function () use ($subcategoryId) {
                $subcategory = Subcategory::find($subcategoryId);

                if (!$subcategory) {
                    return response()->json(['error' => 'No such subcategory found'], 404);
                }

                return response()->json([
                    'attributes'    => $subcategory->attributes()->with('attributeValues')->get(),
                    'categoryId'    => $subcategory->category->id,
                    'categoryName'  => $subcategory->category->name,
                    'categoryImage' => $subcategory->category->image_url,
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error occurred: ' . $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/category/{categoryId}/products',
        summary: 'Get paginated products for a top-level category',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'categoryId', in: 'path',  required: true,  schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page',       in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page',   in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 30)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Products list',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success',    type: 'boolean'),
                    new OA\Property(property: 'products',   type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductListItem')),
                    new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
                ])
            ),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function getProductsByCategory($categoryId)
    {
        Log::info('Received categoryId is ' . $categoryId);

        $page    = request()->query('page', 1);
        $perPage = request()->query('per_page', 30);

        try {
            $products = $this->productRepository->getByCategory((int) $categoryId, ['images', 'region'], (int) $perPage, (int) $page);

            return response()->json([
                'success'    => true,
                'products'   => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching products by category: ' . $e->getMessage());

            return response()->json(['success' => false, 'error' => 'An error occurred while fetching products'], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/filter-products',
        summary: 'Filter / search products with multiple criteria',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'query',            in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'subcategory_id',   in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'category_id',      in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'attribute_values', in: 'query', required: false, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'integer')), description: 'Attribute value IDs'),
            new OA\Parameter(name: 'parent_region_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'child_region_id',  in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'price_from',       in: 'query', required: false, schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'price_to',         in: 'query', required: false, schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'currency',         in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'usd')),
            new OA\Parameter(name: 'sorting_type',     in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['Дорогие', 'Дешевые', 'Новые'])),
            new OA\Parameter(name: 'page',             in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page',         in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 24)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Filtered products',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'products',   type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductListItem')),
                    new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
                ])
            ),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function filterProducts(Request $request)
    {
        try {
            $query   = trim($request->query('query', ''));
            $page    = (int) $request->query('page', 1);
            $perPage = (int) $request->query('per_page', 24);

            Log::info("FilterProducts: Search query received: '$query'");

            $matchingIds = !empty($query) ? $this->searchService->getMatchingProductIds($query) : null;

            $filters = ['matching_ids' => $matchingIds];

            if (!empty($matchingIds)) {
                $filters['id_whitelist'] = $matchingIds;
            } elseif ($matchingIds !== null) {
                $filters['force_empty'] = true;
            }

            $filters = array_merge($filters, [
                'subcategory_id'   => $request->input('subcategory_id'),
                'category_id'      => $request->input('category_id'),
                'attribute_values' => $request->input('attribute_values'),
                'parent_region_id' => $request->input('parent_region_id'),
                'child_region_id'  => $request->input('child_region_id'),
                'price_from'       => $request->input('price_from'),
                'price_to'         => $request->input('price_to'),
                'currency'         => $request->input('currency', 'usd'),
                'usd_rate'         => 12750,
                'sort'             => $request->input('sorting_type') === 'Дорогие' ? 'expensive'
                    : ($request->input('sorting_type') === 'Дешевые' ? 'cheap' : 'new'),
            ]);

            $products = $this->productRepository->getFilteredPaginated($filters, ['images', 'region.parent', 'user'], $perPage, $page);

            $transformed = collect($products->items())->map(function ($product) {
                $data             = $product->toArray();
                $data['image_url']  = $product->images->isNotEmpty() ? $product->images->first()->image_url : null;
                $data['isFromShop'] = optional($product->user)->isShop;
                $data['region']     = optional($product->parentRegion)->name ?? optional($product->region)->name;
                return $data;
            });

            return response()->json([
                'products'   => $transformed,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("FilterProducts error: " . $e->getMessage());

            return response()->json(['error' => 'Error occurred: ' . $e->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/product/create',
        summary: 'Create a new product listing',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/CreateProductRequest')
            )
        ),
        tags: ['Products'],
        responses: [
            new OA\Response(response: 201, description: 'Product created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status',  type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function createProduct(CreateProductRequest $request)
    {
        try {
            $this->productService->createApiProduct($request->validated(), $request);

            return response()->json(['status' => 'success', 'message' => 'Product is created'], 201);
        } catch (\Exception $e) {
            Log::error('Product creation failed: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Product was not created'], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/product/{productId}',
        summary: 'Get full product detail by ID',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'productId', in: 'path',  required: true,  schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'user_id',   in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Viewer ID — used to compute isFavorited'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product detail', content: new OA\JsonContent(ref: '#/components/schemas/Product')),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function getProduct(Request $request, $productId)
    {
        return response()->json(
            $this->productService->getApiProductDetail((int) $productId, $request->query('user_id') ? (int) $request->query('user_id') : null)
        );
    }

    #[OA\Get(
        path: '/api/v1/product/slug/{productSlug}',
        summary: 'Get full product detail by slug',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'productSlug', in: 'path',  required: true,  schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'user_id',     in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Viewer ID — used to compute isFavorited'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product detail', content: new OA\JsonContent(ref: '#/components/schemas/Product')),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function getProductBySlug(Request $request, $productSlug)
    {
        return response()->json(
            $this->productService->getApiProductDetailBySlug($productSlug, $request->query('user_id') ? (int) $request->query('user_id') : null)
        );
    }

    #[OA\Get(
        path: '/api/v1/fetch/product/{id}',
        summary: 'Get product data for editing (owner use)',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product edit data', content: new OA\JsonContent(ref: '#/components/schemas/Product')),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function fetchSingleProduct($id)
    {
        $data = $this->productService->getApiProductForEdit((int) $id);

        return response()->json($data);
    }

    #[OA\Post(
        path: '/api/v1/product/update/{id}',
        summary: 'Update a product listing (owner only)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/UpdateProductRequest')
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status',  type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function updateProduct(Request $request, $id)
    {
        Log::info('Incoming update request', ['fields' => $request->except(['images'])]);

        try {
            $validatedData = $request->validate([
                'name'            => 'required|string|max:255',
                'description'     => 'required|string|max:900',
                'subcategory_id'  => 'required|exists:subcategories,id',
                'images'          => 'nullable|array',
                'images.*'        => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                'latitude'        => 'required|numeric|between:-90,90',
                'longitude'       => 'required|numeric|between:-180,180',
                'attributes'      => 'nullable|array',
                'attributes.*'    => 'integer|exists:attribute_values,id',
                'price'           => 'required|numeric|min:0',
                'currency'        => 'required|string|in:сум,доллар',
                'type'            => 'required|string|in:sale,purchase',
                'child_region_id' => 'required|exists:regions,id',
            ]);

            $this->productService->updateApiProduct((int) $id, $request->user(), $validatedData, $request);

            return response()->json(['status' => 'success', 'message' => 'Product updated successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Product update failed: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Failed to update product: ' . $e->getMessage()], 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/products/delete/{productId}',
        summary: 'Delete a product listing (owner only)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'productId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status',  type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function removeProduct(Request $request, $productId)
    {
        try {
            $this->productService->removeApiProduct((int) $productId, $request->user());

            return response()->json(['status' => 'success', 'message' => 'Product deleted successfully', 'data' => null], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete product: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Failed to delete product', 'data' => null], 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/product/image/{id}',
        summary: 'Delete a product image (owner only)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Image deleted'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function deleteImage(Request $request, $id)
    {
        try {
            $this->productService->deleteApiImage((int) $id, $request->user());

            return response()->json(['success' => true, 'message' => 'Image deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting image: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete image: ' . $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/favorites',
        summary: 'Get IDs of the authenticated user\'s favourite products',
        security: [['sanctum' => []]],
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Favourite IDs',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status',    type: 'string', example: 'success'),
                    new OA\Property(property: 'favorites', type: 'array', items: new OA\Items(type: 'integer')),
                ])
            ),
        ]
    )]
    public function getFavorite(Request $request)
    {
        return response()->json([
            'status'    => 'success',
            'favorites' => $this->productService->getFavoriteIds($request->user()),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/favorite/toggle',
        summary: 'Toggle a product in/out of the authenticated user\'s favourites',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['product_id'],
                properties: [new OA\Property(property: 'product_id', type: 'integer', example: 42)]
            )
        ),
        tags: ['Products'],
        responses: [
            new OA\Response(response: 200, description: 'Toggled', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status',      type: 'string', example: 'success'),
                new OA\Property(property: 'isFavorited', type: 'boolean'),
            ])),
            new OA\Response(response: 400, description: 'Invalid product ID'),
        ]
    )]
    public function toggleFavorites(Request $request)
    {
        $productId = (int) $request->input('product_id');

        if (!$productId || !\App\Models\Product::find($productId)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid product ID'], 400);
        }

        $isFavorited = $this->productService->toggleFavorite($request->user(), $productId);

        return response()->json(['status' => 'success', 'isFavorited' => $isFavorited]);
    }

    #[OA\Get(
        path: '/api/v1/user/favorites/{uuid}',
        summary: 'Get all favourite products for a user (self only)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Favourite products', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status',   type: 'string', example: 'success'),
                new OA\Property(property: 'message',  type: 'string'),
                new OA\Property(property: 'products', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductListItem')),
            ])),
            new OA\Response(response: 400, description: 'Invalid user ID'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function getFavoritesOfUser(Request $request, $uuid)
    {
        try {
            $authenticatedUser = $request->user();

            if (!$authenticatedUser || !$authenticatedUser->exists || $authenticatedUser->id <= 0) {
                return response()->json(['status' => 'error', 'message' => 'Unauthenticated', 'data' => null], 401);
            }

            $uuid = (int) $uuid;

            if ($uuid <= 0) {
                return response()->json(['status' => 'error', 'message' => 'Invalid user ID', 'data' => null], 400);
            }

            if ($authenticatedUser->id !== $uuid) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized: You can only access your own favorites', 'data' => null], 403);
            }

            return response()->json([
                'status'   => 'success',
                'message'  => 'Products fetched successfully',
                'products' => $this->productService->getFavoritesOfUser($authenticatedUser),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch favorites: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Failed to fetch products', 'data' => null], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/user/{uuid}/products',
        summary: 'Get all products created by the authenticated user (self only)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User products', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status',   type: 'string', example: 'success'),
                new OA\Property(property: 'message',  type: 'string'),
                new OA\Property(property: 'products', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductListItem')),
            ])),
            new OA\Response(response: 400, description: 'Invalid user ID'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function getUserProducts(Request $request, $uuid)
    {
        try {
            $authenticatedUser = $request->user();

            if (!$authenticatedUser || !$authenticatedUser->exists || $authenticatedUser->id <= 0) {
                return response()->json(['status' => 'error', 'message' => 'Unauthenticated', 'data' => null], 401);
            }

            $uuid = (int) $uuid;

            if ($uuid <= 0) {
                return response()->json(['status' => 'error', 'message' => 'Invalid user ID', 'data' => null], 400);
            }

            if ($authenticatedUser->id !== $uuid) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized: You can only access your own products', 'data' => null], 403);
            }

            return response()->json([
                'status'   => 'success',
                'message'  => 'Products fetched successfully',
                'products' => $this->productService->getApiUserProducts($authenticatedUser),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch user products: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Failed to fetch products', 'data' => null], 500);
        }
    }
}
