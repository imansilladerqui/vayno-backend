<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $origins = array_values(array_filter(array_map(
        trim(...),
        explode(',', $_ENV['CORS_ORIGINS'] ?? $_SERVER['CORS_ORIGINS'] ?? 'http://localhost:3000,https://vayno.vercel.app'),
    )));

    $container->extension('nelmio_cors', [
        'defaults' => [
            'origin_regex' => false,
            'allow_origin' => $origins,
            'allow_credentials' => false,
            'allow_headers' => ['Authorization', 'Content-Type', 'Accept'],
            'allow_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            'max_age' => 600,
        ],
        'paths' => [
            '^/api/' => [
                'allow_origin' => $origins,
            ],
        ],
    ]);
};
