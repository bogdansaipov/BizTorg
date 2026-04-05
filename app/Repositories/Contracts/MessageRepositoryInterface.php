<?php

namespace App\Repositories\Contracts;

use App\Models\Message;
use Illuminate\Support\Collection;

interface MessageRepositoryInterface
{
    public function create(int $conversationId, int $senderId, ?string $text, ?string $imageUrl): Message;
    public function getByConversation(int $conversationId): Collection;
}
