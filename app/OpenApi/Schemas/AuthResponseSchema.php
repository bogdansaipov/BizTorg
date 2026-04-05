<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'AuthResponse', type: 'object', properties: [
    new OA\Property(property: 'status',  type: 'string',  example: 'success'),
    new OA\Property(property: 'message', type: 'string',  example: 'Successfully logged in'),
    new OA\Property(property: 'uuid',    type: 'integer', example: 42),
    new OA\Property(property: 'token',   type: 'string',  example: '1|abc123...'),
])]
class AuthResponseSchema {}
