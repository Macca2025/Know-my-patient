<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AdminOnlyMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Only start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userRole = $_SESSION['user_role'] ?? null;
        if ($userRole !== 'admin') {
            $response = new \Slim\Psr7\Response();
            $response = $response->withStatus(403);
            $response->getBody()->write('Forbidden: Admins only');
            return $response;
        }
        return $handler->handle($request);
    }
}
