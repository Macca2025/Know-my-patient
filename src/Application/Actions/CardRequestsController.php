<?php

declare(strict_types=1);

namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Services\SessionService;
use App\Application\Services\IpAddressService;
use Slim\Views\Twig;

class CardRequestsController
{
    private \PDO $pdo;
    private SessionService $sessionService;
    private Twig $twig;

    public function __construct(\PDO $pdo, SessionService $sessionService, Twig $twig)
    {
        $this->pdo = $pdo;
        $this->sessionService = $sessionService;
        $this->twig = $twig;
    }

    /**
     * Patient/User: Request a physical card
     */
    public function requestPhysicalCard(Request $request, Response $response): Response
    {
        // Write to a file to confirm method is called
        file_put_contents('/Applications/MAMP/htdocs/know_my_patient/logs/card_request_debug.txt', 
            date('Y-m-d H:i:s') . " - Method called\n", FILE_APPEND);
        
        try {
            // Get user information from session
            $userId = $this->sessionService->get('user_id');
            $userEmail = $this->sessionService->get('user_email');
            
            // Log the request for debugging
            error_log("Card request initiated for user: " . ($userId ?? 'NULL'));
            file_put_contents('/Applications/MAMP/htdocs/know_my_patient/logs/card_request_debug.txt', 
                "User ID: " . ($userId ?? 'NULL') . "\n", FILE_APPEND);
            
            if (!$userId) {
                error_log("Card request failed: No user ID in session");
                $this->sessionService->set('flash_message', 'You must be logged in to request a card.');
                $this->sessionService->set('flash_type', 'danger');
                return $response->withHeader('Location', '/login')->withStatus(302);
            }
            
            // Check if user already has a pending request
            $stmt = $this->pdo->prepare(
                "SELECT id FROM card_requests 
                 WHERE user_id = :user_id 
                 AND status NOT IN ('delivered', 'cancelled')
                 LIMIT 1"
            );
            $stmt->execute(['user_id' => $userId]);
            $existingRequest = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($existingRequest) {
                error_log("Card request failed: Existing request found for user " . $userId);
                $this->sessionService->set('flash_message', 'You already have a card request in progress. Please wait for it to be processed.');
                $this->sessionService->set('flash_type', 'warning');
                return $response->withHeader('Location', '/dashboard')->withStatus(302);
            }
            
            // Get user's UID from users table
            $stmt = $this->pdo->prepare("SELECT uid FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $userRecord = $stmt->fetch(\PDO::FETCH_ASSOC);
            $userUid = $userRecord['uid'] ?? null;
            
            if (!$userUid) {
                error_log("Card request failed: No UID found for user " . $userId);
                $this->sessionService->set('flash_message', 'Unable to process request. User UID not found.');
                $this->sessionService->set('flash_type', 'danger');
                return $response->withHeader('Location', '/dashboard')->withStatus(302);
            }
            
            // Fetch user's patient profile for delivery details
            $stmt = $this->pdo->prepare(
                "SELECT patient_uid, patient_name, address, postcode, phone_number 
                 FROM patient_profiles 
                 WHERE user_id = :user_id 
                 ORDER BY created_at DESC 
                 LIMIT 1"
            );
            $stmt->execute(['user_id' => $userId]);
            $patientProfile = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$patientProfile) {
                error_log("Card request failed: No patient profile found for user " . $userId);
                $this->sessionService->set('flash_message', 'Please complete your patient profile before requesting a physical card.');
                $this->sessionService->set('flash_type', 'danger');
                return $response->withHeader('Location', '/add-patient')->withStatus(302);
            }
            
            error_log("Patient profile found: " . $patientProfile['patient_uid']);
            
            // Validate required fields
            if (empty($patientProfile['address']) || empty($patientProfile['postcode'])) {
                error_log("Card request failed: Missing address or postcode");
                $this->sessionService->set('flash_message', 'Please add your delivery address and postcode to your patient profile before requesting a card.');
                $this->sessionService->set('flash_type', 'danger');
                return $response->withHeader('Location', '/add-patient')->withStatus(302);
            }
            
            // Insert card request
            $stmt = $this->pdo->prepare(
                "INSERT INTO card_requests 
                 (user_id, patient_uid, card_type, delivery_address, delivery_postcode, contact_phone, contact_email, status) 
                 VALUES 
                 (:user_id, :patient_uid, :card_type, :delivery_address, :delivery_postcode, :contact_phone, :contact_email, :status)"
            );
            
            $params = [
                'user_id' => $userId,
                'patient_uid' => $patientProfile['patient_uid'],
                'card_type' => 'standard',
                'delivery_address' => $patientProfile['address'],
                'delivery_postcode' => $patientProfile['postcode'],
                'contact_phone' => $patientProfile['phone_number'] ?? null,
                'contact_email' => $userEmail ?? null,
                'status' => 'pending'
            ];
            
            error_log("Attempting to insert card request with params: " . json_encode($params));
            
            $success = $stmt->execute($params);
            
            if ($success) {
                error_log("Card request inserted successfully for user " . $userId);
                // Log the action
                $this->logCardRequestAction($userId, 'card_request_created', 'Patient requested physical card');
                
                $this->sessionService->set('flash_message', 'Your physical card request has been submitted successfully! We will process it within 2-3 business days.');
                $this->sessionService->set('flash_type', 'success');
            } else {
                error_log("Card request insert failed for user " . $userId);
                $errorInfo = $stmt->errorInfo();
                error_log("PDO Error: " . json_encode($errorInfo));
                $this->sessionService->set('flash_message', 'Failed to submit card request. Please try again later.');
                $this->sessionService->set('flash_type', 'danger');
            }
            
        } catch (\Exception $e) {
            error_log("Card request exception: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sessionService->set('flash_message', 'An error occurred: ' . $e->getMessage());
            $this->sessionService->set('flash_type', 'danger');
        }
        
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    /**
     * Get pending card request for a user
     */
    public function getPendingCardRequest(int $userId): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT cr.*, pp.patient_name 
                 FROM card_requests cr
                 LEFT JOIN patient_profiles pp ON cr.patient_uid = pp.patient_uid
                 WHERE cr.user_id = :user_id 
                 AND cr.status NOT IN ('delivered', 'cancelled')
                 ORDER BY cr.request_date DESC
                 LIMIT 1"
            );
            $stmt->execute(['user_id' => $userId]);
            $pendingCardRequest = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($pendingCardRequest) {
                $pendingCardRequest['patient_name'] = $pendingCardRequest['patient_name'] ?? 'Unknown';
                return $pendingCardRequest;
            }
        } catch (\Exception $e) {
            error_log("Error fetching pending card request: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Admin: View all card requests with filters
     */
    public function adminCardRequests(Request $request, Response $response): Response
    {
        if ($this->sessionService->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Admins only.</p></div>');
            return $response;
        }

        // Generate CSRF tokens
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];

        // Get filters from query params
        $queryParams = $request->getQueryParams();
        $search = isset($queryParams['search']) ? trim($queryParams['search']) : '';
        $status = isset($queryParams['status']) ? trim($queryParams['status']) : '';
        $fromDate = isset($queryParams['from_date']) ? trim($queryParams['from_date']) : '';
        $toDate = isset($queryParams['to_date']) ? trim($queryParams['to_date']) : '';
        $sortBy = isset($queryParams['sort_by']) ? trim($queryParams['sort_by']) : 'request_date';
        $order = isset($queryParams['order']) && strtolower($queryParams['order']) === 'asc' ? 'ASC' : 'DESC';

        // Build SQL
        $sql = 'SELECT * FROM card_requests WHERE 1=1';
        $params = [];
        
        if ($search !== '') {
            $sql .= ' AND (user_id LIKE :search OR patient_uid LIKE :search OR contact_email LIKE :search OR tracking_number LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        
        if ($status !== '' && $status !== 'all') {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }
        
        if ($fromDate !== '') {
            // Convert dd/mm/yyyy to yyyy-mm-dd
            $fromParts = explode('/', $fromDate);
            if (count($fromParts) === 3) {
                $fromDateSql = $fromParts[2] . '-' . $fromParts[1] . '-' . $fromParts[0];
                $sql .= ' AND request_date >= :from_date';
                $params['from_date'] = $fromDateSql . ' 00:00:00';
            }
        }
        
        if ($toDate !== '') {
            $toParts = explode('/', $toDate);
            if (count($toParts) === 3) {
                $toDateSql = $toParts[2] . '-' . $toParts[1] . '-' . $toParts[0];
                $sql .= ' AND request_date <= :to_date';
                $params['to_date'] = $toDateSql . ' 23:59:59';
            }
        }
        
        $allowedSort = ['request_date', 'status', 'card_type'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'request_date';
        }
        $sql .= " ORDER BY $sortBy $order";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $cardRequests = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Calculate stats
        $stats = [
            'total_requests' => 0,
            'pending' => 0,
            'printing' => 0,
            'posted' => 0,
        ];
        
        $allStmt = $this->pdo->query('SELECT status FROM card_requests');
        foreach ($allStmt->fetchAll(\PDO::FETCH_ASSOC) as $req) {
            $stats['total_requests']++;
            if ($req['status'] === 'pending') $stats['pending']++;
            if ($req['status'] === 'printing') $stats['printing']++;
            if ($req['status'] === 'posted') $stats['posted']++;
        }

        $vars = [
            'title' => 'Card Requests',
            'description' => 'Card Requests admin page',
            'canonical_url' => $request->getUri()->getPath(),
            'app_name' => 'Know My Patient',
            'company_logo' => 'images/logo.png',
            'company_name' => 'Know My Patient',
            'keywords' => 'admin, dashboard, know my patient',
            'stats' => $stats,
            'cardRequests' => $cardRequests,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'sort_by' => $sortBy,
                'order' => $order,
            ],
            'csrf' => $csrf,
        ];
        
        $body = $this->twig->getEnvironment()->render('admin/card_requests.html.twig', $vars);
        $response->getBody()->write($body);
        return $response;
    }

    /**
     * Admin: Update card request status
     */
    public function updateCardRequestStatus(Request $request, Response $response): Response
    {
        if ($this->sessionService->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('Forbidden');
            return $response;
        }

        try {
            $data = $request->getParsedBody();
            $requestId = $data['request_id'] ?? null;
            $status = $data['status'] ?? null;
            $trackingNumber = $data['tracking_number'] ?? null;

            if (!$requestId || !$status) {
                $this->sessionService->set('flash_message', 'Invalid request data.');
                $this->sessionService->set('flash_type', 'danger');
                return $response->withHeader('Location', '/admin/card-requests')->withStatus(302);
            }

            // Allowed statuses
            $allowedStatuses = ['pending', 'printing', 'posted', 'delivered', 'cancelled'];
            if (!in_array($status, $allowedStatuses, true)) {
                $this->sessionService->set('flash_message', 'Invalid status value.');
                $this->sessionService->set('flash_type', 'danger');
                return $response->withHeader('Location', '/admin/card-requests')->withStatus(302);
            }

            // Update card request
            $sql = "UPDATE card_requests SET status = :status, updated_at = NOW()";
            $params = ['status' => $status, 'request_id' => $requestId];

            // Add tracking number if provided and status is posted or delivered
            if (in_array($status, ['posted', 'delivered']) && !empty($trackingNumber)) {
                $sql .= ", tracking_number = :tracking_number";
                $params['tracking_number'] = $trackingNumber;
            }

            $sql .= " WHERE id = :request_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            // Log the action
            $adminUserId = $this->sessionService->get('user_id');
            $this->logCardRequestAction(
                $adminUserId,
                'card_request_status_updated',
                "Card request #{$requestId} status updated to {$status}"
            );

            $this->sessionService->set('flash_message', 'Card request status updated successfully.');
            $this->sessionService->set('flash_type', 'success');

        } catch (\Exception $e) {
            error_log("Error updating card request status: " . $e->getMessage());
            $this->sessionService->set('flash_message', 'Failed to update card request status.');
            $this->sessionService->set('flash_type', 'danger');
        }

        return $response->withHeader('Location', '/admin/card-requests')->withStatus(302);
    }

    /**
     * Admin: Delete card request
     */
    public function deleteCardRequest(Request $request, Response $response): Response
    {
        if ($this->sessionService->get('user_role') !== 'admin') {
            $response = $response->withStatus(403);
            $response->getBody()->write('Forbidden');
            return $response;
        }

        try {
            $data = $request->getParsedBody();
            $requestId = $data['request_id'] ?? null;

            if (!$requestId) {
                $this->sessionService->set('flash_message', 'Invalid request ID.');
                $this->sessionService->set('flash_type', 'danger');
                return $response->withHeader('Location', '/admin/card-requests')->withStatus(302);
            }

            // Delete card request
            $stmt = $this->pdo->prepare("DELETE FROM card_requests WHERE id = :request_id");
            $stmt->execute(['request_id' => $requestId]);

            // Log the action
            $adminUserId = $this->sessionService->get('user_id');
            $this->logCardRequestAction(
                $adminUserId,
                'card_request_deleted',
                "Card request #{$requestId} deleted"
            );

            $this->sessionService->set('flash_message', 'Card request deleted successfully.');
            $this->sessionService->set('flash_type', 'success');

        } catch (\Exception $e) {
            error_log("Error deleting card request: " . $e->getMessage());
            $this->sessionService->set('flash_message', 'Failed to delete card request.');
            $this->sessionService->set('flash_type', 'danger');
        }

        return $response->withHeader('Location', '/admin/card-requests')->withStatus(302);
    }

    /**
     * Log card request actions to audit log
     */
    private function logCardRequestAction(string|int $userId, string $action, string $details): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO audit_log (user_id, action, details, ip_address) 
                 VALUES (:user_id, :action, :details, :ip_address)"
            );
            $stmt->execute([
                'user_id' => (string)$userId,
                'action' => $action,
                'details' => $details,
                'ip_address' => IpAddressService::getClientIp()
            ]);
        } catch (\Exception $e) {
            error_log("Failed to log card request action: " . $e->getMessage());
            // Silently fail - logging shouldn't break the request
        }
    }
}
