<?php
declare(strict_types=1);

namespace App\Application\Middleware;
use App\Application\Services\SessionService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class NhsUserOnlyMiddleware implements MiddlewareInterface
{
    private $sessionService;

    public function __construct(\App\Application\Services\SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $userRole = $this->sessionService->get('user_role');
        if ($userRole !== 'nhs_user') {
            $response = new \Slim\Psr7\Response();
            $response = $response->withStatus(403);
            $response->getBody()->write('Forbidden: NHS users only');
            return $response;
        }
        return $handler->handle($request);
    }
}
