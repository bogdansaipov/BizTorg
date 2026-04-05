<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'SendMessageRequest', required: ['receiver_id'], type: 'object', properties: [
    new OA\Property(property: 'receiver_id', type: 'integer', example: 10),
    new OA\Property(property: 'message',     type: 'string',  nullable: true, example: 'Is it still available?'),
    new OA\Property(property: 'image_url',   type: 'string',  nullable: true, example: 'chat_images/photo.jpg'),
])]
class SendMessageRequestSchema {}
