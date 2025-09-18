<?php
declare(strict_types=1);

namespace App\Application\Middleware;
use App\Application\Services\SessionService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Views\Twig;

class AdminOnlyMiddleware implements MiddlewareInterface
{
    private $sessionService;
    private $twig;

    public function __construct(SessionService $sessionService, Twig $twig)
    {
        $this->sessionService = $sessionService;
        $this->twig = $twig;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $userRole = $this->sessionService->get('user_role');
        if ($userRole !== 'admin') {
            $response = new \Slim\Psr7\Response();
            $response = $response->withStatus(403);
            $body = $this->twig->getEnvironment()->render('errors/error_403.html.twig', [
                'title' => '403 Forbidden',
                'description' => 'You do not have permission to access this page.',
                'session' => $this->sessionService->all(),
            ]);
            $response->getBody()->write($body);
            return $response;
        }
        return $handler->handle($request);
    }
}
