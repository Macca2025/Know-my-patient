<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class PatientOnlyMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userRole = $_SESSION['user_role'] ?? null;
        if ($userRole !== 'patient') {
            $response = new \Slim\Psr7\Response();
            $response = $response->withStatus(403);
            $response->getBody()->write('Forbidden: Patients only');
            return $response;
        }
        return $handler->handle($request);
    }
}
