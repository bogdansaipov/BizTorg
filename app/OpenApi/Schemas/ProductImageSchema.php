<?php
namespace App\OpenApi\Schemas;
use OpenApi\Attributes as OA;
#[OA\Schema(schema: 'ProductImage', type: 'object', properties: [
    new OA\Property(property: 'image_url', type: 'string', example: 'product-images/photo.jpg'),
])]
class ProductImageSchema {}
