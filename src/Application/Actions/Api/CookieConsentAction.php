<?php

declare(strict_types=1);

namespace App\Application\Actions\Api;

use App\Application\Actions\Action;
use App\Application\Services\CookieConsentService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

/**
 * Cookie Consent Action
 * 
 * Handles API requests for cookie consent management
 * Endpoint: POST /api/cookie-consent
 * 
 * @package App\Application\Actions\Api
 */
class CookieConsentAction extends Action
{
    private CookieConsentService $cookieConsentService;
    
    /**
     * Constructor
     * 
     * @param LoggerInterface $logger
     * @param CookieConsentService $cookieConsentService
     */
    public function __construct(
        LoggerInterface $logger,
        CookieConsentService $cookieConsentService
    ) {
        parent::__construct($logger);
        $this->cookieConsentService = $cookieConsentService;
    }
    
    /**
     * Handle the cookie consent request
     * 
     * @return Response
     */
    protected function action(): Response
    {
        try {
            $data = $this->getFormData();
            
            // Validate required fields
            if (!isset($data['consent_type'])) {
                return $this->respondWithError('Consent type is required', 400);
            }
            
            $consentType = (string) $data['consent_type'];
            
            // Validate consent type
            if (!$this->cookieConsentService->isValidConsentType($consentType)) {
                return $this->respondWithError('Invalid consent type', 400);
            }
            
            // Get user ID if logged in
            $userId = $_SESSION['user_id'] ?? null;
            
            // Get client IP address
            $ipAddress = $this->request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
            
            // Log consent to database (optional)
            if ($userId) {
                $this->cookieConsentService->logConsentToDatabase(
                    (int) $userId,
                    $consentType,
                    $ipAddress
                );
            }
            
            // Log the consent
            $this->logger->info('Cookie consent recorded', [
                'user_id' => $userId,
                'consent_type' => $consentType,
                'ip_address' => $ipAddress,
                'user_agent' => $this->request->getServerParams()['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            // Return success response
            return $this->respondWithData([
                'success' => true,
                'message' => 'Cookie consent recorded successfully',
                'consent_type' => $consentType
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error recording cookie consent', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->respondWithError('Failed to record consent', 500);
        }
    }
    
    /**
     * Respond with error
     * 
     * @param string $message
     * @param int $statusCode
     * @return Response
     */
    private function respondWithError(string $message, int $statusCode = 400): Response
    {
        $payload = json_encode([
            'success' => false,
            'error' => $message
        ]);
        
        $this->response->getBody()->write($payload);
        
        return $this->response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
