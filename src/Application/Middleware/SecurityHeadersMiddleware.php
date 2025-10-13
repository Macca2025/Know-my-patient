<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Security Headers Middleware
 *
 * Adds comprehensive security headers to all responses to protect against:
 * - XSS (Cross-Site Scripting) attacks
 * - Clickjacking attacks
 * - MIME sniffing vulnerabilities
 * - Protocol downgrade attacks
 *
 * Implements OWASP security best practices
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response with security headers
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);

        // Build Content Security Policy
        $csp = $this->buildContentSecurityPolicy();

        return $response
            // Content Security Policy - Primary XSS defense
            ->withHeader('Content-Security-Policy', $csp)
            
            // Prevent MIME type sniffing
            ->withHeader('X-Content-Type-Options', 'nosniff')
            
            // Prevent clickjacking by denying iframe embedding
            ->withHeader('X-Frame-Options', 'DENY')
            
            // Enable browser's XSS protection (legacy support)
            ->withHeader('X-XSS-Protection', '1; mode=block')
            
            // Control referrer information
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            
            // Restrict browser features and APIs
            ->withHeader('Permissions-Policy', $this->buildPermissionsPolicy())
            
            // Force HTTPS (only if already on HTTPS)
            ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload')
            
            // Remove server signature to prevent information disclosure
            ->withoutHeader('X-Powered-By')
            ->withoutHeader('Server');
    }

    /**
     * Build Content Security Policy directive
     *
     * This policy allows the application to function while blocking most XSS vectors
     */
    private function buildContentSecurityPolicy(): string
    {
        $directives = [
            // Default policy for all resource types not explicitly defined
            "default-src 'self'",
            
            // JavaScript sources
            // 'unsafe-inline' and 'unsafe-eval' needed for some libraries
            // Consider removing these and using nonces for better security
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://*.cloudflare.com",
            
            // CSS sources
            // 'unsafe-inline' needed for inline styles in templates
            // *.cloudflare.com needed for FontAwesome and CDN resources
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://*.cloudflare.com https://fonts.googleapis.com",
            
            // Font sources
            // cdn.jsdelivr.net needed for Bootstrap Icons
            // *.cloudflare.com needed for FontAwesome webfonts
            "font-src 'self' https://cdn.jsdelivr.net https://*.cloudflare.com https://fonts.gstatic.com data:",
            
            // Image sources
            // data: needed for QR codes and base64 images
            "img-src 'self' data: https:",
            
            // AJAX, WebSocket, and EventSource connections
            "connect-src 'self'",
            
            // <object>, <embed>, and <applet> elements
            "object-src 'none'",
            
            // Audio and video sources
            "media-src 'self'",
            
            // Web Workers, Service Workers
            "worker-src 'self'",
            
            // Form submission targets
            "form-action 'self'",
            
            // Valid parents that may embed this page
            "frame-ancestors 'none'",
            
            // Restrict <base> tag URLs
            "base-uri 'self'",
            
            // Block all mixed content (HTTP on HTTPS pages)
            "upgrade-insecure-requests"
        ];

        return implode('; ', $directives);
    }

    /**
     * Build Permissions Policy directive
     *
     * Disables potentially dangerous browser features
     * Note: Camera access allowed for QR code scanning functionality
     */
    private function buildPermissionsPolicy(): string
    {
        $policies = [
            'geolocation=()',        // Block geolocation
            'microphone=()',         // Block microphone access
            'camera=(self)',         // Allow camera access for QR scanning
            'payment=()',            // Block payment API
            'usb=()',                // Block USB access
            'magnetometer=()',       // Block magnetometer
            'accelerometer=()',      // Block accelerometer
            'gyroscope=()',          // Block gyroscope
            'picture-in-picture=()', // Block picture-in-picture
        ];

        return implode(', ', $policies);
    }
}
