<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'UpdateProductRequest', required: ['name', 'description', 'subcategory_id', 'latitude', 'longitude', 'price', 'currency', 'type', 'child_region_id'], type: 'object', properties: [
    new OA\Property(property: 'name',            type: 'string',  example: 'iPhone 14 Pro Max'),
    new OA\Property(property: 'description',     type: 'string',  example: 'Updated description'),
    new OA\Property(property: 'subcategory_id',  type: 'integer', example: 5),
    new OA\Property(property: 'price',           type: 'number',  example: 1300),
    new OA\Property(property: 'currency',        type: 'string',  enum: ['сум', 'доллар']),
    new OA\Property(property: 'type',            type: 'string',  enum: ['sale', 'purchase']),
    new OA\Property(property: 'child_region_id', type: 'integer', example: 3),
    new OA\Property(property: 'latitude',        type: 'number',  example: 41.2995),
    new OA\Property(property: 'longitude',       type: 'number',  example: 69.2401),
    new OA\Property(property: 'images',          type: 'array',   items: new OA\Items(type: 'string', format: 'binary')),
    new OA\Property(property: 'attributes',      type: 'array',   items: new OA\Items(type: 'integer')),
])]
class UpdateProductRequestSchema {}
