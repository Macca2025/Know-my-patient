<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Uri;
use App\Application\Actions\DashboardController;

class DeleteAccountControllerTest extends TestCase
{
    public function testDeleteAccountRedirectsToLoginWithDeletedFlag(): void
    {
        // Create mocks for dependencies
        $twig = $this->createMock(\Slim\Views\Twig::class);
        $pdo = $this->createMock(\PDO::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $sessionService = $this->createMock(\App\Application\Services\SessionService::class);
        $cardRequestsController = $this->createMock(\App\Application\Actions\CardRequestsController::class);

        // Mock PDO prepare/execute to accept delete
    $stmtMock = $this->createMock(\PDOStatement::class);
    $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $pdo->expects($this->once())->method('prepare')->with($this->equalTo('DELETE FROM users WHERE id = ?'))->willReturn($stmtMock);

        // SessionService returns user id
        $sessionService->expects($this->once())->method('get')->with('user_id')->willReturn(123);
        $sessionService->expects($this->once())->method('clear');
        $sessionService->expects($this->once())->method('destroy');

        $controller = new DashboardController($twig, $pdo, $logger, $sessionService, $cardRequestsController);

        $requestFactory = new ServerRequestFactory();
        $uri = new Uri('http', 'localhost', 8080, '/delete-account');
        $request = $requestFactory->createServerRequest('GET', $uri);

        $responseFactory = new \Slim\Psr7\Factory\ResponseFactory();
        $response = $responseFactory->createResponse();

        $result = $controller->deleteAccount($request, $response);

        $this->assertEquals(302, $result->getStatusCode());
        $location = $result->getHeaderLine('Location');
        $this->assertStringContainsString('/login?deleted=1', $location);
    }
}
