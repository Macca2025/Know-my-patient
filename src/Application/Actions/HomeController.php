<?php
namespace App\Application\Actions;

use App\Application\Services\CacheService;
use App\Infrastructure\Persistence\Testimonial\DatabaseTestimonialRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;


class HomeController
{
    private Twig $twig;
    private DatabaseTestimonialRepository $testimonialRepo;
    private CacheService $cacheService;

    public function __construct(Twig $twig, DatabaseTestimonialRepository $testimonialRepo, CacheService $cacheService)
    {
        $this->twig = $twig;
        $this->testimonialRepo = $testimonialRepo;
        $this->cacheService = $cacheService;
    }

    public function home(Request $request, Response $response): Response
    {
        // Cache testimonials for 1 hour (3600 seconds)
        $testimonials = $this->cacheService->remember('testimonials_homepage', function() {
            return $this->testimonialRepo->getTestimonials();
        }, 3600);
        
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
