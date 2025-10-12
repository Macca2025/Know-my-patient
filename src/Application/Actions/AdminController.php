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

    // User Management
    public function users(Request $request, Response $response): Response
    {
        if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Admins only.</p></div>');
            return $response;
        }
        // Fetch all users
        $stmt = $this->pdo->query('SELECT id, email, first_name, last_name, role, active, created_at, updated_at FROM users ORDER BY created_at DESC');
        $allUsers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get selected role from GET param
        $queryParams = $request->getQueryParams();
        $selectedRole = isset($queryParams['role']) ? $queryParams['role'] : '';

        // Filter users if role selected
        $filteredUsers = $allUsers;
        if ($selectedRole) {
            $filteredUsers = array_values(array_filter($allUsers, function($u) use ($selectedRole) {
                return $u['role'] === $selectedRole;
            }));
        }

        // Pagination
        $perPage = 10;
        $page = isset($queryParams['page']) && is_numeric($queryParams['page']) && $queryParams['page'] > 0 ? (int)$queryParams['page'] : 1;
        $totalUsers = count($filteredUsers);
        $totalPages = (int) ceil($totalUsers / $perPage);
        $start = ($page - 1) * $perPage;
        $pagedUsers = array_slice($filteredUsers, $start, $perPage);

        // User stats (always calculated from all users)
        $stats = [
            'total_users' => 0,
            'active_24h' => 0,
            'admins' => 0,
            'patients' => 0,
            'nhs_users' => 0,
            'family' => 0,
        ];
        $now = new \DateTimeImmutable();
        $yesterday = $now->modify('-24 hours');
        foreach ($allUsers as $user) {
            $stats['total_users']++;
            if ($user['role'] === 'admin') $stats['admins']++;
            if ($user['role'] === 'patient') $stats['patients']++;
            if ($user['role'] === 'nhs_user') $stats['nhs_users']++;
            if ($user['role'] === 'family') $stats['family']++;
            if (!empty($user['last_login'])) {
                $lastLogin = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $user['last_login']);
                if ($lastLogin && $lastLogin > $yesterday) {
                    $stats['active_24h']++;
                }
            }
        }

        $vars = [
            'title' => 'User Management',
            'description' => 'User Management admin page',
            'canonical_url' => $request->getUri()->getPath(),
            'app_name' => 'Know My Patient',
            'company_logo' => 'images/logo.png',
            'company_name' => 'Know My Patient',
            'keywords' => 'admin, dashboard, know my patient',
            'users' => $allUsers, // for role dropdown
            'filtered_users' => $filteredUsers, // for table and pagination
            'paged_users' => $pagedUsers, // for table display
            'stats' => $stats,
            'selected_role' => $selectedRole,
            'page' => $page,
            'total_pages' => $totalPages,
        ];
        $body = $this->twig->getEnvironment()->render('admin/users.html.twig', $vars);
        $response->getBody()->write($body);
        return $response;
    }
    public function deleteUser(Request $request, Response $response): Response
    {
        if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403)->withHeader('Content-Type', 'text/html');
            $response->getBody()->write('Forbidden');
            return $response;
        }
        $data = $request->getParsedBody();
        $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
        if ($userId > 0) {
            $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $userId]);
        }
        return $response->withHeader('Location', '/admin/users')->withStatus(302);
    }
    public function suspendUser(Request $request, Response $response): Response
    {
        if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403)->withHeader('Content-Type', 'text/html');
            $response->getBody()->write('Forbidden');
            return $response;
        }
        $data = $request->getParsedBody();
        $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
        $action = isset($data['action']) ? $data['action'] : 'suspend';
        if ($userId > 0) {
            if ($action === 'unsuspend') {
                $stmt = $this->pdo->prepare('UPDATE users SET active = 1, suspended_at = NULL WHERE id = :id');
                $stmt->execute(['id' => $userId]);
            } else {
                $stmt = $this->pdo->prepare('UPDATE users SET suspended_at = NOW(), active = 0 WHERE id = :id');
                $stmt->execute(['id' => $userId]);
            }
        }
        return $response->withHeader('Location', '/admin/users')->withStatus(302);
    }

    // Audit Log
    public function auditDashboard(Request $request, Response $response): Response
    {
        if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Admins only.</p></div>');
            return $response;
        }
        try {
            $queryParams = $request->getQueryParams();
            $search = isset($queryParams['search']) ? trim($queryParams['search']) : '';
            $fromDate = isset($queryParams['from_date']) ? trim($queryParams['from_date']) : '';
            $toDate = isset($queryParams['to_date']) ? trim($queryParams['to_date']) : '';

            $sql = 'SELECT id, user_id, target_user_id, activity_type, description, ip_address, timestamp FROM audit_log WHERE 1=1';
            $params = [];
            if ($search !== '') {
                $sql .= ' AND (user_id LIKE :search OR target_user_id LIKE :search OR description LIKE :search)';
                $params['search'] = '%' . $search . '%';
            }
            if ($fromDate !== '') {
                // Convert dd/mm/yyyy to yyyy-mm-dd
                $fromParts = explode('/', $fromDate);
                if (count($fromParts) === 3) {
                    $fromDateSql = $fromParts[2] . '-' . $fromParts[1] . '-' . $fromParts[0];
                    $sql .= ' AND timestamp >= :from_date';
                    $params['from_date'] = $fromDateSql . ' 00:00:00';
                }
            }
            if ($toDate !== '') {
                $toParts = explode('/', $toDate);
                if (count($toParts) === 3) {
                    $toDateSql = $toParts[2] . '-' . $toParts[1] . '-' . $toParts[0];
                    $sql .= ' AND timestamp <= :to_date';
                    $params['to_date'] = $toDateSql . ' 23:59:59';
                }
            }
            $sql .= ' ORDER BY timestamp DESC LIMIT 100';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $auditLogs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $vars = [
                'title' => 'Audit Management',
                'description' => 'Audit Management admin page',
                'canonical_url' => $request->getUri()->getPath(),
                'app_name' => 'Know My Patient',
                'company_logo' => 'images/logo.png',
                'company_name' => 'Know My Patient',
                'keywords' => 'admin, dashboard, know my patient',
                'auditLogs' => $auditLogs,
                'search' => $search,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ];
            $body = $this->twig->getEnvironment()->render('admin/audit_dashboard.html.twig', $vars);
            $response->getBody()->write($body);
            return $response;
        } catch (\Throwable $e) {
            $this->logger->error('Audit dashboard error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            $response = $response->withStatus(500);
            $response->getBody()->write('Internal Server Error: ' . htmlspecialchars($e->getMessage()));
            return $response;
        }
    }

    // Support Messages
    public function supportMessages(Request $request, Response $response): Response
    {
        if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Admins only.</p></div>');
            return $response;
        }
        
        // Get CSRF tokens
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        
        // Get all messages
        $stmt = $this->pdo->query('SELECT id, name, email, subject, message, status, ip_address, user_agent, created_at FROM support_messages ORDER BY created_at DESC');
        $messages = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Stats
        $total = 0;
        $responded = 0;
        $pending = 0;
        foreach ($messages as $msg) {
            $total++;
            // Count as responded if status is 'responded', 'resolved' or 'closed', OR if has_response flag is set
            if ($msg['status'] === 'responded' || $msg['status'] === 'resolved' || $msg['status'] === 'closed' || !empty($msg['has_response'])) {
                $responded++;
            } elseif ($msg['status'] === 'new' || $msg['status'] === 'in_progress') {
                $pending++;
            }
        }
        $responseRate = $total > 0 ? round(($responded / $total) * 100) : 0;

        $widgetStats = [
            'total' => $total,
            'responded' => $responded,
            'pending' => $pending,
            'response_rate' => $responseRate,
        ];

        $vars = [
            'title' => 'Support Messages',
            'description' => 'Support Messages admin page',
            'canonical_url' => $request->getUri()->getPath(),
            'app_name' => 'Know My Patient',
            'company_logo' => 'images/logo.png',
            'company_name' => 'Know My Patient',
            'keywords' => 'admin, dashboard, know my patient',
            'messages' => $messages,
            'widgetStats' => $widgetStats,
            'csrf' => $csrf,
        ];
        $body = $this->twig->getEnvironment()->render('admin/support_messages.html.twig', $vars);
        $response->getBody()->write($body);
        return $response;
    }
    public function updateSupportMessageStatus(Request $request, Response $response): Response
    {
        if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403)->withHeader('Content-Type', 'text/html');
            $response->getBody()->write('Forbidden');
            return $response;
        }
        
        $data = $request->getParsedBody();
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $status = isset($data['status']) ? $data['status'] : null;
        $allowed = ['new', 'in_progress', 'responded', 'resolved', 'closed'];
        
        if ($id > 0 && in_array($status, $allowed, true)) {
            try {
                $stmt = $this->pdo->prepare('UPDATE support_messages SET status = :status, updated_at = NOW() WHERE id = :id');
                $stmt->execute(['status' => $status, 'id' => $id]);
                
                // Set success message in session
                $this->session->set('flash_message', 'Support message status updated successfully!');
                $this->session->set('flash_type', 'success');
            } catch (\PDOException $e) {
                error_log("Error updating support message status: " . $e->getMessage());
                $this->session->set('flash_message', 'Error updating status. Please try again.');
                $this->session->set('flash_type', 'danger');
            }
        } else {
            $this->session->set('flash_message', 'Invalid status or message ID.');
            $this->session->set('flash_type', 'warning');
        }
        
        return $response->withHeader('Location', '/admin/support-messages')->withStatus(302);
    }
    
    public function deleteSupportMessage(Request $request, Response $response): Response
    {
        if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403)->withHeader('Content-Type', 'text/html');
            $response->getBody()->write('Forbidden');
            return $response;
        }
        
        $data = $request->getParsedBody();
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        
        if ($id > 0) {
            try {
                $stmt = $this->pdo->prepare('DELETE FROM support_messages WHERE id = :id');
                $stmt->execute(['id' => $id]);
                
                // Set success message in session
                $this->session->set('flash_message', 'Support message deleted successfully!');
                $this->session->set('flash_type', 'success');
            } catch (\PDOException $e) {
                error_log("Error deleting support message: " . $e->getMessage());
                $this->session->set('flash_message', 'Error deleting message. Please try again.');
                $this->session->set('flash_type', 'danger');
            }
        } else {
            $this->session->set('flash_message', 'Invalid message ID.');
            $this->session->set('flash_type', 'warning');
        }
        
        return $response->withHeader('Location', '/admin/support-messages')->withStatus(302);
    }

    // Testimonials
    public function testimonials(Request $request, Response $response): Response
    {
        if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Admins only.</p></div>');
            return $response;
        }
        
        // Get CSRF tokens
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        
        $stmt = $this->pdo->query('SELECT id, name, role, testimonial FROM testimonials ORDER BY id DESC');
        $testimonials = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $vars = [
            'title' => 'Testimonials',
            'description' => 'Testimonials admin page',
            'canonical_url' => $request->getUri()->getPath(),
            'app_name' => 'Know My Patient',
            'company_logo' => 'images/logo.png',
            'company_name' => 'Know My Patient',
            'keywords' => 'admin, dashboard, know my patient',
            'testimonials' => $testimonials,
            'csrf' => $csrf,
        ];
        $body = $this->twig->getEnvironment()->render('admin/testimonials.html.twig', $vars);
        $response->getBody()->write($body);
        return $response;
    }
    public function deleteTestimonial(Request $request, Response $response): Response
    {
        if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403)->withHeader('Content-Type', 'text/html');
            $response->getBody()->write('Forbidden');
            return $response;
        }
        $data = $request->getParsedBody();
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        if ($id > 0) {
            $stmt = $this->pdo->prepare('DELETE FROM testimonials WHERE id = :id');
            $stmt->execute(['id' => $id]);
        }
        return $response->withHeader('Location', '/admin/testimonials')->withStatus(302);
    }

    // Onboarding Enquiries
    public function onboardingEnquiries(Request $request, Response $response): Response
    {
        $this->logger->debug('onboardingEnquiries called', [
            'user_role' => $this->session->get('user_role'),
            'uri' => $request->getUri()->getPath()
        ]);
        if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Admins only.</p></div>');
            return $response;
        }
        
        // Get CSRF tokens
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        
        $stmt = $this->pdo->query('SELECT id, company_name, company_website, organization_type, organization_size, contact_person, job_title, email, phone, gdpr_consent, marketing_consent, status, assigned_to, created_at FROM onboarding_enquiries ORDER BY created_at DESC');
        $enquiries = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmtUsers = $this->pdo->query('SELECT id, first_name, last_name FROM users ORDER BY first_name ASC');
        $users = $stmtUsers->fetchAll(\PDO::FETCH_ASSOC);

        // Calculate onboarding stats
        $stats = [
            'total_enquiries' => 0,
            'new_enquiries' => 0,
            'contacted' => 0,
            'qualified_leads' => 0,
        ];
        foreach ($enquiries as $enquiry) {
            $stats['total_enquiries']++;
            if ($enquiry['status'] === 'new') {
                $stats['new_enquiries']++;
            }
            if (!empty($enquiry['last_contacted'])) {
                $stats['contacted']++;
            }
            if ($enquiry['status'] === 'completed') {
                $stats['qualified_leads']++;
            }
        }

        $vars = [
            'title' => 'Onboarding Enquiries',
            'description' => 'Onboarding Enquiries admin page',
            'canonical_url' => $request->getUri()->getPath(),
            'app_name' => 'Know My Patient',
            'company_logo' => 'images/logo.png',
            'company_name' => 'Know My Patient',
            'keywords' => 'admin, dashboard, know my patient',
            'enquiries' => $enquiries,
            'users' => $users,
            'stats' => $stats,
            'csrf' => $csrf,
        ];
        $body = $this->twig->getEnvironment()->render('admin/onboarding_enquiries.html.twig', $vars);
        $response->getBody()->write($body);
        return $response;
    }

    // Assign onboarding enquiry to a user (admin action)
    public function assignOnboardingEnquiry(Request $request, Response $response): Response
    {
        $this->logger->debug('assignOnboardingEnquiry called', [
            'user_role' => $this->session->get('user_role'),
            'post_data' => $request->getParsedBody()
        ]);
        if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403)->withHeader('Content-Type', 'text/html');
            $response->getBody()->write('Forbidden');
            return $response;
        }
        $data = $request->getParsedBody();
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $assigned_to = isset($data['assigned_to']) ? (int)$data['assigned_to'] : null;
        if ($id > 0 && $assigned_to) {
            $stmt = $this->pdo->prepare('UPDATE onboarding_enquiries SET assigned_to = :assigned_to WHERE id = :id');
            $stmt->execute(['assigned_to' => $assigned_to, 'id' => $id]);
        }
        return $response->withHeader('Location', '/admin/onboarding-enquiries')->withStatus(302);
    }

    // Update onboarding enquiry status (admin action)
    public function updateOnboardingEnquiryStatus(Request $request, Response $response): Response
    {
        $this->logger->debug('updateOnboardingEnquiryStatus called', [
            'user_role' => $this->session->get('user_role'),
            'post_data' => $request->getParsedBody()
        ]);
        if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403)->withHeader('Content-Type', 'text/html');
            $response->getBody()->write('Forbidden');
            return $response;
        }
        $data = $request->getParsedBody();
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $status = isset($data['status']) ? $data['status'] : null;
        $allowed = ['new', 'in_progress', 'completed', 'rejected', 'archived'];
        if ($id > 0 && in_array($status, $allowed, true)) {
            $stmt = $this->pdo->prepare('UPDATE onboarding_enquiries SET status = :status WHERE id = :id');
            $stmt->execute(['status' => $status, 'id' => $id]);
        }
        return $response->withHeader('Location', '/admin/onboarding-enquiries')->withStatus(302);
    }

    // Delete onboarding enquiry (admin action)
    public function deleteOnboardingEnquiry(Request $request, Response $response): Response
    {
        $this->logger->debug('deleteOnboardingEnquiry called', [
            'user_role' => $this->session->get('user_role'),
            'post_data' => $request->getParsedBody()
        ]);
        if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403)->withHeader('Content-Type', 'text/html');
            $response->getBody()->write('Forbidden');
            return $response;
        }
        $data = $request->getParsedBody();
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        if ($id > 0) {
            $stmt = $this->pdo->prepare('DELETE FROM onboarding_enquiries WHERE id = :id');
            $stmt->execute(['id' => $id]);
        }
        return $response->withHeader('Location', '/admin/onboarding-enquiries')->withStatus(302);
    }

    // Resources
    public function resources(Request $request, Response $response): Response
    {
        if ($this->session->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Admins only.</p></div>');
            return $response;
        }
        $stmt = $this->pdo->query('SELECT id, title, description, file_path, file_type FROM resources ORDER BY id DESC');
        $resources = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $vars = [
            'title' => 'Resources',
            'description' => 'Resources admin page',
            'canonical_url' => $request->getUri()->getPath(),
            'app_name' => 'Know My Patient',
            'company_logo' => 'images/logo.png',
            'company_name' => 'Know My Patient',
            'keywords' => 'admin, dashboard, know my patient',
            'resources' => $resources,
        ];
        $body = $this->twig->getEnvironment()->render('admin/resources.html.twig', $vars);
        $response->getBody()->write($body);
        return $response;
    }
}
