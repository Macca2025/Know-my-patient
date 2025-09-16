<?php
// app/Application/Middleware/CsrfLoggingMiddleware.php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Csrf\Guard;
use Slim\Middleware\ErrorMiddleware;
use Slim\Psr7\Response;

class CsrfLoggingMiddleware
{
    private $csrf;
    private $logger;

    public function __construct(Guard $csrf, LoggerInterface $logger)
    {
        $this->csrf = $csrf;
        $this->logger = $logger;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $this->logger->info('CSRF Middleware: Incoming request', [
            'method' => $request->getMethod(),
            'uri' => (string)$request->getUri(),
            'post' => $request->getParsedBody(),
            'cookies' => $request->getCookieParams(),
            'session' => $_SESSION ?? null
        ]);
        try {
            return ($this->csrf)($request, $response, $next);
        } catch (\Throwable $e) {
            $this->logger->error('CSRF Middleware Exception', [
                'exception' => $e,
                'post' => $request->getParsedBody(),
                'cookies' => $request->getCookieParams(),
                'session' => $_SESSION ?? null
            ]);
            $response = new Response();
            $response->getBody()->write('CSRF validation failed.');
            return $response->withStatus(400);
        }
    }
}
