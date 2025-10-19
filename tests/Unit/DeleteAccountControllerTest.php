<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Uri;
use App\Application\Actions\User\ConfirmDeletionAction;

class DeleteAccountControllerTest extends TestCase
{
    public function testDeleteAccountRedirectsToLoginWithDeletedFlag(): void
    {
        // Create mocks for dependencies of ConfirmDeletionAction
        $twig = $this->createMock(\Slim\Views\Twig::class);
        $pdo = $this->createMock(\PDO::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $sessionService = $this->createMock(\App\Application\Services\SessionService::class);

        // Expect the session service to return a user id, and that clear/destroy are called
        $sessionService->expects($this->once())->method('get')->with('user_id')->willReturn(123);
        $sessionService->expects($this->once())->method('clear');
        $sessionService->expects($this->once())->method('destroy');

        // Expect the PDO to prepare and execute the delete statement
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->with($this->equalTo([123]))->willReturn(true);
        $pdo->expects($this->once())->method('prepare')->with($this->equalTo('DELETE FROM users WHERE id = ?'))->willReturn($stmtMock);

    /** @var \Slim\Views\Twig $twig */
    /** @var \PDO $pdo */
    /** @var \App\Application\Services\SessionService $sessionService */
    /** @var \Psr\Log\LoggerInterface $logger */
    $action = new ConfirmDeletionAction($twig, $pdo, $sessionService, $logger);

        // Build a POST request with the exact confirmation phrase
        $requestFactory = new ServerRequestFactory();
        $uri = new Uri('http', 'localhost', 8080, '/confirm-deletion');
        $request = $requestFactory->createServerRequest('POST', $uri)
            ->withParsedBody(['confirmText' => 'I CONFIRM MY ACCOUNT FOR DELETION']);

        $responseFactory = new \Slim\Psr7\Factory\ResponseFactory();
        $response = $responseFactory->createResponse();

        $result = $action->__invoke($request, $response);

        $this->assertEquals(302, $result->getStatusCode());
        $location = $result->getHeaderLine('Location');
        $this->assertStringContainsString('/login?deleted=1', $location);
    }
}
