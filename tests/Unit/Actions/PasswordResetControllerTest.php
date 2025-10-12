<?php
declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Application\Actions\PasswordResetController;
use App\Application\Services\SessionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * Unit tests for PasswordResetController
 * 
 * Tests password reset functionality including:
 * - Token generation
 * - Rate limiting
 * - Email sending
 * - Password updates
 */
class PasswordResetControllerTest extends TestCase
{
    private \PDO $pdo;
    private SessionService $sessionService;
    private Twig $twig;
    private LoggerInterface $logger;
    private PasswordResetController $controller;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create in-memory SQLite database for testing
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Create necessary tables
        $this->pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                role VARCHAR(50),
                active INTEGER DEFAULT 1
            )
        ');
        
        $this->pdo->exec('
            CREATE TABLE password_resets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                email VARCHAR(255) NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                used_at DATETIME,
                ip_address VARCHAR(45),
                user_agent TEXT
            )
        ');
        
        $this->pdo->exec('
            CREATE TABLE audit_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                action VARCHAR(100),
                details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        // Mock dependencies
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        
        $this->sessionService = new SessionService();
        $this->twig = $this->createMock(Twig::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->controller = new PasswordResetController(
            $this->pdo,
            $this->sessionService,
            $this->twig,
            $this->logger
        );
    }
    
    protected function tearDown(): void
    {
        $this->pdo = null;
        $_SESSION = [];
        parent::tearDown();
    }
    
    public function testForgotPasswordFormDisplays(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/forgot-password');
        $response = (new ResponseFactory())->createResponse();
        
        $this->twig->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($this->createMock(\Twig\Environment::class));
        
        $result = $this->controller->showForgotPasswordForm($request, $response);
        
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result);
    }
    
    public function testPasswordResetCreatesToken(): void
    {
        // Create test user
        $stmt = $this->pdo->prepare('INSERT INTO users (email, password, first_name, active) VALUES (?, ?, ?, ?)');
        $stmt->execute(['test@example.com', password_hash('password123', PASSWORD_ARGON2ID), 'Test', 1]);
        $userId = (int) $this->pdo->lastInsertId();
        
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/forgot-password');
        $request = $request->withParsedBody(['email' => 'test@example.com']);
        $request = $request->withAttribute('csrf_name', 'test_csrf_name');
        $request = $request->withAttribute('csrf_value', 'test_csrf_value');
        
        $response = (new ResponseFactory())->createResponse();
        
        $result = $this->controller->handleForgotPassword($request, $response);
        
        // Should redirect to login
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/login', $result->getHeaderLine('Location'));
        
        // Verify token was created in database
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM password_resets WHERE email = ?');
        $stmt->execute(['test@example.com']);
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(1, $count);
    }
    
    public function testPasswordResetRateLimiting(): void
    {
        // Create test user
        $stmt = $this->pdo->prepare('INSERT INTO users (email, password, first_name, active) VALUES (?, ?, ?, ?)');
        $stmt->execute(['ratelimit@example.com', password_hash('password123', PASSWORD_ARGON2ID), 'Test', 1]);
        $userId = (int) $this->pdo->lastInsertId();
        
        // Insert 3 recent reset requests (at the limit)
        $stmt = $this->pdo->prepare('
            INSERT INTO password_resets (user_id, email, token, expires_at, created_at) 
            VALUES (?, ?, ?, datetime("now", "+1 hour"), datetime("now"))
        ');
        
        for ($i = 0; $i < 3; $i++) {
            $stmt->execute([$userId, 'ratelimit@example.com', hash('sha256', 'token' . $i)]);
        }
        
        // Try to request another reset
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/forgot-password');
        $request = $request->withParsedBody(['email' => 'ratelimit@example.com']);
        $request = $request->withAttribute('csrf_name', 'test_csrf_name');
        $request = $request->withAttribute('csrf_value', 'test_csrf_value');
        
        $response = (new ResponseFactory())->createResponse();
        
        $result = $this->controller->handleForgotPassword($request, $response);
        
        // Should redirect back to forgot password with warning
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/forgot-password', $result->getHeaderLine('Location'));
        
        // Check flash message
        $flashType = $this->sessionService->get('flash_type');
        $this->assertEquals('warning', $flashType);
    }
    
    public function testPasswordResetWithInvalidEmail(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/forgot-password');
        $request = $request->withParsedBody(['email' => 'nonexistent@example.com']);
        $request = $request->withAttribute('csrf_name', 'test_csrf_name');
        $request = $request->withAttribute('csrf_value', 'test_csrf_value');
        
        $response = (new ResponseFactory())->createResponse();
        
        $result = $this->controller->handleForgotPassword($request, $response);
        
        // Should still redirect to login (don't reveal if email exists)
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/login', $result->getHeaderLine('Location'));
        
        // No token should be created
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM password_resets WHERE email = ?');
        $stmt->execute(['nonexistent@example.com']);
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(0, $count);
    }
    
    public function testPasswordResetCompletesSuccessfully(): void
    {
        // Create test user
        $oldPassword = password_hash('oldpassword', PASSWORD_ARGON2ID);
        $stmt = $this->pdo->prepare('INSERT INTO users (email, password, first_name, active) VALUES (?, ?, ?, ?)');
        $stmt->execute(['reset@example.com', $oldPassword, 'Test', 1]);
        $userId = (int) $this->pdo->lastInsertId();
        
        // Create reset token
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);
        $stmt = $this->pdo->prepare('
            INSERT INTO password_resets (user_id, email, token, expires_at) 
            VALUES (?, ?, ?, datetime("now", "+1 hour"))
        ');
        $stmt->execute([$userId, 'reset@example.com', $hashedToken]);
        
        // Submit new password
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/reset-password');
        $request = $request->withParsedBody([
            'token' => $token,
            'password' => 'NewPassword123!',
            'password_confirm' => 'NewPassword123!',
        ]);
        $request = $request->withAttribute('csrf_name', 'test_csrf_name');
        $request = $request->withAttribute('csrf_value', 'test_csrf_value');
        
        $response = (new ResponseFactory())->createResponse();
        
        $result = $this->controller->handleResetPassword($request, $response);
        
        // Should redirect to login
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/login', $result->getHeaderLine('Location'));
        
        // Verify password was updated
        $stmt = $this->pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $newPasswordHash = $stmt->fetchColumn();
        
        $this->assertNotEquals($oldPassword, $newPasswordHash);
        $this->assertTrue(password_verify('NewPassword123!', $newPasswordHash));
        
        // Verify token was marked as used
        $stmt = $this->pdo->prepare('SELECT used_at FROM password_resets WHERE token = ?');
        $stmt->execute([$hashedToken]);
        $usedAt = $stmt->fetchColumn();
        
        $this->assertNotNull($usedAt);
    }
}
