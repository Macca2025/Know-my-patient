<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sentry\State\Scope;

use function Sentry\captureException;
use function Sentry\configureScope;

/**
 * Sentry Error Monitoring Middleware
 *
 * Captures unhandled exceptions and sends them to Sentry for monitoring
 */
class SentryMiddleware implements MiddlewareInterface
{
    private bool $enabled;

    public function __construct(bool $enabled = true)
    {
        $this->enabled = $enabled;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (!$this->enabled) {
            return $handler->handle($request);
        }

        try {
            // Add request context to Sentry
            configureScope(function (Scope $scope) use ($request): void {
                $scope->setContext('request', [
                    'url' => (string)$request->getUri(),
                    'method' => $request->getMethod(),
                    'headers' => $this->sanitizeHeaders($request->getHeaders()),
                    'query_string' => $request->getUri()->getQuery(),
                ]);

                // Add user context if available
                $session = $request->getAttribute('session');
                if ($session && isset($session['user_id'])) {
                    $scope->setUser([
                        'id' => $session['user_id'],
                        'ip_address' => $this->getClientIp($request),
                    ]);
                }
            });

            return $handler->handle($request);
        } catch (\Throwable $e) {
            // Capture exception in Sentry
            captureException($e);

            // Re-throw so the application's error handler can also handle it
            throw $e;
        }
    }

    /**
     * Sanitize headers to remove sensitive information
     *
     * @param array<string, array<string>> $headers
     * @return array<string, array<string>>
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'set-cookie', 'x-csrf-token'];

        foreach ($sensitiveHeaders as $header) {
            $headerLower = strtolower($header);
            foreach ($headers as $key => $value) {
                if (strtolower($key) === $headerLower) {
                    $headers[$key] = ['[REDACTED]'];
                }
            }
        }

        return $headers;
    }

    /**
     * Get client IP address
     */
    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();

        if (!empty($serverParams['HTTP_CLIENT_IP'])) {
            return $serverParams['HTTP_CLIENT_IP'];
        }

        if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        return $serverParams['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
