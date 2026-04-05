<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'FcmTokenRequest', required: ['user_id', 'fcm_token'], type: 'object', properties: [
    new OA\Property(property: 'user_id',   type: 'integer', example: 42),
    new OA\Property(property: 'fcm_token', type: 'string',  example: 'eqR7iXpuTfu...'),
])]
class FcmTokenRequestSchema {}
