<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService)
    {
    }

    #[OA\Post(
        path: '/api/v1/profile/update',
        summary: 'Update name, email, phone and region for a user',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateProfileRequest')
        ),
        tags: ['Profiles'],
        responses: [
            new OA\Response(response: 200, description: 'Updated',           content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])),
            new OA\Response(response: 400, description: 'Invalid ID'),
            new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function updateProfile(Request $request)
    {
        $id = $request->input('uuid');

        if (!is_numeric($id)) {
            return response()->json(['error' => 'Invalid ID'], 400);
        }

        $validatedData = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => "required|email|max:255|unique:users,email,{$id}",
            'phone'     => 'required|string|max:20',
            'region_id' => 'nullable|exists:regions,id',
        ]);

        $this->profileService->updateApiProfile((int) $id, $validatedData);

        return response()->json(['message' => 'Profile updated'], 200);
    }

    #[OA\Get(
        path: '/api/v1/profile/{id}',
        summary: 'Get full profile data for a user (cached 1 minute)',
        tags: ['Profiles'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'current_user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Viewer ID — used to compute isAlreadySubscriber / hasAlreadyRated'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User data',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'user',                ref: '#/components/schemas/User'),
                    new OA\Property(property: 'user_profile',        ref: '#/components/schemas/Profile', nullable: true),
                    new OA\Property(property: 'region',              ref: '#/components/schemas/Region',  nullable: true),
                    new OA\Property(property: 'isShop',              type: 'boolean'),
                    new OA\Property(property: 'shop_profile',        ref: '#/components/schemas/ShopProfile', nullable: true),
                    new OA\Property(property: 'isAlreadySubscriber', type: 'boolean'),
                    new OA\Property(property: 'hasAlreadyRated',     type: 'boolean'),
                ])
            ),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    public function getUserDataJson(Request $request, $id)
    {
        $currentUserId = $request->query('current_user_id');
        $cacheKey      = "user_data_{$id}_viewer_{$currentUserId}";

        $userData = Cache::remember($cacheKey, 1, fn () =>
            $this->profileService->getApiUserData((int) $id, $currentUserId ? (int) $currentUserId : null)
        );

        return response()->json($userData);
    }
}
