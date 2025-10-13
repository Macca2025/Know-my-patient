<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Application\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * RateLimitMiddleware Unit Tests
 * Tests rate limiting functionality for preventing abuse
 */
class RateLimitMiddlewareTest extends TestCase
{
    private string $testCacheDir;

    protected function setUp(): void
    {
        // Create temporary cache directory for tests
        $this->testCacheDir = sys_get_temp_dir() . '/rate_limit_test_' . uniqid();
        if (!is_dir($this->testCacheDir)) {
            mkdir($this->testCacheDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test cache directory
        if (is_dir($this->testCacheDir)) {
            $files = glob($this->testCacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testCacheDir);
        }

        // Clean up $_SERVER
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    /**
     * Create a mock request with specific IP
     */
    private function createMockRequest(string $ip = '127.0.0.1'): ServerRequestInterface
    {
        // Set the IP in $_SERVER so IpAddressService can retrieve it
        $_SERVER['HTTP_X_FORWARDED_FOR'] = $ip;

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/login');
        return $request->withHeader('X-Forwarded-For', $ip);
    }

    /**
     * Create a mock handler
     */
    private function createMockHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                return new Response(200);
            }
        };
    }

    /**
     * Test that requests within limit are allowed
     */
    public function testRequestsWithinLimitAreAllowed(): void
    {
        $middleware = new RateLimitMiddleware(5, 1, $this->testCacheDir); // 5 attempts per minute
        $request = $this->createMockRequest('192.168.1.1');
        $handler = $this->createMockHandler();

        // First request should succeed
        $response = $middleware->process($request, $handler);
        $this->assertEquals(200, $response->getStatusCode());

        // Second request should also succeed
        $response = $middleware->process($request, $handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that exceeding the limit blocks requests
     */
    public function testExceedingLimitBlocksRequests(): void
    {
        $middleware = new RateLimitMiddleware(3, 1, $this->testCacheDir); // 3 attempts per minute
        $request = $this->createMockRequest('192.168.1.2');
        $handler = $this->createMockHandler();

        // Make 3 successful requests
        for ($i = 0; $i < 3; $i++) {
            $response = $middleware->process($request, $handler);
            $this->assertEquals(200, $response->getStatusCode());
        }

        // 4th request should be rate limited
        $response = $middleware->process($request, $handler);
        $this->assertEquals(429, $response->getStatusCode());

        // Check response body contains error message
        $body = (string)$response->getBody();
        $this->assertStringContainsString('Too many attempts', $body);
    }

    /**
     * Test that different IPs are tracked separately
     */
    public function testDifferentIPsTrackedSeparately(): void
    {
        $middleware = new RateLimitMiddleware(2, 1, $this->testCacheDir); // 2 attempts per minute
        $handler = $this->createMockHandler();

        // Make 2 requests from IP1 (should succeed)
        $request1 = $this->createMockRequest('192.168.1.10');
        $response = $middleware->process($request1, $handler);
        $this->assertEquals(200, $response->getStatusCode());
        $response = $middleware->process($request1, $handler);
        $this->assertEquals(200, $response->getStatusCode());

        // 3rd request from IP1 should be blocked
        $response = $middleware->process($request1, $handler);
        $this->assertEquals(429, $response->getStatusCode());

        // But request from IP2 should still work - need fresh middleware to avoid shared cache issues
        $middleware2 = new RateLimitMiddleware(2, 60, $this->testCacheDir);
        $request2 = $this->createMockRequest('192.168.1.20');
        $response = $middleware2->process($request2, $handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test rate limit reset after time window
     * Tests that expired cache entries are properly cleaned up
     */
    public function testRateLimitResetsAfterTimeWindow(): void
    {
        $middleware = new RateLimitMiddleware(2, 1, $this->testCacheDir);
        $request = $this->createMockRequest('192.168.1.30');
        $handler = $this->createMockHandler();

        // Make 2 requests (should succeed)
        $response = $middleware->process($request, $handler);
        $this->assertEquals(200, $response->getStatusCode());
        $response = $middleware->process($request, $handler);
        $this->assertEquals(200, $response->getStatusCode());

        // 3rd request should be blocked
        $response = $middleware->process($request, $handler);
        $this->assertEquals(429, $response->getStatusCode());

        // Manually expire the cache file by setting expires_at to the past
        $cacheFiles = glob($this->testCacheDir . '/*');
        $this->assertNotEmpty($cacheFiles, 'Cache file should exist');

        foreach ($cacheFiles as $file) {
            $data = json_decode(file_get_contents($file), true);
            $data['expires_at'] = time() - 3600; // Expired 1 hour ago
            file_put_contents($file, json_encode($data));
        }

        // Request should now succeed again (expired cache is cleaned up)
        $response = $middleware->process($request, $handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test retry-after header is set correctly
     */
    public function testRetryAfterHeaderIsSet(): void
    {
        $middleware = new RateLimitMiddleware(1, 5, $this->testCacheDir); // 1 attempt per 5 minutes
        $request = $this->createMockRequest('192.168.1.40');
        $handler = $this->createMockHandler();

        // First request succeeds
        $middleware->process($request, $handler);

        // Second request gets rate limited
        $response = $middleware->process($request, $handler);
        $this->assertEquals(429, $response->getStatusCode());

        // Check retry_after is in response
        $body = (string)$response->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('retry_after', $data);
        $this->assertIsInt($data['retry_after']);
        $this->assertGreaterThan(0, $data['retry_after']);
    }

    /**
     * Test cache file is created correctly
     */
    public function testCacheFileIsCreated(): void
    {
        $middleware = new RateLimitMiddleware(5, 1, $this->testCacheDir);
        $request = $this->createMockRequest('192.168.1.50');
        $handler = $this->createMockHandler();

        // Make a request
        $middleware->process($request, $handler);

        // Check that cache directory has files
        $files = glob($this->testCacheDir . '/*');
        $this->assertNotEmpty($files);
    }

    /**
     * Test handling of missing IP address
     */
    public function testHandlingMissingIPAddress(): void
    {
        $middleware = new RateLimitMiddleware(3, 1, $this->testCacheDir);

        // Create request without IP headers
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/login');
        $handler = $this->createMockHandler();

        // Should still work (uses default IP handling)
        $response = $middleware->process($request, $handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test zero attempts configuration
     */
    public function testZeroAttemptsConfiguration(): void
    {
        $middleware = new RateLimitMiddleware(0, 1, $this->testCacheDir); // 0 attempts = always block
        $request = $this->createMockRequest('192.168.1.60');
        $handler = $this->createMockHandler();

        // First request should be blocked
        $response = $middleware->process($request, $handler);
        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * Test high limit configuration
     */
    public function testHighLimitConfiguration(): void
    {
        $middleware = new RateLimitMiddleware(1000, 1, $this->testCacheDir);
        $request = $this->createMockRequest('192.168.1.70');
        $handler = $this->createMockHandler();

        // Multiple requests should all succeed
        for ($i = 0; $i < 10; $i++) {
            $response = $middleware->process($request, $handler);
            $this->assertEquals(200, $response->getStatusCode());
        }
    }
}
