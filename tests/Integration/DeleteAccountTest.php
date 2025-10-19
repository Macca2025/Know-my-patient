<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    public function testDeleteAccountRedirectsToLoginWithDeletedFlag(): void
    {
        // Mark that PHPUnit is running to change CSRF behavior
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }

        $app = $this->getAppInstance();

        // Simulate a logged-in user by setting session
        // Set session user_id directly for the test (no session_start to avoid header issues)
        $_SESSION['user_id'] = 1; // assume user with id=1 exists in test DB or logic handles non-existent

        // Create request to delete account
        $request = $this->createRequest('GET', '/delete-account');
        $response = $app->handle($request);

        // Assert redirect
        $this->assertEquals(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('/login?deleted=1', $location, 'Delete-account should redirect to login with deleted=1');
    }
}
