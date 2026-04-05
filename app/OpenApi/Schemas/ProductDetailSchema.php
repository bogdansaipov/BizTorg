<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'Product', type: 'object', properties: [
    new OA\Property(property: 'id',             type: 'integer', example: 1),
    new OA\Property(property: 'name',           type: 'string',  example: 'iPhone 14 Pro'),
    new OA\Property(property: 'slug',           type: 'string',  example: 'iphone-14-pro'),
    new OA\Property(property: 'description',    type: 'string',  example: 'Brand new, sealed box'),
    new OA\Property(property: 'price',          type: 'number',  example: 1200),
    new OA\Property(property: 'currency',       type: 'string',  enum: ['сум', 'доллар']),
    new OA\Property(property: 'type',           type: 'string',  enum: ['sale', 'purchase']),
    new OA\Property(property: 'subcategory_id', type: 'integer', example: 5),
    new OA\Property(property: 'region_id',      type: 'integer', example: 3),
    new OA\Property(property: 'user_id',        type: 'integer', example: 42),
    new OA\Property(property: 'latitude',       type: 'number',  example: 41.2995),
    new OA\Property(property: 'longitude',      type: 'number',  example: 69.2401),
    new OA\Property(property: 'showNumber',     type: 'boolean', example: true),
    new OA\Property(property: 'number',         type: 'string',  nullable: true, example: '+998901234567'),
    new OA\Property(property: 'created_at',     type: 'string',  format: 'date-time'),
    new OA\Property(property: 'updated_at',     type: 'string',  format: 'date-time'),
    new OA\Property(property: 'region',         type: 'string',  nullable: true, example: 'Ташкент - Юнусабадский'),
    new OA\Property(property: 'images',         type: 'array',   items: new OA\Items(ref: '#/components/schemas/ProductImage')),
])]
class ProductDetailSchema {}
