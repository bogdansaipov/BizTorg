<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'Region', type: 'object', properties: [
    new OA\Property(property: 'id',        type: 'integer', example: 1),
    new OA\Property(property: 'name',      type: 'string',  example: 'Tashkent'),
    new OA\Property(property: 'slug',      type: 'string',  example: 'tashkent'),
    new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: null),
])]
class RegionSchema {}
