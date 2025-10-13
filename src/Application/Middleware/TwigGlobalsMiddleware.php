<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Services\SessionService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Twig\Environment;

class TwigGlobalsMiddleware
{
    private Environment $twig;
    private SessionService $sessionService;

    public function __construct(Environment $twig, SessionService $sessionService)
    {
        $this->twig = $twig;
        $this->sessionService = $sessionService;
    }

    public function __invoke(Request $request, Handler $handler): Response
    {
        $this->twig->addGlobal('title', 'Know My Patient');
        $this->twig->addGlobal('company_name', 'Know My Patient');
        $this->twig->addGlobal('company_logo', 'images/logo.png');
        $this->twig->addGlobal('description', 'Your app description here.');
        $this->twig->addGlobal('keywords', 'health, patient, care');
        $this->twig->addGlobal('app_name', 'Know My Patient');
        $this->twig->addGlobal('canonical_url', 'https://knowmypatient.info/');
        $this->twig->addGlobal('app_version', '1.0.0');
        $this->twig->addGlobal('year', date('Y'));

        // Add session globals for authentication state
        $this->twig->addGlobal('session', [
            'user_id' => $this->sessionService->get('user_id'),
            'user_email' => $this->sessionService->get('user_email'),
            'user_name' => $this->sessionService->get('user_name'),
            'user_role' => $this->sessionService->get('user_role'),
        ]);

        return $handler->handle($request);
    }
}
