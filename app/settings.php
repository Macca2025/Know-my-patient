<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    // Load environment variables
    $appEnv = $_ENV['APP_ENV'] ?? 'production';
    $isProduction = $appEnv === 'production';

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () use ($isProduction, $appEnv) {
            return new Settings([
                'environment'         => $appEnv, // Add environment to settings
                'displayErrorDetails' => !$isProduction, // False in production
                'logError'            => true,
                'logErrorDetails'     => true,
                'logger' => [
                    'name' => 'know-my-patient',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => $isProduction ? Logger::WARNING : Logger::DEBUG,
                ],
                'sentry' => [
                    'dsn' => $_ENV['SENTRY_DSN'] ?? null,
                    'environment' => $_ENV['SENTRY_ENVIRONMENT'] ?? $appEnv,
                    'traces_sample_rate' => (float)($_ENV['SENTRY_TRACES_SAMPLE_RATE'] ?? 0.2),
                    'send_default_pii' => filter_var($_ENV['SENTRY_SEND_DEFAULT_PII'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ],
            ]);
        }
    ]);
};
