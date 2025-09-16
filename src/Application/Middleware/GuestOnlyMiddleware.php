<?php
namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class GuestOnlyMiddleware implements Middleware
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        if (!empty($_SESSION['user_id'])) {
            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }
        return $handler->handle($request);
    }
}
