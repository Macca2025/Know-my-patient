<?php
declare(strict_types=1);
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
    \App\Application\Actions\SupportController::class => function (ContainerInterface $c): \App\Application\Actions\SupportController {
            return new \App\Application\Actions\SupportController(
                $c->get(\Slim\Views\Twig::class),
                $c->get(\App\Infrastructure\Persistence\Support\DatabaseSupportMessageRepository::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        },
    \App\Application\Actions\OnboardingController::class => function (ContainerInterface $c): \App\Application\Actions\OnboardingController {
            return new \App\Application\Actions\OnboardingController(
                $c->get(\Slim\Views\Twig::class),
                $c->get(\App\Infrastructure\Persistence\Onboarding\DatabaseOnboardingEnquiryRepository::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        },
    \App\Infrastructure\Persistence\Onboarding\DatabaseOnboardingEnquiryRepository::class => function (ContainerInterface $c): \App\Infrastructure\Persistence\Onboarding\DatabaseOnboardingEnquiryRepository {
            return new \App\Infrastructure\Persistence\Onboarding\DatabaseOnboardingEnquiryRepository($c->get(\PDO::class));
        },
        // Slim\Views\Twig definitions for DI
    \Slim\Views\Twig::class => function (): \Slim\Views\Twig {
        return \Slim\Views\Twig::create(
            __DIR__ . '/../templates',
            [
                'cache' => false,
                'auto_reload' => true
            ]
        );
        },
        // Bind Slim ResponseFactory to PSR-17 ResponseFactoryInterface for CSRF
    \Psr\Http\Message\ResponseFactoryInterface::class => function(ContainerInterface $c): \Psr\Http\Message\ResponseFactoryInterface {
            /** @var \Slim\App $app */
            $app = $c->get('app');
            return $app->getResponseFactory();
        },
    \Psr\Log\LoggerInterface::class => function (ContainerInterface $c): \Psr\Log\LoggerInterface {
            $settings = $c->get(\App\Application\Settings\SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new \Monolog\Logger($loggerSettings['name']);

            $processor = new \Monolog\Processor\UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new \Monolog\Handler\StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },

        // Register Slim CSRF Guard in the container
    'csrf' => function(ContainerInterface $c): \Slim\Csrf\Guard {
            $responseFactory = $c->get(\Psr\Http\Message\ResponseFactoryInterface::class);
            return new \Slim\Csrf\Guard($responseFactory);
        },
        // PDO Database connection
    'pdo' => function (): \PDO {
            $required = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];
            foreach ($required as $var) {
                if (empty($_ENV[$var])) {
                    throw new RuntimeException("Environment variable $var is not set or empty");
                }
            }
            $pdo = new \PDO(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8',
                    $_ENV['DB_HOST'],
                    $_ENV['DB_PORT'],
                    $_ENV['DB_NAME']
                ),
                $_ENV['DB_USER'],
                $_ENV['DB_PASS']
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $pdo;
        },
    \PDO::class => function (ContainerInterface $c): \PDO {
            return $c->get('pdo');
        },
    \App\Infrastructure\Persistence\Support\DatabaseSupportMessageRepository::class => function (ContainerInterface $c): \App\Infrastructure\Persistence\Support\DatabaseSupportMessageRepository {
            return new \App\Infrastructure\Persistence\Support\DatabaseSupportMessageRepository($c->get(\PDO::class));
        },
    ]);
};