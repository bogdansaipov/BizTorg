<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'SendVerificationRequest', required: ['email'], type: 'object', properties: [
    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
])]
class SendVerificationRequestSchema {}
