<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'Notification', type: 'object', properties: [
    new OA\Property(property: 'id',          type: 'integer', example: 1),
    new OA\Property(property: 'receiver_id', type: 'integer', example: 42),
    new OA\Property(property: 'sender_id',   type: 'integer', example: 10),
    new OA\Property(property: 'type',        type: 'string',  example: 'message'),
    new OA\Property(property: 'content',     type: 'string',  example: 'У вас новое сообщение от: John'),
    new OA\Property(property: 'hasBeenSeen', type: 'boolean', example: false),
    new OA\Property(property: 'is_global',   type: 'boolean', example: false),
    new OA\Property(property: 'priority',    type: 'string',  example: 'medium'),
    new OA\Property(property: 'metadata',    type: 'string',  nullable: true),
    new OA\Property(property: 'created_at',  type: 'string',  format: 'date-time'),
])]
class NotificationSchema {}
