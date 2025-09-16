<?php
namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Respect\Validation\Validator as v;

class AuthController
{
    private Twig $twig;
    private \PDO $pdo;
    private LoggerInterface $logger;

    public function __construct(Twig $twig, \PDO $pdo, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->pdo = $pdo;
        $this->logger = $logger;
    }


    public function login(Request $request, Response $response): Response
    {
        $error = null;
        $form = [];
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();
            $form = $data;
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $emailValidator = v::notEmpty()->email();
            $passwordValidator = v::notEmpty()->length(8, null);
            if (!$emailValidator->validate($email)) {
                $error = 'A valid email is required.';
                $this->logger->warning('Login attempt with invalid email', ['email' => $email]);
            } elseif (!$passwordValidator->validate($password)) {
                $error = 'Password is required and must be at least 8 characters.';
                $this->logger->warning('Login attempt with invalid password', ['email' => $email]);
            } else {
                $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ? AND active = 1 LIMIT 1');
                $stmt->execute([$email]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($user && password_verify($password, $user['password'])) {
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['user_role'] = $user['role'] ?? null;
                    $this->logger->info('User login successful', ['user_id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]);
                    // Redirect to dashboard
                    return $response
                        ->withHeader('Location', '/dashboard')
                        ->withStatus(302);
                } else {
                    $error = 'Invalid email or password.';
                    $this->logger->warning('Failed login attempt', ['email' => $email]);
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
        $registered = $request->getQueryParams()['registered'] ?? null;
        $body = $this->twig->getEnvironment()->render('login.html.twig', [
            'error' => $error,
            'form' => $form,
            'csrf' => $csrf,
            'registered' => $registered,
            'current_route' => 'login'
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
                    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
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
            'csrf' => $csrf,
            'current_route' => 'register'
        ]);
        $response->getBody()->write($body);
        return $response;
    }


    public function logout(Request $request, Response $response): Response
    {
        // Destroy session and redirect to login
        $this->logger->info('User logged out', ['user_id' => $_SESSION['user_id'] ?? null, 'email' => $_SESSION['user_email'] ?? null]);
        session_unset();
        session_destroy();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }


    public function forgotPassword(Request $request, Response $response): Response
    {
        $body = $this->twig->getEnvironment()->render('forgot_password.html.twig');
        $response->getBody()->write($body);
        return $response;
    }
}
