<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class ConversationsController extends Controller
{
    public function __construct(private readonly ConversationService $conversationService)
    {
    }

    #[OA\Get(
        path: '/api/v1/user/get/chat/conversations',
        summary: 'Get all chat conversations for the authenticated user',
        security: [['sanctum' => []]],
        tags: ['Messaging'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Conversations list',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status',        type: 'string',  example: 'success'),
                    new OA\Property(property: 'message',       type: 'string'),
                    new OA\Property(property: 'conversations', type: 'array', items: new OA\Items(type: 'object')),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function getChats(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user || !($user instanceof \App\Models\User) || !$user->exists || $user->id <= 0) {
                return response()->json(['status' => 'error', 'message' => 'Unauthenticated', 'data' => null], 401);
            }

            $conversations = $this->conversationService->getChatsForUser($user);

            Log::info('Fetched conversations', ['count' => $conversations->count()]);

            return response()->json([
                'status'        => 'success',
                'message'       => 'Conversations fetched successfully',
                'conversations' => $conversations,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch conversations: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Failed to fetch conversations', 'data' => null], 500);
        }
    }
}
