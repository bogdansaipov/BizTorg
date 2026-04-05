<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShopProfileRequest;
use App\Http\Requests\UpdateShopImagesRequest;
use App\Http\Requests\UpdateShopProfileRequest;
use App\Models\Product;
use App\Models\ShopProfile;
use App\Services\ShopService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class ShopProfileController extends Controller
{
    public function __construct(private readonly ShopService $shopService)
    {
    }

    #[OA\Post(
        path: '/api/v1/shop-profiles',
        summary: 'Create a shop profile for the authenticated user',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreShopRequest')
        ),
        tags: ['Shops'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Shop created',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data',    ref: '#/components/schemas/ShopProfile'),
                ])
            ),
            new OA\Response(response: 400, description: 'Shop already exists'),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function store(StoreShopProfileRequest $request)
    {
        try {
            $user = Auth::user();

            Log::info('Shop Profile creation attempt', [
                'user_id'        => $user->id,
                'validated_data' => $request->validated(),
            ]);

            $shop = $this->shopService->createShop($user, $request->validated());

            if ($shop === null) {
                return response()->json([
                    'message' => 'У вас уже есть созданный магазин',
                ], 400);
            }

            return response()->json([
                'message' => 'Shop profile created successfully',
                'data'    => $shop,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create ShopProfile', [
                'user_id'     => Auth::id(),
                'error'       => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error happened ' . $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/shop/update',
        summary: 'Update shop profile data for the authenticated user',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreShopRequest')
        ),
        tags: ['Shops'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Shop updated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data',    ref: '#/components/schemas/ShopProfile'),
                ])
            ),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function updateShopData(UpdateShopProfileRequest $request)
    {
        try {
            Log::info('Shop Profile update attempt', [
                'user_id'        => Auth::id(),
                'validated_data' => $request->validated(),
            ]);

            $shop = $this->shopService->updateShop(Auth::user(), $request->validated());

            return response()->json([
                'message' => 'Shop profile updated successfully',
                'data'    => $shop,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating ShopProfile: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'error'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to update shop profile',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/{shopId}/upload-profile-images',
        summary: 'Upload banner and/or profile images for a shop',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(property: 'banner_url',  type: 'string', format: 'binary', nullable: true),
                    new OA\Property(property: 'profile_url', type: 'string', format: 'binary', nullable: true),
                ])
            )
        ),
        tags: ['Shops'],
        parameters: [
            new OA\Parameter(name: 'shopId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Images updated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data',    ref: '#/components/schemas/ShopProfile'),
                ])
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Shop not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function updateImages(UpdateShopImagesRequest $request, $shopId)
    {
        try {
            $shop = ShopProfile::findOrFail($shopId);

            $this->authorize('manage', $shop);

            $updatedShop = $this->shopService->updateImages(
                $shop,
                $request->file('banner_url'),
                $request->file('profile_url')
            );

            Log::info('ShopProfile images updated', [
                'shop_id' => $shop->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Изображение успешно обновлено',
                'data'    => $updatedShop,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating shop images: ' . $e->getMessage());

            return response()->json([
                'message' => 'error occured',
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/{shopId}/getShop',
        summary: 'Get shop profile by ID (owner-only)',
        security: [['sanctum' => []]],
        tags: ['Shops'],
        parameters: [
            new OA\Parameter(name: 'shopId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Shop profile',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'shop_profile',        ref: '#/components/schemas/ShopProfile'),
                    new OA\Property(property: 'message',             type: 'string'),
                    new OA\Property(property: 'isAlreadySubscriber', type: 'boolean'),
                    new OA\Property(property: 'hasAlreadyRated',     type: 'boolean'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Shop not found'),
        ]
    )]
    public function getShopProfile(Request $request, $shopId)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized access. Please log in.',
                ], 401);
            }

            $shopProfile = ShopProfile::findOrFail($shopId);

            $this->authorize('manage', $shopProfile);

            $isAlreadySubscriber = false;
            $hasAlreadyRated     = false;

            if ($user->isShop) {
                $isAlreadySubscriber = $user->shopProfile->subscribers()->where('user_id', $user->id)->exists();
                $hasAlreadyRated     = $user->shopProfile->raters()->where('user_id', $user->id)->exists();
            }

            return response()->json([
                'shop_profile'        => $shopProfile,
                'message'             => 'success',
                'isAlreadySubscriber' => $isAlreadySubscriber,
                'hasAlreadyRated'     => $hasAlreadyRated,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching shop profile: ' . $e->getMessage());

            return response()->json([
                'message' => 'error',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    #[OA\Get(
        path: '/api/v1/shops/{userId}',
        summary: 'Get all products listed by a user/shop (public)',
        tags: ['Shops'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User products',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'products',       type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductListItem')),
                    new OA\Property(property: 'products_count', type: 'integer'),
                    new OA\Property(property: 'message',        type: 'string'),
                ])
            ),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function getUserProducts(Request $request, $userId)
    {
        try {
            $userProducts = Product::with('region')
                ->where('user_id', $userId)
                ->get()
                ->map(function ($product) {
                    return [
                        'id'         => $product->id,
                        'slug'       => $product->slug,
                        'name'       => $product->name,
                        'price'      => $product->price,
                        'currency'   => $product->currency,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                        'region'     => $product->parentRegion->name ?? $product->region->name ?? null,
                        'images'     => $product->images->map(fn ($img) => ['image_url' => $img->image_url])->toArray(),
                    ];
                });

            return response()->json([
                'products'       => $userProducts,
                'products_count' => $userProducts->count(),
                'message'        => 'success',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching user products for shop: ' . $e->getMessage());

            return response()->json(['message' => 'Failed to fetch user products'], 500);
        }
    }
}
