<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'Pagination', type: 'object', properties: [
    new OA\Property(property: 'current_page', type: 'integer', example: 1),
    new OA\Property(property: 'last_page',    type: 'integer', example: 10),
    new OA\Property(property: 'per_page',     type: 'integer', example: 24),
    new OA\Property(property: 'total',        type: 'integer', example: 240),
    new OA\Property(property: 'has_more',     type: 'boolean', example: true),
])]
class PaginationSchema {}
