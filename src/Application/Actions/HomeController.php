<?php
namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;


class HomeController
{
    private Twig $twig;
    private $testimonialRepo;

    public function __construct(Twig $twig, $testimonialRepo)
    {
        $this->twig = $twig;
        $this->testimonialRepo = $testimonialRepo;
    }

    public function home(Request $request, Response $response): Response
    {
        $testimonials = $this->testimonialRepo->getTestimonials();
        $body = $this->twig->getEnvironment()->render('home.html.twig', [
            'testimonials' => $testimonials,
            'current_route' => 'home'
        ]);
        $response->getBody()->write($body);
        return $response;
    }


    public function privacyPolicy(Request $request, Response $response): Response
    {
        $body = $this->twig->getEnvironment()->render('privacy_policy.html.twig', [
            'current_route' => 'privacy_policy'
        ]);
        $response->getBody()->write($body);
        return $response;
    }
}
