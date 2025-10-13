<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Application\Services\ErrorMessageService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ErrorMessageService
 *
 * Tests error message sanitization and security
 */
class ErrorMessageServiceTest extends TestCase
{
    private ErrorMessageService $productionService;
    private ErrorMessageService $developmentService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock logger
        /** @var LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);

        // Create services for both environments
        $this->productionService = new ErrorMessageService($logger, true);
        $this->developmentService = new ErrorMessageService($logger, false);
    }

    public function testProductionHidesDatabaseErrors(): void
    {
        $exception = new \PDOException("SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax");

        $result = $this->productionService->getUserMessage($exception);

        $this->assertStringNotContainsString('SQL', $result);
        $this->assertStringNotContainsString('1064', $result);
        $this->assertStringContainsString('error occurred', strtolower($result));
    }

    public function testDevelopmentShowsDatabaseErrors(): void
    {
        $exception = new \PDOException("SQLSTATE[42000]: Syntax error in query");

        $result = $this->developmentService->getUserMessage($exception);

        $this->assertStringContainsString('SQLSTATE', $result);
    }

    public function testProductionHidesFilePaths(): void
    {
        $exception = new \Exception("Error in /var/www/html/application/Controllers/UserController.php on line 42");

        $result = $this->productionService->getUserMessage($exception);

        $this->assertStringNotContainsString('/var/www', $result);
        $this->assertStringNotContainsString('UserController.php', $result);
    }

    public function testProductionHidesStackTraces(): void
    {
        $exception = new \Exception("Database error");

        $result = $this->productionService->getUserMessage($exception);

        // Production should not include stack trace
        $this->assertStringNotContainsString('#0', $result);
        $this->assertStringNotContainsString('#1', $result);
    }

    public function testSafeMessagesArePreserved(): void
    {
        $exception = new \Exception("Invalid email address provided");

        // In production, returns generic message for security
        $productionResult = $this->productionService->getUserMessage($exception);
        $this->assertStringContainsString('error occurred', strtolower($productionResult));

        // In development, shows actual message
        $developmentResult = $this->developmentService->getUserMessage($exception);
        $this->assertStringContainsString('Invalid email address', $developmentResult);
    }

    public function testValidationErrorsArePreserved(): void
    {
        $exception = new \InvalidArgumentException("Password must be at least 8 characters long");

        // In production, can use custom message
        $result = $this->productionService->getUserMessage($exception, 'validation', 'Invalid password format');
        $this->assertStringContainsString('Invalid password', $result);

        // In development, shows actual message
        $devResult = $this->developmentService->getUserMessage($exception);
        $this->assertStringContainsString('Password must be at least 8 characters', $devResult);
    }

    public function testJsonErrorDataForProduction(): void
    {
        $exception = new \PDOException("SQLSTATE[42000]: Database connection failed");

        $result = $this->productionService->getJsonErrorData($exception);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringNotContainsString('SQLSTATE', $result['message']);
    }

    public function testJsonErrorDataForDevelopment(): void
    {
        $exception = new \PDOException("SQLSTATE[42000]: Database connection failed");

        $result = $this->developmentService->getJsonErrorData($exception);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('SQLSTATE', $result['message']);
    }

    public function testPDOExceptionsAreSanitizedInProduction(): void
    {
        $exception = new \PDOException("SQLSTATE[HY000] [2002] Connection refused");

        $result = $this->productionService->getUserMessage($exception);

        $this->assertStringNotContainsString('SQLSTATE', $result);
        $this->assertStringNotContainsString('Connection refused', $result);
    }

    public function testExceptionMessagesWithSensitiveDataAreSanitized(): void
    {
        $exception = new \Exception("Failed to connect to MySQL server user='root' password='secret123'");

        $result = $this->productionService->getUserMessage($exception);

        $this->assertStringNotContainsString('secret123', $result);
    }

    public function testRuntimeExceptionsAreHandled(): void
    {
        $exception = new \RuntimeException("Unexpected runtime error occurred");

        $productionResult = $this->productionService->getUserMessage($exception);
        $developmentResult = $this->developmentService->getUserMessage($exception);

        $this->assertIsString($productionResult);
        $this->assertIsString($developmentResult);
        $this->assertNotEmpty($productionResult);
        $this->assertNotEmpty($developmentResult);
    }
}
