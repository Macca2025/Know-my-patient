<?php
namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;

class AdminController
{
    private Twig $twig;
    private \PDO $pdo;
    private LoggerInterface $logger;

    public function __construct(Twig $twig, \PDO $pdo, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function users(Request $request, Response $response): Response
    {
        $vars = [
            'title' => 'User Management',
            'description' => 'User Management admin page',
            'canonical_url' => $request->getUri()->getPath(),
            'app_name' => 'Know My Patient',
            'company_logo' => 'images/logo.png',
            'company_name' => 'Know My Patient',
            'keywords' => 'admin, dashboard, know my patient',
        ];
        $body = $this->twig->getEnvironment()->render('admin/users.html.twig', $vars);
        $response->getBody()->write($body);
        return $response;
    }
    public function auditDashboard(Request $request, Response $response): Response
    {
        $vars = [
            'title' => 'Audit Management',
            'description' => 'Audit Management admin page',
            'canonical_url' => $request->getUri()->getPath(),
            'app_name' => 'Know My Patient',
            'company_logo' => 'images/logo.png',
            'company_name' => 'Know My Patient',
            'keywords' => 'admin, dashboard, know my patient',
        ];
        $body = $this->twig->getEnvironment()->render('admin/audit_dashboard.html.twig', $vars);
        $response->getBody()->write($body);
        return $response;
    }
    public function supportMessages(Request $request, Response $response): Response
    {
        $vars = [
            'title' => 'Support Messages',
            'description' => 'Support Messages admin page',
            'canonical_url' => $request->getUri()->getPath(),
            'app_name' => 'Know My Patient',
            'company_logo' => 'images/logo.png',
            'company_name' => 'Know My Patient',
            'keywords' => 'admin, dashboard, know my patient',
        ];
        $body = $this->twig->getEnvironment()->render('admin/support_messages.html.twig', $vars);
        $response->getBody()->write($body);
        return $response;
    }
    public function cardRequests(Request $request, Response $response): Response
    {
        $vars = [
            'title' => 'Card Requests',
            'description' => 'Card Requests admin page',
            'canonical_url' => $request->getUri()->getPath(),
            'app_name' => 'Know My Patient',
            'company_logo' => 'images/logo.png',
            'company_name' => 'Know My Patient',
            'keywords' => 'admin, dashboard, know my patient',
        ];
        $body = $this->twig->getEnvironment()->render('admin/card_requests.html.twig', $vars);
        $response->getBody()->write($body);
        return $response;
    }
    public function testimonials(Request $request, Response $response): Response
    {
        $vars = [
            'title' => 'Testimonials',
            'description' => 'Testimonials admin page',
            'canonical_url' => $request->getUri()->getPath(),
            'app_name' => 'Know My Patient',
            'company_logo' => 'images/logo.png',
            'company_name' => 'Know My Patient',
            'keywords' => 'admin, dashboard, know my patient',
        ];
        $body = $this->twig->getEnvironment()->render('admin/testimonials.html.twig', $vars);
        $response->getBody()->write($body);
        return $response;
    }
    public function onboardingEnquiries(Request $request, Response $response): Response
    {
        $vars = [
            'title' => 'Onboarding Enquiries',
            'description' => 'Onboarding Enquiries admin page',
            'canonical_url' => $request->getUri()->getPath(),
            'app_name' => 'Know My Patient',
            'company_logo' => 'images/logo.png',
            'company_name' => 'Know My Patient',
            'keywords' => 'admin, dashboard, know my patient',
        ];
        $body = $this->twig->getEnvironment()->render('admin/onboarding_enquiries.html.twig', $vars);
        $response->getBody()->write($body);
        return $response;
    }
    public function resources(Request $request, Response $response): Response
    {
        $vars = [
            'title' => 'Resources',
            'description' => 'Resources admin page',
            'canonical_url' => $request->getUri()->getPath(),
            'app_name' => 'Know My Patient',
            'company_logo' => 'images/logo.png',
            'company_name' => 'Know My Patient',
            'keywords' => 'admin, dashboard, know my patient',
        ];
        $body = $this->twig->getEnvironment()->render('admin/resources.html.twig', $vars);
        $response->getBody()->write($body);
        return $response;
    }
}
