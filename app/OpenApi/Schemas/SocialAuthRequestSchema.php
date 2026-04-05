<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'SocialAuthRequest', required: ['access_token'], type: 'object', properties: [
    new OA\Property(property: 'access_token', type: 'string', example: 'ya29.abc...'),
])]
class SocialAuthRequestSchema {}
