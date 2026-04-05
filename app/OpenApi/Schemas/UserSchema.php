<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'User', type: 'object', properties: [
    new OA\Property(property: 'id',                type: 'integer', example: 42),
    new OA\Property(property: 'name',              type: 'string',  example: 'John Doe'),
    new OA\Property(property: 'email',             type: 'string',  format: 'email'),
    new OA\Property(property: 'avatar',            type: 'string',  nullable: true),
    new OA\Property(property: 'role_id',           type: 'integer', example: 0),
    new OA\Property(property: 'isShop',            type: 'boolean', example: false),
    new OA\Property(property: 'email_verified_at', type: 'string',  format: 'date-time', nullable: true),
    new OA\Property(property: 'created_at',        type: 'string',  format: 'date-time'),
    new OA\Property(property: 'updated_at',        type: 'string',  format: 'date-time'),
    new OA\Property(property: 'profile',           ref: '#/components/schemas/Profile', nullable: true),
])]
class UserSchema {}
