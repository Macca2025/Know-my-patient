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

        // Display page for /display route
    public function displayPage(Request $request, Response $response): Response
    {
        $userId = $this->sessionService->get('user_id');
        $uniqueCode = '';
        $qrDataUri = '';
        if ($userId) {
            $stmt = $this->pdo->prepare('SELECT uid FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($user && !empty($user['uid'])) {
                $uniqueCode = $user['uid'];
                // Generate QR code using endroid/qr-code v6.x
                $appUrl = rtrim(getenv('APP_URL') ?: ($_SERVER['APP_URL'] ?? ''), '/') . '/';
                $qrContent = $appUrl . $uniqueCode;
                $qrCode = new \Endroid\QrCode\QrCode($qrContent);
                $writer = new \Endroid\QrCode\Writer\PngWriter();
                $result = $writer->write($qrCode);
                $qrDataUri = $result->getDataUri();
            }
        }
        $body = $this->twig->getEnvironment()->render('users_pages/display.html.twig', [
            'uniqueCode' => $uniqueCode,
            'qrDataUri' => $qrDataUri
        ]);
        $response->getBody()->write($body);
        return $response;
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
        // Get user ID from session
        $userId = $this->sessionService->get('user_id');
        $currentUser = [
            'first_name' => '',
            'surname' => '',
            'email' => '',
            'user_id' => '',
            'role' => '',
            'is_verified' => 0,
            'created_at' => null
        ];
        if ($userId) {
            $stmt = $this->pdo->prepare('SELECT id, first_name, last_name, email, role, is_verified, created_at FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($user) {
                $currentUser['first_name'] = $user['first_name'] ?? '';
                $currentUser['surname'] = $user['last_name'] ?? '';
                $currentUser['email'] = $user['email'] ?? '';
                $currentUser['user_id'] = $user['id'] ?? '';
                $currentUser['role'] = $user['role'] ?? '';
                $currentUser['is_verified'] = $user['is_verified'] ?? 0;
                $currentUser['created_at'] = $user['created_at'] ?? null;
            }
        }
        $body = $this->twig->getEnvironment()->render('users_pages/my_profile.html.twig', [
            'currentUser' => $currentUser
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
