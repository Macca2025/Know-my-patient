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
        \App\Infrastructure\Persistence\User\DatabaseAuditLogRepository::class => function (ContainerInterface $c): \App\Infrastructure\Persistence\User\DatabaseAuditLogRepository {
            return new \App\Infrastructure\Persistence\User\DatabaseAuditLogRepository($c->get(\PDO::class));
        },
        \App\Domain\User\AuditLogRepository::class => function (ContainerInterface $c): \App\Domain\User\AuditLogRepository {
            return $c->get(\App\Infrastructure\Persistence\User\DatabaseAuditLogRepository::class);
        },
    ]);
    $containerBuilder->addDefinitions([
        \App\Infrastructure\Persistence\User\DatabasePatientProfileRepository::class => function (ContainerInterface $c): \App\Infrastructure\Persistence\User\DatabasePatientProfileRepository {
            return new \App\Infrastructure\Persistence\User\DatabasePatientProfileRepository($c->get(\PDO::class));
        },
        \App\Domain\User\PatientProfileRepository::class => function (ContainerInterface $c): \App\Domain\User\PatientProfileRepository {
            return $c->get(\App\Infrastructure\Persistence\User\DatabasePatientProfileRepository::class);
        },
        \App\Application\Actions\Healthcare\PatientProfileApiAction::class => function (ContainerInterface $c): \App\Application\Actions\Healthcare\PatientProfileApiAction {
            return new \App\Application\Actions\Healthcare\PatientProfileApiAction(
                $c->get(\Psr\Log\LoggerInterface::class),
                $c->get(\App\Domain\User\PatientProfileRepository::class),
                $c->get(\App\Domain\User\AuditLogRepository::class),
                $c->get(\App\Application\Services\SessionService::class)
            );
        },
    ]);
    $containerBuilder->addDefinitions([
    \App\Infrastructure\Persistence\Testimonial\DatabaseTestimonialRepository::class => function (ContainerInterface $c): \App\Infrastructure\Persistence\Testimonial\DatabaseTestimonialRepository {
        return new \App\Infrastructure\Persistence\Testimonial\DatabaseTestimonialRepository($c->get(\PDO::class));
    },
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
                \App\Application\Middleware\AuthMiddleware::class => function (ContainerInterface $c): \App\Application\Middleware\AuthMiddleware {
            return new \App\Application\Middleware\AuthMiddleware(
                $c->get(\App\Application\Services\SessionService::class)
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
                    \App\Application\Services\SessionService::class => function (): \App\Application\Services\SessionService {
                    return new \App\Application\Services\SessionService();
                },
                \App\Application\Actions\AdminController::class => function (ContainerInterface $c): \App\Application\Actions\AdminController {
                    return new \App\Application\Actions\AdminController(
                        $c->get(\Slim\Views\Twig::class),
                        $c->get(\PDO::class),
                        $c->get(\Psr\Log\LoggerInterface::class),
                        $c->get(\App\Application\Services\SessionService::class)
                    );
                },
                
                \App\Application\Actions\CardRequestsController::class => function (ContainerInterface $c): \App\Application\Actions\CardRequestsController {
                    return new \App\Application\Actions\CardRequestsController(
                        $c->get(\PDO::class),
                        $c->get(\App\Application\Services\SessionService::class),
                        $c->get(\Slim\Views\Twig::class)
                    );
                },
                
                \App\Application\Actions\DashboardController::class => function (ContainerInterface $c): \App\Application\Actions\DashboardController {
                    return new \App\Application\Actions\DashboardController(
                        $c->get(\Slim\Views\Twig::class),
                        $c->get(\PDO::class),
                        $c->get(\Psr\Log\LoggerInterface::class),
                        $c->get(\App\Application\Services\SessionService::class),
                        $c->get(\App\Application\Actions\CardRequestsController::class)
                    );
                },
                
                \App\Application\Actions\AddPatientController::class => function (ContainerInterface $c): \App\Application\Actions\AddPatientController {
                    return new \App\Application\Actions\AddPatientController(
                        $c->get(\Slim\Views\Twig::class),
                        $c->get(\PDO::class),
                        $c->get(\Psr\Log\LoggerInterface::class),
                        $c->get(\App\Application\Services\SessionService::class)
                    );
                },
                
                \App\Application\Actions\HomeController::class => function (ContainerInterface $c): \App\Application\Actions\HomeController {
                    return new \App\Application\Actions\HomeController(
                        $c->get(\Slim\Views\Twig::class),
                        $c->get(\App\Infrastructure\Persistence\Testimonial\DatabaseTestimonialRepository::class)
                    );
                },
                'csrf' => function(ContainerInterface $c): \Slim\Csrf\Guard {
                    $responseFactory = $c->get(\Psr\Http\Message\ResponseFactoryInterface::class);
                    return new \Slim\Csrf\Guard($responseFactory);
                },
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
                \App\Application\Middleware\AdminOnlyMiddleware::class => function (ContainerInterface $c): \App\Application\Middleware\AdminOnlyMiddleware {
                    return new \App\Application\Middleware\AdminOnlyMiddleware(
                        $c->get(\App\Application\Services\SessionService::class),
                        $c->get(\Slim\Views\Twig::class)
                    );
                },
                \App\Application\Middleware\NhsUserOnlyMiddleware::class => function (ContainerInterface $c): \App\Application\Middleware\NhsUserOnlyMiddleware {
                    return new \App\Application\Middleware\NhsUserOnlyMiddleware(
                        $c->get(\App\Application\Services\SessionService::class)
                    );
                },
                \App\Application\Middleware\PatientOnlyMiddleware::class => function (ContainerInterface $c): \App\Application\Middleware\PatientOnlyMiddleware {
                    return new \App\Application\Middleware\PatientOnlyMiddleware(
                        $c->get(\App\Application\Services\SessionService::class)
                    );
                },
                \App\Application\Middleware\FamilyOnlyMiddleware::class => function (ContainerInterface $c): \App\Application\Middleware\FamilyOnlyMiddleware {
                    return new \App\Application\Middleware\FamilyOnlyMiddleware(
                        $c->get(\App\Application\Services\SessionService::class)
                    );
                },
                \App\Application\Middleware\GuestOnlyMiddleware::class => function (ContainerInterface $c): \App\Application\Middleware\GuestOnlyMiddleware {
                    return new \App\Application\Middleware\GuestOnlyMiddleware(
                        $c->get(\App\Application\Services\SessionService::class)
                    );
                },
                \App\Application\Middleware\TwigGlobalsMiddleware::class => function (ContainerInterface $c): \App\Application\Middleware\TwigGlobalsMiddleware {
                    $slimTwig = $c->get(\Slim\Views\Twig::class);
                    return new \App\Application\Middleware\TwigGlobalsMiddleware(
                        $slimTwig->getEnvironment(),
                        $c->get(\App\Application\Services\SessionService::class)
                    );
                },
                \App\Application\Middleware\SessionMiddleware::class => function (ContainerInterface $c): \App\Application\Middleware\SessionMiddleware {
                    return new \App\Application\Middleware\SessionMiddleware(
                        $c->get(\App\Application\Services\SessionService::class)
                    );
                },
            ]);
        };