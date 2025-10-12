<?php
namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Respect\Validation\Validator as v;

use App\Application\Services\SessionService;

class AuthController
{
    private Twig $twig;
    private \PDO $pdo;
    private LoggerInterface $logger;
    private SessionService $sessionService;

    public function __construct(Twig $twig, \PDO $pdo, LoggerInterface $logger, SessionService $sessionService)
    {
        $this->twig = $twig;
        $this->pdo = $pdo;
        $this->logger = $logger;
        $this->sessionService = $sessionService;
    }


    public function login(Request $request, Response $response): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    $error = null;
    $form = [];
    $suspended = false;

        // 1. Check for remember me cookie if not logged in
        if (!isset($_SESSION['user_id']) && isset($_COOKIE['rememberme'])) {
            $cookie = $_COOKIE['rememberme'];
            if (strpos($cookie, ':') !== false) {
                list($userId, $token) = explode(':', $cookie, 2);
                $stmt = $this->pdo->prepare('SELECT id, email, first_name, last_name, role, remember_token FROM users WHERE id = ? AND active = 1 LIMIT 1');
                $stmt->execute([$userId]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($user && $user['remember_token'] && password_verify($token, $user['remember_token'])) {
                    $this->sessionService->set('user_id', $user['id']);
                    $this->sessionService->set('user_email', $user['email']);
                    $this->sessionService->set('user_name', $user['first_name'] . ' ' . $user['last_name']);
                    $this->sessionService->set('user_role', $user['role'] ?? null);
                    $_SESSION['_last_activity'] = time(); // Initialize session timeout tracking
                    $this->logger->info('User auto-logged in via remember me', ['user_id' => $user['id'], 'email' => $user['email']]);
                    return $response->withHeader('Location', '/dashboard')->withStatus(302);
                }
            }
        }

        // 2. Handle POST login
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();
            $form = $data;
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $remember = !empty($data['remember']);
            $emailValidator = v::notEmpty()->email();
            $passwordValidator = v::notEmpty()->length(8, null);
            if (!$emailValidator->validate($email)) {
                $error = 'A valid email is required.';
                $this->logger->warning('Login attempt with invalid email', ['email' => $email]);
            } elseif (!$passwordValidator->validate($password)) {
                $error = 'Password is required and must be at least 8 characters.';
                $this->logger->warning('Login attempt with invalid password', ['email' => $email]);
            } else {
                // Check for suspended user first
                $stmtSuspended = $this->pdo->prepare('SELECT id, email, first_name, last_name, role, active FROM users WHERE email = ? AND active = 0 LIMIT 1');
                $stmtSuspended->execute([$email]);
                $suspendedUser = $stmtSuspended->fetch(\PDO::FETCH_ASSOC);
                if ($suspendedUser) {
                    $suspended = true;
                    $this->logger->warning('Suspended user login attempt', ['email' => $email]);
                } else {
                    $stmt = $this->pdo->prepare('SELECT id, email, password, first_name, last_name, role, active, remember_token FROM users WHERE email = ? AND active = 1 LIMIT 1');
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($user && password_verify($password, $user['password'])) {
                        // Set session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['user_role'] = $user['role'] ?? null;
                        $_SESSION['_last_activity'] = time(); // Initialize session timeout tracking
                        
                        // Update last_login
                        $updateLogin = $this->pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
                        $updateLogin->execute([$user['id']]);
                        $this->logger->info('User login successful', ['user_id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]);
                        
                        // Handle remember me
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            $hashedToken = password_hash($token, PASSWORD_ARGON2ID);
                            $update = $this->pdo->prepare('UPDATE users SET remember_token = ? WHERE id = ?');
                            $update->execute([$hashedToken, $user['id']]);
                            setcookie('rememberme', $user['id'] . ':' . $token, [
                                'expires' => time() + (86400 * 30),
                                'path' => '/',
                                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                                'httponly' => true,
                                'samesite' => 'Lax',
                            ]);
                        } else {
                            setcookie('rememberme', '', time() - 3600, '/');
                            $update = $this->pdo->prepare('UPDATE users SET remember_token = NULL WHERE id = ?');
                            $update->execute([$user['id']]);
                        }
                        return $response->withHeader('Location', '/dashboard')->withStatus(302);
                    } else {
                        $error = 'Invalid email or password.';
                        $this->logger->warning('Failed login attempt', ['email' => $email]);
                    }
                }
            }
        }

        // 3. Render login page
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        $queryParams = $request->getQueryParams();
        $registered = $queryParams['registered'] ?? null;
        $deleted = $queryParams['deleted'] ?? null;
        
        // Get flash messages and clear them after reading
        $flashMessage = $this->sessionService->get('flash_message');
        $flashType = $this->sessionService->get('flash_type');
        if ($flashMessage) {
            $this->sessionService->remove('flash_message');
            $this->sessionService->remove('flash_type');
        }
        
        $body = $this->twig->getEnvironment()->render('login.html.twig', [
            'error' => $error,
            'form' => $form,
            'csrf' => $csrf,
            'registered' => $registered,
            'deleted' => $deleted,
            'suspended' => $suspended,
            'session' => array_merge($this->sessionService->all(), [
                'flash_message' => $flashMessage,
                'flash_type' => $flashType,
            ]),
            'title' => 'Login',
            'description' => 'Login to Know My Patient',
            'canonical_url' => '/login',
            'app_name' => 'Know My Patient'
        ]);
        $response->getBody()->write($body);
        return $response;
    }


