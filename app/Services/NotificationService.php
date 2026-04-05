<?php

namespace App\Services;

use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(private readonly NotificationRepositoryInterface $notificationRepository)
    {
    }

    public function getUnseenForUser(int $userId): Collection
    {
        $notifications = $this->notificationRepository->getUnseen($userId);

        return $notifications->map(function ($notification) use ($userId) {
            $metadata            = json_decode($notification->metadata, true) ?? [];
            $isShop              = false;
            $senderName          = $metadata['sender_name'] ?? 'Unknown';
            $shopProfile         = null;
            $userProfile         = null;
            $isAlreadySubscriber = false;
            $hasAlreadyRated     = false;

            if ($notification->sender) {
                $isShop     = $notification->sender->isShop;
                $senderName = $isShop
                    ? ($notification->sender->shopProfile->shop_name ?? $senderName)
                    : ($notification->sender->name ?? $senderName);
                $shopProfile = $notification->sender->shopProfile;
                $userProfile = $notification->sender->profile;

                if ($isShop && $shopProfile) {
                    $isAlreadySubscriber = $shopProfile->subscribers()->where('user_id', $userId)->exists();
                    $hasAlreadyRated     = $shopProfile->raters()->where('user_id', $userId)->exists();
                }
            }

            $metadata['sender_name'] = $senderName;

            return array_merge($notification->toArray(), [
                'metadata'            => json_encode($metadata),
                'isShop'              => $isShop,
                'sender_name'         => $senderName,
                'shopProfile'         => $shopProfile,
                'userProfile'         => $userProfile,
                'isAlreadySubscriber' => $isAlreadySubscriber,
                'hasAlreadyRated'     => $hasAlreadyRated,
            ]);
        });
    }

    public function markAllSeen(int $userId): void
    {
        $this->notificationRepository->markAllSeenForUser($userId);
    }

    public function markSeenForChat(int $userId, int $otherUserId): void
    {
        $this->notificationRepository->markSeenForChat($userId, $otherUserId);
    }
}
