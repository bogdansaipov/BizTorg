<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'ShopProfile', type: 'object', properties: [
    new OA\Property(property: 'id',             type: 'integer', example: 1),
    new OA\Property(property: 'user_id',        type: 'integer', example: 42),
    new OA\Property(property: 'shop_name',      type: 'string',  example: 'Tech Store'),
    new OA\Property(property: 'description',    type: 'string',  example: 'Best electronics in Tashkent'),
    new OA\Property(property: 'phone',          type: 'string',  example: '+998901234567'),
    new OA\Property(property: 'contact_name',   type: 'string',  nullable: true),
    new OA\Property(property: 'address',        type: 'string',  nullable: true),
    new OA\Property(property: 'facebook_link',  type: 'string',  nullable: true),
    new OA\Property(property: 'telegram_link',  type: 'string',  nullable: true),
    new OA\Property(property: 'instagram_link', type: 'string',  nullable: true),
    new OA\Property(property: 'website',        type: 'string',  nullable: true),
    new OA\Property(property: 'rating',         type: 'number',  example: 4.5),
    new OA\Property(property: 'subscribers',    type: 'integer', example: 120),
    new OA\Property(property: 'total_reviews',  type: 'integer', example: 35),
    new OA\Property(property: 'views',          type: 'integer', example: 500),
    new OA\Property(property: 'verified',       type: 'boolean', example: false),
    new OA\Property(property: 'profile_url',    type: 'string',  nullable: true),
    new OA\Property(property: 'banner_url',     type: 'string',  nullable: true),
    new OA\Property(property: 'latitude',       type: 'number',  nullable: true),
    new OA\Property(property: 'longitude',      type: 'number',  nullable: true),
])]
class ShopProfileSchema {}
