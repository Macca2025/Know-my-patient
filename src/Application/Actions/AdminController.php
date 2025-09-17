<?php
namespace App\Application\Actions;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use App\Application\Services\SessionService;

class AdminController
{

    private Twig $twig;
    private \PDO $pdo;
    private LoggerInterface $logger;
    private SessionService $session;

    public function __construct(Twig $twig, \PDO $pdo, LoggerInterface $logger, SessionService $session)
    {
        $this->twig = $twig;
        $this->pdo = $pdo;
        $this->logger = $logger;
        $this->session = $session;
    }

    public function users(Request $request, Response $response): Response
    {
    if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Admins only.</p></div>');
            return $response;
        }
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
    if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Admins only.</p></div>');
            return $response;
        }
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
    if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Admins only.</p></div>');
            return $response;
        }
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
    if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Admins only.</p></div>');
            return $response;
        }
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
    if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Admins only.</p></div>');
            return $response;
        }
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
    if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Admins only.</p></div>');
            return $response;
        }
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
    if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Admins only.</p></div>');
            return $response;
        }
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
