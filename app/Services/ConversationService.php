<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ConversationService
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepository,
        private readonly UserRepositoryInterface         $userRepository,
    ) {
    }

    public function getChatsForUser(User $user): Collection
    {
        $conversations = $this->conversationRepository->getChatsForUser($user->id);

        return $conversations->map(function ($conversation) use ($user) {
            $otherUser   = $conversation->user_one_id == $user->id ? $conversation->userTwo : $conversation->userOne;
            $phoneNumber = optional($otherUser->profile)->phone ?? 'Не указан';
            $avatar      = $otherUser->avatar ?? '';
            $lastMessage = $conversation->messages->first();

            $lastMessageContent = $lastMessage ? $lastMessage->message : 'Нет сообщений';
            $lastMessageDate    = '';

            if ($lastMessage) {
                $date    = new DateTime($lastMessage->created_at);
                $dayMap  = ['Mon' => 'Пн', 'Tue' => 'Вт', 'Wed' => 'Ср', 'Thu' => 'Чт', 'Fri' => 'Пт', 'Sat' => 'Сб', 'Sun' => 'Вс'];
                $lastMessageDate = ($dayMap[$date->format('D')] ?? $date->format('D')) . ' ' . $date->format('d.m.y');
            }

            $otherUserFull = $this->userRepository->findOrFail($otherUser->id);
            $shopProfile   = null;
            $isShop        = $otherUserFull->isShop;
            $userProfile   = $otherUserFull->profile;

            $isAlreadySubscriber = false;
            $hasAlreadyRated     = false;

            if ($otherUserFull->isShop) {
                $shopProfile         = $otherUserFull->shopProfile;
                $isAlreadySubscriber = $otherUserFull->shopProfile->subscribers()->where('user_id', $user->id)->exists();
                $hasAlreadyRated     = $otherUserFull->shopProfile->raters()->where('user_id', $user->id)->exists();
            }

            return [
                'id'                  => $conversation->id,
                'user_one_id'         => $conversation->user_one_id,
                'user_two_id'         => $conversation->user_two_id,
                'created_at'          => $conversation->created_at,
                'updated_at'          => $conversation->updated_at,
                'user_one'            => $conversation->userOne,
                'user_two'            => $conversation->userTwo,
                'phone_number'        => $phoneNumber,
                'last_message'        => $lastMessageContent,
                'last_message_date'   => $lastMessageDate,
                'avatar'              => $avatar,
                'isShop'              => $isShop,
                'shopProfile'         => $shopProfile,
                'userProfile'         => $userProfile,
                'isAlreadySubscriber' => $isAlreadySubscriber,
                'hasAlreadyRated'     => $hasAlreadyRated,
            ];
        });
    }
}
