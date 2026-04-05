<?php

namespace App\Repositories;

use App\Models\Message;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Support\Collection;

class MessageRepository implements MessageRepositoryInterface
{
    public function __construct(private Message $model)
    {
    }

    public function create(int $conversationId, int $senderId, ?string $text, ?string $imageUrl): Message
    {
        return $this->model->create([
            'conversation_id' => $conversationId,
            'sender_id'       => $senderId,
            'message'         => $text ?? '',
            'image_url'       => $imageUrl,
        ]);
    }

    public function getByConversation(int $conversationId): Collection
    {
        return $this->model->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
