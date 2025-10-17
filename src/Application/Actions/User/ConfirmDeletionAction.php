<?php

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Application\Services\SessionService;
use App\Application\Services\IpAddressService;
use Psr\Log\LoggerInterface;

class ConfirmDeletionAction
{
    private Twig $twig;
    private \PDO $pdo;
    private SessionService $sessionService;
    private LoggerInterface $logger;

    public function __construct(Twig $twig, \PDO $pdo, SessionService $sessionService, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->pdo = $pdo;
        $this->sessionService = $sessionService;
        $this->logger = $logger;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $error = null;
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();
            $confirmText = strtoupper(trim($data['confirmText'] ?? ''));
            if ($confirmText === 'I CONFIRM MY ACCOUNT FOR DELETION') {
                $userId = $this->sessionService->get('user_id');
                if ($userId) {
                    // Audit log before deletion
                    $this->logger->info('User account deleted', [
                        'user_id' => $userId,
                        'timestamp' => date('c'),
                        'ip' => IpAddressService::getClientIp()
                    ]);
                    // Delete user from DB
                    $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
                    $stmt->execute([$userId]);
                }
                // Destroy session
                $this->sessionService->clear();
                $this->sessionService->destroy();
                // Redirect to login with deleted message using absolute URL
                $uri = $request->getUri();
                $scheme = $uri->getScheme();
                $host = $uri->getHost();
                $port = $uri->getPort();
                $baseUrl = $scheme . '://' . $host;
                if (($scheme === 'http' && $port && $port !== 80) || ($scheme === 'https' && $port && $port !== 443)) {
                    $baseUrl .= ':' . $port;
                }
                $location = $baseUrl . '/login?deleted=1';
                $this->logger->info('ConfirmDeletionAction redirect', ['from' => (string)$uri, 'to' => $location]);
                return $response->withHeader('Location', $location)->withStatus(302);
            } else {
                $error = 'You must type the exact phrase to confirm deletion.';
            }
        }
        $body = $this->twig->getEnvironment()->render('confirm_deletion.html.twig', [
            'error' => $error
        ]);
        $response->getBody()->write($body);
        return $response;
    }
}
