<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'Profile', type: 'object', properties: [
    new OA\Property(property: 'id',         type: 'integer', example: 1),
    new OA\Property(property: 'user_id',    type: 'integer', example: 42),
    new OA\Property(property: 'phone',      type: 'string',  nullable: true, example: '+998901234567'),
    new OA\Property(property: 'address',    type: 'string',  nullable: true, example: 'Yunusabad'),
    new OA\Property(property: 'region_id',  type: 'integer', nullable: true, example: 3),
    new OA\Property(property: 'avatar',     type: 'string',  nullable: true, example: 'avatars/photo.jpg'),
    new OA\Property(property: 'latitude',   type: 'number',  nullable: true, example: 41.2995),
    new OA\Property(property: 'longitude',  type: 'number',  nullable: true, example: 69.2401),
    new OA\Property(property: 'created_at', type: 'string',  format: 'date-time'),
    new OA\Property(property: 'updated_at', type: 'string',  format: 'date-time'),
])]
class ProfileSchema {}
