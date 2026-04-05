<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'RateShopRequest', required: ['shop_id', 'rating'], type: 'object', properties: [
    new OA\Property(property: 'shop_id', type: 'integer', example: 1),
    new OA\Property(property: 'rating',  type: 'number',  minimum: 1, maximum: 5, example: 4),
])]
class RateShopRequestSchema {}
