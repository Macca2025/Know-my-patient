<?php
namespace Application\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use App\Application\Services\EmailService;
use App\Domain\User\UserRepository;

class NHSVerifyController
{
    private Twig $view;
    private EmailService $emailService;
    private UserRepository $userRepository;

    public function __construct(Twig $view, EmailService $emailService, UserRepository $userRepository)
    {
        $this->view = $view;
        $this->emailService = $emailService;
        $this->userRepository = $userRepository;
    }

    // Show verification page if NHS email and not verified
    /**
     * @param array<string, mixed> $args
     */
    public function showVerifyPage(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $user = is_array($user) ? $user : null;
        $email = $user['email'] ?? null;
        $nhsVerified = $user['nhs_verified'] ?? null;
        if (!$user || !$email || !$this->isNhsEmail($email) || $nhsVerified) {
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }
        $queryParams = $request->getQueryParams();
        $resent = is_array($queryParams) && isset($queryParams['resent']) ? $queryParams['resent'] : false;
        return $this->view->render($response, 'nhsverify.html.twig', [
            'user' => $user,
            'resent' => $resent
        ]);
    }

    // Handle verification request (send token)
    /**
     * @param array<string, mixed> $args
     */
    public function sendVerification(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $user = is_array($user) ? $user : null;
        $email = $user['email'] ?? null;
        $nhsVerified = $user['nhs_verified'] ?? null;
        $userId = $user['id'] ?? null;
        if (!$user || !$email || !$this->isNhsEmail($email) || $nhsVerified || !$userId) {
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }
        $token = bin2hex(random_bytes(16));
        $this->userRepository->saveNhsVerificationToken($userId, $token);
        $this->emailService->sendNhsVerificationEmail($email, $token);
        return $response->withHeader('Location', '/nhsverify?resent=1')->withStatus(302);
    }

    private function isNhsEmail(?string $email): bool
    {
        if ($email === null) {
            return false;
        }
        return (bool)preg_match('/@nhs\.(net|uk)$/i', $email);
    }
}
