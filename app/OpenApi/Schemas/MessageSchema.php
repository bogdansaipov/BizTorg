<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'Message', type: 'object', properties: [
    new OA\Property(property: 'id',              type: 'integer', example: 1),
    new OA\Property(property: 'conversation_id', type: 'integer', example: 7),
    new OA\Property(property: 'sender_id',       type: 'integer', example: 42),
    new OA\Property(property: 'message',         type: 'string',  example: 'Hello!'),
    new OA\Property(property: 'image_url',       type: 'string',  nullable: true),
    new OA\Property(property: 'created_at',      type: 'string',  format: 'date-time'),
])]
class MessageSchema {}
