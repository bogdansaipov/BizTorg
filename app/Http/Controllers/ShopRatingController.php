<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\ShopProfileRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class ShopRatingController extends Controller
{
    public function __construct(private readonly ShopProfileRepositoryInterface $shopProfileRepository)
    {
    }

    #[OA\Post(
        path: '/api/v1/shop/rate',
        summary: 'Rate a shop (1–5 stars)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RateShopRequest')
        ),
        tags: ['Shops'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Rating added',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message',       type: 'string'),
                    new OA\Property(property: 'shop_profile',  ref: '#/components/schemas/ShopProfile'),
                    new OA\Property(property: 'rating_sum',    type: 'number'),
                    new OA\Property(property: 'rating',        type: 'number'),
                    new OA\Property(property: 'rating_count',  type: 'integer'),
                ])
            ),
            new OA\Response(response: 404, description: 'Shop not found'),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function rateShop(Request $request)
    {
        try {
            $shopProfile = $this->shopProfileRepository->findById((int) $request->input('shop_id'));

            if (!$shopProfile) {
                return response()->json(['error' => 'ShopProfile not found'], 404);
            }

            $this->shopProfileRepository->addRating($shopProfile, $request->user()->id, $request->input('rating'));

            $shopProfile->refresh();

            return response()->json([
                'message'      => 'Rating added successfully',
                'shop_profile' => $shopProfile,
                'rating_sum'   => $shopProfile->rating_sum,
                'rating'       => $shopProfile->rating,
                'rating_count' => $shopProfile->rating_count,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to add rating: " . $e->getMessage());

            return response()->json(['error' => 'Failed to add rating: ' . $e->getMessage()], 500);
        }
    }
}
