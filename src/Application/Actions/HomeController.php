<?php
namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class HomeController
{
    private Twig $twig;

    public function __construct(Twig $twig)
    {
        $this->twig = $twig;
    }


    public function home(Request $request, Response $response): Response
    {
        // Example: fetch testimonials if needed
        // $pdo = ... (inject if needed)
        // $testimonialRepo = new ...
        // $testimonials = $testimonialRepo->getTestimonials();
        $body = $this->twig->getEnvironment()->render('home.html.twig', [
            // 'testimonials' => $testimonials
        ]);
        $response->getBody()->write($body);
        return $response;
    }


    public function privacyPolicy(Request $request, Response $response): Response
    {
        $body = $this->twig->getEnvironment()->render('privacy_policy.html.twig');
        $response->getBody()->write($body);
        return $response;
    }
}
