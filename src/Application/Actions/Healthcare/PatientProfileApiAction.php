<?php
namespace App\Application\Actions\Healthcare;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Response as SlimResponse;
use App\Domain\User\PatientProfileRepository;
use App\Domain\User\AuditLogRepository;
use App\Application\Services\SessionService;

class PatientProfileApiAction
{
    private $logger;
    private $profileRepository;
    private $auditLogRepository;
    private $sessionService;

    public function __construct(
        LoggerInterface $logger,
        PatientProfileRepository $profileRepository,
        AuditLogRepository $auditLogRepository,
        SessionService $sessionService
    ) {
        $this->logger = $logger;
        $this->profileRepository = $profileRepository;
        $this->auditLogRepository = $auditLogRepository;
        $this->sessionService = $sessionService;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $uid = $args['uid'] ?? null;
        $user = $this->sessionService->get('user');
        $userId = $user['id'] ?? 'guest';
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $desc = '';
        $activityType = 'patient_profile_lookup';
        $targetUserId = null;
        $status = 'success';
        $profile = null;
        if (!$uid) {
            $desc = 'No UID provided';
            $status = 'invalid';
        } else {
            $profile = $this->profileRepository->findByUid($uid);
            if (!$profile) {
                $desc = 'Profile not found for UID: ' . $uid;
                $status = 'not_found';
            } else {
                $desc = 'Profile lookup for UID: ' . $uid;
                $targetUserId = $profile['user_id'] ?? null;
            }
        }
        $this->auditLogRepository->log([
            'activity_type' => $activityType,
            'user_id' => $userId,
            'target_user_id' => $targetUserId,
            'description' => $desc,
            'ip_address' => $ip,
        ]);
        if (!$uid) {
            $data = ['error' => 'Invalid UID'];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        if (!$profile) {
            $data = ['error' => 'Profile not found'];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $response->getBody()->write(json_encode($profile));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
