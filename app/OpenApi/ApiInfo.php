<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Global OpenAPI metadata, server, and security scheme.
 * This class is never instantiated — it exists solely to hold OA attributes.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'BizTorg API',
    description: 'BizTorg marketplace REST API. All authenticated endpoints require a Bearer token obtained from /api/v1/auth/login or /api/v1/auth/register.',
    contact: new OA\Contact(name: 'BizTorg Support'),
)]
#[OA\Server(
    url: 'http://localhost',
    description: 'API Server',
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'token',
    description: 'Enter the token obtained from /api/v1/auth/login',
)]
#[OA\Tag(name: 'Auth',          description: 'Authentication — register, login, social, verification')]
#[OA\Tag(name: 'Users',         description: 'User lookup, FCM token management')]
#[OA\Tag(name: 'Profiles',      description: 'User profile read and update')]
#[OA\Tag(name: 'Regions',       description: 'Region / city hierarchy')]
#[OA\Tag(name: 'Categories',    description: 'Categories, subcategories, home feed, search')]
#[OA\Tag(name: 'Products',      description: 'Product listings, detail, CRUD, images, favorites')]
#[OA\Tag(name: 'Messaging',     description: 'Chat messages and conversations')]
#[OA\Tag(name: 'Notifications', description: 'In-app notifications')]
#[OA\Tag(name: 'Shops',         description: 'Shop profiles, subscriptions, ratings')]
class ApiInfo
{
}
