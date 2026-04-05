<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'UpdateProfileRequest', required: ['name', 'email', 'phone'], type: 'object', properties: [
    new OA\Property(property: 'name',      type: 'string',  example: 'John Doe'),
    new OA\Property(property: 'email',     type: 'string',  format: 'email', example: 'john@example.com'),
    new OA\Property(property: 'phone',     type: 'string',  example: '+998901234567'),
    new OA\Property(property: 'region_id', type: 'integer', nullable: true, example: 3),
    new OA\Property(property: 'uuid',      type: 'integer', description: 'User ID (required for API update)', example: 42),
])]
class UpdateProfileRequestSchema {}
