<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;

class MessageService
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepository,
        private readonly MessageRepositoryInterface      $messageRepository,
        private readonly NotificationRepositoryInterface $notificationRepository,
        private readonly UserRepositoryInterface         $userRepository,
    ) {
    }

    /**
     * Find an existing conversation between two users or create a new one.
     */
    public function findOrCreateConversation(int $senderId, int $receiverId): Conversation
    {
        return $this->conversationRepository->findOrCreate($senderId, $receiverId);
    }

    /**
     * Create a message and its associated notification.
     */
    public function sendMessage(int $senderId, int $receiverId, ?string $text, ?string $imageUrl): Message
    {
        $conversation = $this->conversationRepository->findOrCreate($senderId, $receiverId);

        $message = $this->messageRepository->create(
            $conversation->id,
            $senderId,
            $text,
            $imageUrl
        );

        $sender = $this->userRepository->findOrFail($senderId);

        $this->notificationRepository->createMessageNotification(
            receiverId:     $receiverId,
            senderId:       $senderId,
            senderName:     $sender->name,
            messageId:      $message->id,
            conversationId: $conversation->id,
            messagePreview: $text
        );

        return $message;
    }

    /**
     * Retrieve all messages in a conversation between two users, ordered descending.
     */
    public function getMessages(int $userId, int $otherUserId): array
    {
        $conversation = $this->conversationRepository->findBetweenUsers($userId, $otherUserId);

        if (!$conversation) {
            return ['found' => false, 'messages' => []];
        }

        return [
            'found'    => true,
            'messages' => $this->messageRepository->getByConversation($conversation->id),
        ];
    }
}
