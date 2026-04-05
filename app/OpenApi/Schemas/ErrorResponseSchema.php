<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'Error', type: 'object', properties: [
    new OA\Property(property: 'status',  type: 'string', example: 'error'),
    new OA\Property(property: 'message', type: 'string', example: 'Something went wrong'),
])]
class ErrorResponseSchema {}
