<?php

return [
    'default' => 'default',

    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'BizTorg API',
            ],

            'routes' => [
                /*
                 * Route for accessing the Swagger UI: /api/documentation
                 */
                'api' => 'api/documentation',
            ],

            'paths' => [
                /*
                 * File path to store the generated docs JSON/YAML.
                 */
                'docs'         => public_path('docs'),
                'docs_json'    => 'api-docs.json',
                'docs_yaml'    => 'api-docs.yaml',
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),

                /*
                 * Directories to scan for OpenAPI annotations.
                 * Scans the whole app directory.
                 */
                'annotations' => [
                    base_path('app'),
                ],
            ],
        ],
    ],

    'defaults' => [
        'routes' => [
            'docs'             => 'docs',
            'oauth2_callback'  => 'api/oauth2-callback',
            'middleware' => [
                'api'            => [],
                'asset'          => [],
                'docs'           => [],
                'oauth2_callback' => [],
            ],
            'group_options' => [],
        ],

        'paths' => [
            'views'                  => base_path('vendor/swagger-api/swagger-ui/dist/'),
            'base'                   => env('L5_SWAGGER_BASE_PATH', null),
            'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
            'excludes'               => [],
        ],

        'scanOptions' => [
            'default_processors_configuration' => [],
            'analyser'   => null,
            'analysis'   => null,
            'processors' => [],
            'pattern'    => null,
            'exclude'    => [
                base_path('app/Helpers'),
            ],
            'open_api_spec_version' => env(
                'L5_SWAGGER_OPEN_API_SPEC_VERSION',
                \L5Swagger\Generator::OPEN_API_DEFAULT_SPEC_VERSION
            ),
        ],

        'securityDefinitions' => [
            'securitySchemes' => [],
            'security'        => [],
        ],

        /*
         * Set to true in development so docs regenerate on every request.
         */
        'generate_always'   => env('L5_SWAGGER_GENERATE_ALWAYS', false),
        'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', false),

        'proxy'                => false,
        'additional_config_url' => null,
        'operations_sort'      => env('L5_SWAGGER_OPERATIONS_SORT', null),
        'validator_url'        => null,

        'ui' => [
            'display' => [
                'doc_expansion'           => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),
                'filter'                  => env('L5_SWAGGER_UI_FILTER', true),
                'show_extensions'         => env('L5_SWAGGER_UI_SHOW_EXTENSIONS', true),
                'show_common_extensions'  => env('L5_SWAGGER_UI_SHOW_COMMON_EXTENSIONS', true),
                'try_it_out_enabled'      => env('L5_SWAGGER_TRY_IT_OUT_ENABLED', true),
                'with_credentials'        => env('L5_SWAGGER_WITH_CREDENTIALS', false),
            ],
            'authorization' => [
                'persist_authorization' => env('L5_SWAGGER_PERSIST_AUTHORIZATION', true),
            ],
        ],

        /*
         * L5_SWAGGER_CONST_HOST is used inside #[OA\Server(url: L5_SWAGGER_CONST_HOST)]
         * annotations. Set this to your API base URL.
         */
        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', env('APP_URL', 'http://localhost')),
        ],
    ],
];
