<?php

namespace App\Repositories;

use App\Models\Conversation;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use Illuminate\Support\Collection;

class ConversationRepository implements ConversationRepositoryInterface
{
    public function __construct(private Conversation $model)
    {
    }

    public function findBetweenUsers(int $userOneId, int $userTwoId): ?Conversation
    {
        return $this->model->where(function ($q) use ($userOneId, $userTwoId) {
            $q->where('user_one_id', $userOneId)->where('user_two_id', $userTwoId);
        })->orWhere(function ($q) use ($userOneId, $userTwoId) {
            $q->where('user_one_id', $userTwoId)->where('user_two_id', $userOneId);
        })->first();
    }

    public function create(int $userOneId, int $userTwoId): Conversation
    {
        return $this->model->create([
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);
    }

    public function findOrCreate(int $senderId, int $receiverId): Conversation
    {
        return $this->findBetweenUsers($senderId, $receiverId)
            ?? $this->create($senderId, $receiverId);
    }

    public function getChatsForUser(int $userId): Collection
    {
        return $this->model
            ->where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->with([
                'userOne'         => fn ($q) => $q->select('id', 'name', 'avatar'),
                'userOne.profile' => fn ($q) => $q->select('id', 'user_id', 'phone'),
                'userTwo'         => fn ($q) => $q->select('id', 'name', 'avatar'),
                'userTwo.profile' => fn ($q) => $q->select('id', 'user_id', 'phone'),
                'messages'        => fn ($q) => $q->latest()->limit(1),
            ])
            ->get();
    }
}
