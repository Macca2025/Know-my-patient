<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Rate Limiting Middleware
 * Prevents brute force attacks and API abuse
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxAttempts;
    private int $decayMinutes;
    private string $cacheDir;

    public function __construct(int $maxAttempts = 5, int $decayMinutes = 15, string $cacheDir = '/tmp')
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
        $this->cacheDir = $cacheDir;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $key = $this->resolveRequestKey($request);
        $attempts = $this->getAttempts($key);

        if ($attempts >= $this->maxAttempts) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'error' => 'Too many attempts. Please try again later.',
                'retry_after' => $this->decayMinutes * 60
            ]));
            return $response
                ->withStatus(429)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string)($this->decayMinutes * 60));
        }

        $this->incrementAttempts($key);
        return $handler->handle($request);
    }

    private function resolveRequestKey(Request $request): string
    {
        $uri = $request->getUri()->getPath();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return 'rate_limit:' . md5($ip . ':' . $uri);
    }

    private function getAttempts(string $key): int
    {
        $file = $this->cacheDir . '/' . $key;
        if (!file_exists($file)) {
            return 0;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data || $data['expires_at'] < time()) {
            unlink($file);
            return 0;
        }

        return $data['attempts'];
    }

    private function incrementAttempts(string $key): void
    {
        $file = $this->cacheDir . '/' . $key;
        $attempts = $this->getAttempts($key) + 1;
        $expiresAt = time() + ($this->decayMinutes * 60);

        file_put_contents($file, json_encode([
            'attempts' => $attempts,
            'expires_at' => $expiresAt
        ]));
    }
}
