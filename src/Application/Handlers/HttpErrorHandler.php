<?php

declare(strict_types=1);

namespace App\Application\Handlers;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Slim\Views\Twig;

class HttpErrorHandler extends SlimErrorHandler
{
    protected LoggerInterface $logger;

    /**
     * @param callable $callableResolver
     * @param callable $responseFactory
     */
    public function __construct(callable $callableResolver, callable $responseFactory)
    {
        parent::__construct($callableResolver, $responseFactory);
        $this->logger = new NullLogger();
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): void
    {
        if ($container->has(LoggerInterface::class)) {
            $logger = $container->get(LoggerInterface::class);
            if ($logger instanceof LoggerInterface) {
                $this->logger = $logger;
            } else {
                $this->logger = new NullLogger();
            }
        } else {
            // Ensure logger property always contains a LoggerInterface
            $this->logger = new NullLogger();
        }
    }
}