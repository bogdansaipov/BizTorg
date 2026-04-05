<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'VerifyAndRegisterRequest', required: ['email', 'password'], type: 'object', properties: [
    new OA\Property(property: 'email',    type: 'string', format: 'email', example: 'user@example.com'),
    new OA\Property(property: 'password', type: 'string', minLength: 8, maxLength: 8, example: 'Abc!1234'),
])]
class VerifyAndRegisterRequestSchema {}
