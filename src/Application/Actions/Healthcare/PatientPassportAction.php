<?php
namespace App\Application\Actions\Healthcare;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Application\Services\SessionService;

class PatientPassportAction
{
    private Twig $twig;
    private SessionService $sessionService;

    public function __construct(Twig $twig, SessionService $sessionService)
    {
        $this->twig = $twig;
        $this->sessionService = $sessionService;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $role = $this->sessionService->get('user_role');
        if ($role !== 'nhs_user') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Only NHS users can access this page.</p></div>');
            return $response;
        }
        $body = $this->twig->getEnvironment()->render('healthcare_pages/patient_passport.html.twig', []);
        $response->getBody()->write($body);
        return $response;
    }
}
