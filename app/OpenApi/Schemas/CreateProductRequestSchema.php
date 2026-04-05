<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'CreateProductRequest', required: ['uuid', 'name', 'description', 'subcategory_id', 'latitude', 'longitude', 'price', 'currency', 'type', 'child_region_id', 'showNumber'], type: 'object', properties: [
    new OA\Property(property: 'uuid',            type: 'integer', example: 42),
    new OA\Property(property: 'name',            type: 'string',  example: 'iPhone 14 Pro'),
    new OA\Property(property: 'description',     type: 'string',  example: 'Brand new'),
    new OA\Property(property: 'subcategory_id',  type: 'integer', example: 5),
    new OA\Property(property: 'price',           type: 'number',  example: 1200),
    new OA\Property(property: 'currency',        type: 'string',  enum: ['сум', 'доллар']),
    new OA\Property(property: 'type',            type: 'string',  enum: ['sale', 'purchase']),
    new OA\Property(property: 'child_region_id', type: 'integer', example: 3),
    new OA\Property(property: 'latitude',        type: 'number',  example: 41.2995),
    new OA\Property(property: 'longitude',       type: 'number',  example: 69.2401),
    new OA\Property(property: 'showNumber',      type: 'boolean', example: true),
    new OA\Property(property: 'number',          type: 'string',  nullable: true, example: '+998901234567'),
    new OA\Property(property: 'images',          type: 'array',   items: new OA\Items(type: 'string', format: 'binary'), description: 'Product images (multipart upload)'),
    new OA\Property(property: 'attributes',      type: 'array',   items: new OA\Items(type: 'integer'), description: 'Array of attribute_value IDs'),
])]
class CreateProductRequestSchema {}
