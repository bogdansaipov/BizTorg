<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'ProductListItem', type: 'object', properties: [
    new OA\Property(property: 'id',         type: 'integer', example: 1),
    new OA\Property(property: 'name',       type: 'string',  example: 'iPhone 14 Pro'),
    new OA\Property(property: 'slug',       type: 'string',  example: 'iphone-14-pro'),
    new OA\Property(property: 'price',      type: 'number',  example: 1200),
    new OA\Property(property: 'currency',   type: 'string',  enum: ['сум', 'доллар'], example: 'доллар'),
    new OA\Property(property: 'region',     type: 'string',  nullable: true, example: 'Ташкент, Юнусабадский'),
    new OA\Property(property: 'created_at', type: 'string',  format: 'date-time'),
    new OA\Property(property: 'isFromShop', type: 'boolean', example: false),
    new OA\Property(property: 'images',     type: 'array',   items: new OA\Items(ref: '#/components/schemas/ProductImage')),
])]
class ProductListItemSchema {}
