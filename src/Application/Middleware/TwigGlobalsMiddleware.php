<?php
namespace App\Application\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Twig\Environment;

class TwigGlobalsMiddleware
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
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
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_email' => $_SESSION['user_email'] ?? null,
            'user_name' => $_SESSION['user_name'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null,
        ]);

        return $handler->handle($request);
    }
}