    public function register(Request $request, Response $response): Response
    {
        $errors = [];
        $success = false;
        $data = $request->getParsedBody() ?: [];

        if ($request->getMethod() === 'POST') {
            $registerTypeValidator = v::notEmpty()->in(['nhs', 'family', 'patient']);
            $firstNameValidator = v::notEmpty()->alpha();
            $lastNameValidator = v::notEmpty()->alpha();
            $emailValidator = v::notEmpty()->email();
            $passwordValidator = v::notEmpty()->length(8, null);

            if (!$registerTypeValidator->validate($data['register_type'] ?? null)) {
                $errors['register_type'] = 'Please select a valid registration type.';
            }
            if (!$firstNameValidator->validate($data['firstName'] ?? null)) {
                $errors['firstName'] = 'First name is required and must be alphabetic.';
            }
            if (!$lastNameValidator->validate($data['lastName'] ?? null)) {
                $errors['lastName'] = 'Last name is required and must be alphabetic.';
            }
            if (!$emailValidator->validate($data['email'] ?? null)) {
                $errors['email'] = 'A valid email is required.';
            }
            if (!$passwordValidator->validate($data['password'] ?? null)) {
                $errors['password'] = 'Password must be at least 8 characters.';
            }
            // Check if email already exists
            if (empty($errors['email'])) {
                $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
                $stmt->execute([$data['email']]);
                if ($stmt->fetchColumn() > 0) {
                    $errors['email'] = 'Email is already registered.';
                }
            }
            if (empty($errors)) {
                try {
                    $stmt = $this->pdo->prepare('INSERT INTO users (uid, first_name, last_name, email, password, role, created_at, updated_at, active) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), 1)');
                    $uid = bin2hex(random_bytes(16));
                    $hashedPassword = password_hash($data['password'], PASSWORD_ARGON2ID);
                    $role = $data['register_type'] === 'nhs' ? 'nhs_user' : ($data['register_type'] === 'family' ? 'family' : 'patient');
                    $stmt->execute([
                        $uid,
                        $data['firstName'],
                        $data['lastName'],
                        $data['email'],
                        $hashedPassword,
                        $role
                    ]);
                    // Redirect to login with success alert
                    return $response->withHeader('Location', '/login?registered=1')->withStatus(302);
                } catch (\Throwable $e) {
                    $errors['general'] = 'Could not register user. Please try again later.';
                }
            }
        }

        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        $body = $this->twig->getEnvironment()->render('register.html.twig', [
            'form' => $data,
            'errors' => $errors,
            'success' => $success,
            'csrf' => $csrf
        ]);
        $response->getBody()->write($body);
        return $response;
    }


    public function logout(Request $request, Response $response): Response
    {
        // Destroy session, clear remember me cookie and DB token, then redirect to login
        $this->logger->info('User logged out', ['user_id' => $_SESSION['user_id'] ?? null, 'email' => $_SESSION['user_email'] ?? null]);
        if ($this->sessionService->get('user_id')) {
            $update = $this->pdo->prepare('UPDATE users SET remember_token = NULL WHERE id = ?');
            $update->execute([$this->sessionService->get('user_id')]);
        }
        setcookie('rememberme', '', time() - 3600, '/');
        $this->sessionService->clear();
        $this->sessionService->destroy();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }


    public function forgotPassword(Request $request, Response $response): Response
    {
        $body = $this->twig->getEnvironment()->render('forgot_password.html.twig');
        $response->getBody()->write($body);
        return $response;
    }
}
