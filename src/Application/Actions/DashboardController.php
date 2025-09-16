<?php
namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;

class DashboardController
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


    public function dashboard(Request $request, Response $response): Response
    {
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        $body = $this->twig->getEnvironment()->render('dashboard.html.twig', [
            'user_name' => $_SESSION['user_name'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null,
            'csrf' => $csrf
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    public function dashboardNhsUser(Request $request, Response $response): Response
    {
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        $body = $this->twig->getEnvironment()->render('dashboard/dashboard_nhs_user.html.twig', [
            'user_name' => $_SESSION['user_name'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null,
            'csrf' => $csrf
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    public function dashboardPatient(Request $request, Response $response): Response
    {
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        $body = $this->twig->getEnvironment()->render('dashboard/dashboard_patient.html.twig', [
            'user_name' => $_SESSION['user_name'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null,
            'csrf' => $csrf
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    public function dashboardFamily(Request $request, Response $response): Response
    {
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        $body = $this->twig->getEnvironment()->render('dashboard/dashboard_family.html.twig', [
            'user_name' => $_SESSION['user_name'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null,
            'csrf' => $csrf
        ]);
        $response->getBody()->write($body);
        return $response;
    }


    public function myProfile(Request $request, Response $response): Response
    {
        $body = $this->twig->getEnvironment()->render('users_pages/my_profile.html.twig', [
            'currentUser' => $_SESSION['currentUser'] ?? [
                'first_name' => $_SESSION['user_name'] ?? '',
                'surname' => '',
                'email' => $_SESSION['user_email'] ?? ''
            ]
        ]);
        $response->getBody()->write($body);
        return $response;
    }


    public function deleteAccount(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            session_unset();
            session_destroy();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
