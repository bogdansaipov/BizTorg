<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'StoreShopRequest', required: ['shop_name', 'description', 'phone'], type: 'object', properties: [
    new OA\Property(property: 'shop_name',      type: 'string', example: 'Tech Store'),
    new OA\Property(property: 'description',    type: 'string', example: 'Best electronics'),
    new OA\Property(property: 'phone',          type: 'string', example: '+998901234567'),
    new OA\Property(property: 'tax_id_number',  type: 'string', nullable: true),
    new OA\Property(property: 'contact_name',   type: 'string', nullable: true),
    new OA\Property(property: 'address',        type: 'string', nullable: true),
    new OA\Property(property: 'facebook_link',  type: 'string', nullable: true),
    new OA\Property(property: 'telegram_link',  type: 'string', nullable: true),
    new OA\Property(property: 'instagram_link', type: 'string', nullable: true),
    new OA\Property(property: 'website',        type: 'string', nullable: true),
    new OA\Property(property: 'latitude',       type: 'number', nullable: true),
    new OA\Property(property: 'longitude',      type: 'number', nullable: true),
])]
class StoreShopRequestSchema {}
