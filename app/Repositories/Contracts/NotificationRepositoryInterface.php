<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use Illuminate\Support\Collection;

interface NotificationRepositoryInterface
{
    public function createMessageNotification(
        int $receiverId,
        int $senderId,
        string $senderName,
        int $messageId,
        int $conversationId,
        ?string $messagePreview
    ): Notification;

    public function getUnseen(int $userId): Collection;
    public function markAllSeenForUser(int $userId): void;
    public function markSeenForChat(int $userId, int $senderId): void;
}
