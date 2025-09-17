<?php
namespace App\Application\Actions;

use App\Application\Services\SessionService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;

class DashboardController
{
    private Twig $twig;
    private \PDO $pdo;
    private SessionService $sessionService;

    public function __construct(Twig $twig, \PDO $pdo, LoggerInterface $logger, SessionService $sessionService)
    {
        $this->twig = $twig;
        $this->pdo = $pdo;
        $this->sessionService = $sessionService;
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
        $role = $_SESSION['user_role'] ?? null;
        $dashboardPartial = null;
        $dashboardTitle = 'User Dashboard';
        $dashboardSubtitle = 'Manage your account, privacy, and records all in one place.';
        switch ($role) {
            case 'admin':
                $dashboardPartial = 'dashboard/dashboard_admin.html.twig';
                $dashboardTitle = 'Admin Dashboard';
                $dashboardSubtitle = 'Manage the system and user accounts.';
                break;
            case 'nhs_user':
                $dashboardPartial = 'dashboard/dashboard_nhs_user.html.twig';
                $dashboardTitle = 'NHS Staff Dashboard';
                $dashboardSubtitle = 'Access patient records and NHS tools.';
                break;
            case 'patient':
                $dashboardPartial = 'dashboard/dashboard_patient.html.twig';
                break;
            case 'family':
                $dashboardPartial = 'dashboard/dashboard_family.html.twig';
                break;
            case 'healthcare_worker':
                $dashboardPartial = 'dashboard/dashboard_healthcare_worker.html.twig';
                break;
        }
        $currentUser = [
            'first_name' => $this->sessionService->get('user_name', ''),
            'surname' => '',
            'email' => $this->sessionService->get('user_email', '')
        ];
        $body = $this->twig->getEnvironment()->render('dashboard.html.twig', [
            'csrf' => $csrf,
            'current_route' => 'dashboard',
            'currentUser' => $currentUser,
            'dashboardPartial' => $dashboardPartial,
            'dashboardTitle' => $dashboardTitle,
            'dashboardSubtitle' => $dashboardSubtitle,
            'role' => $role
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
            'user_name' => $this->sessionService->get('user_name'),
            'user_role' => $this->sessionService->get('user_role'),
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
            'user_name' => $this->sessionService->get('user_name'),
            'user_role' => $this->sessionService->get('user_role'),
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
            'user_name' => $this->sessionService->get('user_name'),
            'user_role' => $this->sessionService->get('user_role'),
            'csrf' => $csrf
        ]);
        $response->getBody()->write($body);
        return $response;
    }


    public function myProfile(Request $request, Response $response): Response
    {
        $body = $this->twig->getEnvironment()->render('users_pages/my_profile.html.twig', [
            'currentUser' => $this->sessionService->get('currentUser', [
                'first_name' => $this->sessionService->get('user_name', ''),
                'surname' => '',
                'email' => $this->sessionService->get('user_email', '')
            ])
        ]);
        $response->getBody()->write($body);
        return $response;
    }


    public function deleteAccount(Request $request, Response $response): Response
    {
        $userId = $this->sessionService->get('user_id');
        if ($userId) {
            $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $this->sessionService->clear();
            $this->sessionService->destroy();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
