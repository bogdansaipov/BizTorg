<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class NotificationsController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    #[OA\Get(
        path: '/api/v1/notifications',
        summary: 'Get all unseen notifications for the authenticated user',
        security: [['sanctum' => []]],
        tags: ['Notifications'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notifications list',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status',        type: 'string', example: 'success'),
                    new OA\Property(property: 'notifications', type: 'array', items: new OA\Items(ref: '#/components/schemas/Notification')),
                ])
            ),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function index(Request $request)
    {
        try {
            $notifications = $this->notificationService->getUnseenForUser($request->user()->id);

            Log::info("Fetched notifications for user {$request->user()->id}", ['count' => $notifications->count()]);

            return response()->json(['status' => 'success', 'notifications' => $notifications], 200);
        } catch (\Exception $e) {
            Log::error("Error fetching notifications: {$e->getMessage()}");

            return response()->json(['status' => 'error', 'message' => 'Failed to fetch notifications: ' . $e->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/notifications/mark-all-seen',
        summary: 'Mark all notifications as seen for the authenticated user',
        security: [['sanctum' => []]],
        tags: ['Notifications'],
        responses: [
            new OA\Response(response: 200, description: 'Marked as seen', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status',  type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function markAsSeen(Request $request)
    {
        try {
            $this->notificationService->markAllSeen($request->user()->id);

            return response()->json(['status' => 'success', 'message' => 'All notifications marked as seen'], 200);
        } catch (\Exception $e) {
            Log::error("Error marking notifications as seen: {$e->getMessage()}");

            return response()->json(['status' => 'error', 'message' => 'Failed to mark notifications as seen: ' . $e->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/notifications/mark-seen-for-chat',
        summary: 'Mark chat-related notifications as seen for a specific conversation partner',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['other_user_id'],
                properties: [
                    new OA\Property(property: 'other_user_id', type: 'integer', example: 10),
                ]
            )
        ),
        tags: ['Notifications'],
        responses: [
            new OA\Response(response: 200, description: 'Marked as seen', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status',  type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 400, description: 'other_user_id missing'),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function markSeenForChat(Request $request)
    {
        $otherUserId = $request->input('other_user_id');

        if (!$otherUserId) {
            return response()->json(['status' => 'error', 'message' => 'Other user ID is required'], 400);
        }

        try {
            $this->notificationService->markSeenForChat($request->user()->id, (int) $otherUserId);

            return response()->json(['status' => 'success', 'message' => 'Notifications marked as seen for this chat'], 200);
        } catch (\Exception $e) {
            Log::error("Error marking chat notifications as seen: {$e->getMessage()}");

            return response()->json(['status' => 'error', 'message' => 'Failed to mark chat notifications as seen: ' . $e->getMessage()], 500);
        }
    }
}
