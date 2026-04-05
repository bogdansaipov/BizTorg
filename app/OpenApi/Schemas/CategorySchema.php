<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'Category', type: 'object', properties: [
    new OA\Property(property: 'id',        type: 'integer', example: 1),
    new OA\Property(property: 'name',      type: 'string',  example: 'Electronics'),
    new OA\Property(property: 'slug',      type: 'string',  example: 'elektronika'),
    new OA\Property(property: 'image_url', type: 'string',  nullable: true),
])]
class CategorySchema {}
