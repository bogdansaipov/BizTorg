<?php

namespace App\Http\Controllers;

use App\Models\ShopProfile;
use App\Services\ShopService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class ShopSubscriptionController extends Controller
{
    public function __construct(private readonly ShopService $shopService)
    {
    }

    #[OA\Post(
        path: '/api/v1/subscribe/{shopId}',
        summary: 'Subscribe to a shop',
        security: [['sanctum' => []]],
        tags: ['Shops'],
        parameters: [
            new OA\Parameter(name: 'shopId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Subscribed successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message',     type: 'string'),
                new OA\Property(property: 'subscribers', type: 'array', items: new OA\Items(type: 'object')),
            ])),
            new OA\Response(response: 400, description: 'Already subscribed'),
            new OA\Response(response: 404, description: 'Shop not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function subscribe(Request $request, $shopId)
    {
        try {
            Log::info('Subscribe request received', [
                'shop_id' => $shopId,
                'user_id' => $request->user()->id,
            ]);

            $user = $request->user();
            $shop = ShopProfile::findOrFail($shopId);

            $subscribed = $this->shopService->subscribe($user, $shop);

            if (!$subscribed) {
                Log::warning('User already subscribed to shop', [
                    'user_id' => $user->id,
                    'shop_id' => $shop->id,
                ]);

                return response()->json(['message' => 'Already subscribed'], 400);
            }

            return response()->json([
                'message'     => 'Subscribed successfully',
                'subscribers' => $shop->fresh()->subscribers,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in subscribe method', [
                'shop_id' => $shopId,
                'user_id' => $request->user()->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to subscribe'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/unsubscribe/{shopId}',
        summary: 'Unsubscribe from a shop',
        security: [['sanctum' => []]],
        tags: ['Shops'],
        parameters: [
            new OA\Parameter(name: 'shopId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Unsubscribed successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message',     type: 'string'),
                new OA\Property(property: 'subscribers', type: 'array', items: new OA\Items(type: 'object')),
            ])),
            new OA\Response(response: 400, description: 'Not subscribed'),
            new OA\Response(response: 404, description: 'Shop not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function unsubscribe(Request $request, $shopId)
    {
        try {
            Log::info('Unsubscribe request received', [
                'shop_id' => $shopId,
                'user_id' => $request->user()->id,
            ]);

            $user = $request->user();
            $shop = ShopProfile::findOrFail($shopId);

            $unsubscribed = $this->shopService->unsubscribe($user, $shop);

            if (!$unsubscribed) {
                Log::warning('User not subscribed to shop', [
                    'user_id' => $user->id,
                    'shop_id' => $shop->id,
                ]);

                return response()->json(['message' => 'Not subscribed'], 400);
            }

            return response()->json([
                'message'     => 'Unsubscribed successfully',
                'subscribers' => $shop->fresh()->subscribers,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in unsubscribe method', [
                'shop_id' => $shopId,
                'user_id' => $request->user()->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to unsubscribe'], 500);
        }
    }
}
