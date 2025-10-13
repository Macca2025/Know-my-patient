<?php

namespace App\Application\Actions\Healthcare;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Application\Services\SessionService;
use App\Application\Services\IpAddressService;
use PDO;

class PatientPassportAction
{
    private Twig $twig;
    private SessionService $sessionService;
    private PDO $pdo;

    public function __construct(Twig $twig, SessionService $sessionService, PDO $pdo)
    {
        $this->twig = $twig;
        $this->sessionService = $sessionService;
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $role = $this->sessionService->get('user_role');
        if ($role !== 'nhs_user') {
            $response = $response->withStatus(403);
            $response->getBody()->write('<div class="container py-5"><h1>Forbidden</h1><p>Only NHS users can access this page.</p></div>');
            return $response;
        }

        $method = $request->getMethod();
        $patientData = null;
        $accessHistory = [];
        $message = null;
        $messageType = 'info';
        $uid = null;

        // Handle POST request (form submission)
        if ($method === 'POST') {
            $parsedBody = $request->getParsedBody();
            $uid = $parsedBody['uid'] ?? null;

            if ($uid) {
                // Fetch patient data
                $stmt = $this->pdo->prepare("SELECT * FROM patient_profiles WHERE uid = :uid LIMIT 1");
                $stmt->execute(['uid' => $uid]);
                $patientData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($patientData) {
                    $nhsUserId = $this->sessionService->get('user_id');
                    $patientId = $patientData['user_id'];
                    $ipAddress = IpAddressService::getClientIp();

                    // Log the access
                    $stmt = $this->pdo->prepare("
                        INSERT INTO audit_log (user_id, target_user_id, activity_type, description, ip_address, timestamp)
                        VALUES (:user_id, :target_user_id, :activity_type, :description, :ip_address, NOW())
                    ");
                    $stmt->execute([
                        'user_id' => $nhsUserId,
                        'target_user_id' => $patientId,
                        'activity_type' => 'PATIENT_RECORD_ACCESSED',
                        'description' => json_encode([
                            'patient_name' => ($patientData['firstName'] ?? '') . ' ' . ($patientData['lastName'] ?? ''),
                            'nhs_number' => $patientData['nhs_number'] ?? '',
                            'access_method' => $parsedBody['access_method'] ?? 'unknown'
                        ]),
                        'ip_address' => $ipAddress
                    ]);

                    // Fetch access history for this patient
                    $stmt = $this->pdo->prepare("
                        SELECT 
                            al.timestamp,
                            al.ip_address,
                            u.name as accessor_name,
                            u.role as accessor_role,
                            u.email as accessor_email
                        FROM audit_log al
                        LEFT JOIN users u ON al.user_id = u.id
                        WHERE al.target_user_id = :patient_id 
                        AND al.activity_type = 'PATIENT_RECORD_ACCESSED'
                        ORDER BY al.timestamp DESC
                        LIMIT 20
                    ");
                    $stmt->execute(['patient_id' => $patientId]);
                    $accessHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $message = 'Patient record loaded successfully.';
                    $messageType = 'success';
                } else {
                    $message = 'Patient not found with UID: ' . htmlspecialchars($uid);
                    $messageType = 'warning';
                }
            } else {
                $message = 'Please enter a patient UID.';
                $messageType = 'warning';
            }
        }

        // Render template
        $body = $this->twig->getEnvironment()->render('healthcare_pages/patient_passport.html.twig', [
            'patientData' => $patientData,
            'accessHistory' => $accessHistory,
            'message' => $message,
            'messageType' => $messageType,
            'uid' => $uid,
            'user_id' => $this->sessionService->get('user_id') // For conditional display
        ]);
        $response->getBody()->write($body);
        return $response;
    }
}
