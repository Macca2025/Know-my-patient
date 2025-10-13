<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Application\Middleware\SecurityHeadersMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class SecurityHeadersMiddlewareTest extends TestCase
{
    private SecurityHeadersMiddleware $middleware;
    private ServerRequestInterface $request;
    private RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->middleware = new SecurityHeadersMiddleware();

        // Create a mock request
        $requestFactory = new ServerRequestFactory();
        $this->request = $requestFactory->createServerRequest('GET', '/');

        // Create a mock handler that returns a basic response
        $this->handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $responseFactory = new ResponseFactory();
                return $responseFactory->createResponse();
            }
        };
    }

    public function testAddsContentSecurityPolicyHeader(): void
    {
        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertTrue($response->hasHeader('Content-Security-Policy'));

        $csp = $response->getHeaderLine('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src", $csp);
        $this->assertStringContainsString("style-src", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    public function testAddsXContentTypeOptionsHeader(): void
    {
        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertTrue($response->hasHeader('X-Content-Type-Options'));
        $this->assertEquals('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
    }

    public function testAddsXFrameOptionsHeader(): void
    {
        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertTrue($response->hasHeader('X-Frame-Options'));
        $this->assertEquals('DENY', $response->getHeaderLine('X-Frame-Options'));
    }

    public function testAddsXXSSProtectionHeader(): void
    {
        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertTrue($response->hasHeader('X-XSS-Protection'));
        $this->assertEquals('1; mode=block', $response->getHeaderLine('X-XSS-Protection'));
    }

    public function testAddsReferrerPolicyHeader(): void
    {
        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertTrue($response->hasHeader('Referrer-Policy'));
        $this->assertEquals('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
    }

    public function testAddsPermissionsPolicyHeader(): void
    {
        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertTrue($response->hasHeader('Permissions-Policy'));

        $policy = $response->getHeaderLine('Permissions-Policy');
        $this->assertStringContainsString('geolocation=()', $policy);
        $this->assertStringContainsString('microphone=()', $policy);
        // Camera allowed for QR code scanning functionality
        $this->assertStringContainsString('camera=(self)', $policy);
    }

    public function testAddsStrictTransportSecurityHeader(): void
    {
        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertTrue($response->hasHeader('Strict-Transport-Security'));
        $this->assertStringContainsString('max-age=31536000', $response->getHeaderLine('Strict-Transport-Security'));
        $this->assertStringContainsString('includeSubDomains', $response->getHeaderLine('Strict-Transport-Security'));
    }

    public function testRemovesServerSignatureHeaders(): void
    {
        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertFalse($response->hasHeader('X-Powered-By'));
        $this->assertFalse($response->hasHeader('Server'));
    }

    public function testCSPIncludesUpgradeInsecureRequests(): void
    {
        $response = $this->middleware->process($this->request, $this->handler);

        $csp = $response->getHeaderLine('Content-Security-Policy');
        $this->assertStringContainsString('upgrade-insecure-requests', $csp);
    }

    public function testCSPBlocksUnsafeObjectSources(): void
    {
        $response = $this->middleware->process($this->request, $this->handler);

        $csp = $response->getHeaderLine('Content-Security-Policy');
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    public function testCSPAllowsCDNSources(): void
    {
        $response = $this->middleware->process($this->request, $this->handler);

        $csp = $response->getHeaderLine('Content-Security-Policy');
        $this->assertStringContainsString('cdn.jsdelivr.net', $csp);
        // Wildcard for all Cloudflare CDN subdomains (includes cdnjs.cloudflare.com)
        $this->assertStringContainsString('*.cloudflare.com', $csp);
        $this->assertStringContainsString('fonts.googleapis.com', $csp);
        $this->assertStringContainsString('fonts.gstatic.com', $csp);
    }

    public function testCSPAllowsDataURIsForImages(): void
    {
        $response = $this->middleware->process($this->request, $this->handler);

        $csp = $response->getHeaderLine('Content-Security-Policy');
        $this->assertStringContainsString("img-src 'self' data:", $csp);
    }

    public function testAllSecurityHeadersArePresentTogether(): void
    {
        $response = $this->middleware->process($this->request, $this->handler);

        $expectedHeaders = [
            'Content-Security-Policy',
            'X-Content-Type-Options',
            'X-Frame-Options',
            'X-XSS-Protection',
            'Referrer-Policy',
            'Permissions-Policy',
            'Strict-Transport-Security'
        ];

        foreach ($expectedHeaders as $header) {
            $this->assertTrue(
                $response->hasHeader($header),
                "Missing expected security header: {$header}"
            );
        }
    }
}
