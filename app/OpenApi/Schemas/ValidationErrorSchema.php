<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'ValidationError', type: 'object', properties: [
    new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
    new OA\Property(property: 'errors', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string'))),
])]
class ValidationErrorSchema {}
