<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendMessageRequest;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class MessagesController extends Controller
{
    public function __construct(private readonly MessageService $messageService)
    {
    }

    #[OA\Post(
        path: '/api/v1/send/message',
        summary: 'Send a message (text and/or image) to another user',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SendMessageRequest')
        ),
        tags: ['Messaging'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Message sent',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status',  type: 'string', example: 'success'),
                    new OA\Property(property: 'message', ref: '#/components/schemas/Message'),
                ])
            ),
            new OA\Response(response: 422, description: 'No content / validation error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function sendMessage(SendMessageRequest $request)
    {
        try {
            if (!$request->hasContent()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Message or image is required',
                ], 422);
            }

            $message = $this->messageService->sendMessage(
                senderId:   $request->user()->id,
                receiverId: (int) $request->validated('receiver_id'),
                text:       $request->validated('message'),
                imageUrl:   $request->validated('image_url')
            );

            Log::info("Message sent: ID {$message->id}");

            return response()->json([
                'status'  => 'success',
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error("Error sending message: {$e->getMessage()}", [
                'request'   => $request->all(),
                'exception' => $e,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 422);
        }
    }

    #[OA\Get(
        path: '/api/v1/getMessages/{receiver_id}',
        summary: 'Get all messages in the conversation with a user',
        security: [['sanctum' => []]],
        tags: ['Messaging'],
        parameters: [
            new OA\Parameter(name: 'receiver_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Messages list',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success',  type: 'boolean'),
                    new OA\Property(property: 'message',  type: 'string'),
                    new OA\Property(property: 'messages', type: 'array', items: new OA\Items(ref: '#/components/schemas/Message')),
                ])
            ),
        ]
    )]
    public function getMessages(Request $request, $receiver_id)
    {
        $senderId = $request->user()->id;

        Log::info("Fetching messages: sender {$senderId}, receiver {$receiver_id}");

        $result = $this->messageService->getMessages($senderId, (int) $receiver_id);

        if (!$result['found']) {
            return response()->json([
                'success'  => false,
                'message'  => 'No conversation found.',
                'messages' => [],
            ]);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Messages fetched successfully.',
            'messages' => $result['messages'],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/upload/chat-image',
        summary: 'Upload an image for use in chat (returns storage path)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['image'],
                    properties: [
                        new OA\Property(property: 'image', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        tags: ['Messaging'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Image uploaded',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success',   type: 'boolean'),
                    new OA\Property(property: 'image_url', type: 'string', example: 'chat_images/photo.jpg'),
                ])
            ),
            new OA\Response(response: 400, description: 'No image provided'),
        ]
    )]
    public function uploadChatImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('chat_images', 'public');

            return response()->json([
                'success'   => true,
                'image_url' => $path,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'No image uploaded',
        ], 400);
    }
}
