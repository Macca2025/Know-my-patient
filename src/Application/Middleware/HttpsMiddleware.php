<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * HTTPS Enforcement Middleware
 *
 * NHS DCB0129 Compliance: Hazard H-003 (Unauthorized Access Prevention)
 *
 * Enforces HTTPS connections in production environments to protect:
 * - Patient data in transit
 * - Authentication credentials
 * - Session cookies
 * - Personal identifiable information (PII)
 *
 * Features:
 * - Automatic HTTP to HTTPS redirect (301 permanent)
 * - Environment-aware (disabled in development)
 * - Preserves query parameters and path
 * - HSTS header support (optional)
 * - X-Forwarded-Proto header detection (for proxies/load balancers)
 */
class HttpsMiddleware implements MiddlewareInterface
{
    private bool $enforceHttps;
    private bool $enableHSTS;
    private int $hstsMaxAge;

    /**
     * @param bool $enforceHttps Whether to enforce HTTPS (set false for development)
     * @param bool $enableHSTS Whether to add HSTS header
     * @param int $hstsMaxAge HSTS max-age in seconds (default: 1 year)
     */
    public function __construct(
        bool $enforceHttps = true,
        bool $enableHSTS = true,
        int $hstsMaxAge = 31536000
    ) {
        $this->enforceHttps = $enforceHttps;
        $this->enableHSTS = $enableHSTS;
        $this->hstsMaxAge = $hstsMaxAge;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Skip enforcement if disabled (development mode)
        if (!$this->enforceHttps) {
            return $handler->handle($request);
        }

        // Check if request is already HTTPS
        if ($this->isHttpsRequest($request)) {
            $response = $handler->handle($request);

            // Add HSTS header if enabled
            if ($this->enableHSTS) {
                $response = $this->addHSTSHeader($response);
            }

            return $response;
        }

        // Request is HTTP - redirect to HTTPS
        return $this->redirectToHttps($request);
    }

    /**
     * Check if the request is using HTTPS
     * Supports X-Forwarded-Proto header for proxies/load balancers
     */
    private function isHttpsRequest(ServerRequestInterface $request): bool
    {
        $uri = $request->getUri();

        // Check URI scheme
        if ($uri->getScheme() === 'https') {
            return true;
        }

        // Check X-Forwarded-Proto header (for reverse proxies)
        $forwardedProto = $request->getHeaderLine('X-Forwarded-Proto');
        if (strtolower($forwardedProto) === 'https') {
            return true;
        }

        // Check if behind a load balancer/proxy
        $serverParams = $request->getServerParams();
        if (!empty($serverParams['HTTPS']) && $serverParams['HTTPS'] !== 'off') {
            return true;
        }

        return false;
    }

    /**
     * Redirect HTTP request to HTTPS
     */
    private function redirectToHttps(ServerRequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();

        // Build HTTPS URI
        $httpsUri = $uri
            ->withScheme('https')
            ->withPort(443);

        // Create redirect response
        $response = new Response();
        $response = $response
            ->withHeader('Location', (string) $httpsUri)
            ->withStatus(301); // 301 Permanent Redirect

        return $response;
    }

    /**
     * Add HTTP Strict Transport Security (HSTS) header
     *
     * HSTS tells browsers to always use HTTPS for this domain,
     * even if the user types http:// or clicks an http:// link.
     *
     * This provides protection against:
     * - Protocol downgrade attacks
     * - Cookie hijacking
     * - Man-in-the-middle attacks
     */
    private function addHSTSHeader(ResponseInterface $response): ResponseInterface
    {
        // max-age: How long browsers should remember to use HTTPS (in seconds)
        // includeSubDomains: Apply to all subdomains (optional, commented out)
        // preload: Allow inclusion in browser HSTS preload lists (optional, commented out)

        $hstsValue = sprintf('max-age=%d', $this->hstsMaxAge);

        // Uncomment to apply to subdomains:
        // $hstsValue .= '; includeSubDomains';

        // Uncomment to enable HSTS preload (requires submission to browser lists):
        // $hstsValue .= '; preload';

        return $response->withHeader('Strict-Transport-Security', $hstsValue);
    }
}
