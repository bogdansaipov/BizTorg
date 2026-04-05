<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Collection;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(private Notification $model)
    {
    }

    public function createMessageNotification(
        int $receiverId,
        int $senderId,
        string $senderName,
        int $messageId,
        int $conversationId,
        ?string $messagePreview
    ): Notification {
        return $this->model->create([
            'receiver_id'  => $receiverId,
            'sender_id'    => $senderId,
            'type'         => 'message',
            'content'      => "У вас новое сообщение от: {$senderName}",
            'hasBeenSeen'  => false,
            'is_global'    => false,
            'reference_id' => $messageId,
            'priority'     => 'medium',
            'metadata'     => json_encode([
                'conversation_id' => $conversationId,
                'message_preview' => $messagePreview,
                'sender_name'     => $senderName,
            ]),
        ]);
    }

    public function getUnseen(int $userId): Collection
    {
        return $this->model
            ->where(fn ($q) => $q->where('receiver_id', $userId)->orWhere('is_global', true))
            ->where('hasBeenSeen', false)
            ->orderBy('date', 'desc')
            ->with(['sender' => fn ($q) => $q->select('id', 'isShop', 'name')->with(['profile', 'shopProfile'])])
            ->get();
    }

    public function markAllSeenForUser(int $userId): void
    {
        $this->model->where('receiver_id', $userId)->update(['hasBeenSeen' => true]);
    }

    public function markSeenForChat(int $userId, int $senderId): void
    {
        $this->model
            ->where('receiver_id', $userId)
            ->where('sender_id', $senderId)
            ->where('type', 'message')
            ->where('hasBeenSeen', false)
            ->update(['hasBeenSeen' => true]);
    }
}
