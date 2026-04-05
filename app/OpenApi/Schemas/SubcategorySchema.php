<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'Subcategory', type: 'object', properties: [
    new OA\Property(property: 'id',          type: 'integer', example: 5),
    new OA\Property(property: 'name',        type: 'string',  example: 'Smartphones'),
    new OA\Property(property: 'slug',        type: 'string',  example: 'smartfony'),
    new OA\Property(property: 'category_id', type: 'integer', example: 1),
])]
class SubcategorySchema {}
