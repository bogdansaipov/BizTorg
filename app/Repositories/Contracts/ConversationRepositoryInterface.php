<?php

namespace App\Repositories\Contracts;

use App\Models\Conversation;
use Illuminate\Support\Collection;

interface ConversationRepositoryInterface
{
    public function findBetweenUsers(int $userOneId, int $userTwoId): ?Conversation;
    public function create(int $userOneId, int $userTwoId): Conversation;
    public function findOrCreate(int $senderId, int $receiverId): Conversation;
    public function getChatsForUser(int $userId): Collection;
}
