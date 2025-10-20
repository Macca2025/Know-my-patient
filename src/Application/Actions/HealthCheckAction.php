<?php

declare(strict_types=1);

namespace App\Application\Actions;

use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Health Check Action
 * Provides a /health endpoint for monitoring system status
 */
class HealthCheckAction
{
    private PDO $pdo;
    private LoggerInterface $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $startTime = microtime(true);

        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'checks' => []
        ];

        // 1. Database connectivity check
        try {
            $stmt = $this->pdo->query('SELECT 1');
            if ($stmt !== false) {
                $stmt->fetch();
                $health['checks']['database'] = [
                    'status' => 'ok',
                    'message' => 'Database connection successful'
                ];
            } else {
                throw new \RuntimeException('Database query failed');
            }
        } catch (\Exception $e) {
            $health['checks']['database'] = [
                'status' => 'error',
                'message' => 'Database connection failed'
            ];
            $health['status'] = 'unhealthy';
            // Log full error details for debugging (not exposed in response)
            $this->logger->error('Health check: Database connection failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        // 2. Disk space check
        try {
            $rootPath = __DIR__ . '/../../../';
            $freeSpace = disk_free_space($rootPath);
            $totalSpace = disk_total_space($rootPath);

            if ($freeSpace !== false && $totalSpace !== false) {
                $percentFree = ($freeSpace / $totalSpace) * 100;
                $freeGB = round($freeSpace / 1024 / 1024 / 1024, 2);
                $totalGB = round($totalSpace / 1024 / 1024 / 1024, 2);

                $diskStatus = 'ok';
                if ($percentFree < 5) {
                    $diskStatus = 'critical';
                    $health['status'] = 'unhealthy';
                } elseif ($percentFree < 10) {
                    $diskStatus = 'warning';
                    if ($health['status'] === 'healthy') {
                        $health['status'] = 'degraded';
                    }
                }

                $health['checks']['disk_space'] = [
                    'status' => $diskStatus,
                    'free_gb' => $freeGB,
                    'total_gb' => $totalGB,
                    'percent_free' => round($percentFree, 2)
                ];
            }
        } catch (\Exception $e) {
            $health['checks']['disk_space'] = [
                'status' => 'error',
                'message' => 'Disk space check failed'
            ];
        }

        // 3. Logs directory check
        try {
            $logsDir = __DIR__ . '/../../../logs';
            $isWritable = is_writable($logsDir);

            $health['checks']['logs'] = [
                'status' => $isWritable ? 'ok' : 'error',
                'writable' => $isWritable,
                'path' => $logsDir
            ];

            if (!$isWritable) {
                $health['status'] = 'unhealthy';
                $this->logger->warning('Health check: Logs directory not writable');
            }
        } catch (\Exception $e) {
            $health['checks']['logs'] = [
                'status' => 'error',
                'message' => 'Logs check failed'
            ];
        }

        // 4. Cache directory check
        try {
            $cacheDir = __DIR__ . '/../../../var/cache';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            $isWritable = is_writable($cacheDir);

            $health['checks']['cache'] = [
                'status' => $isWritable ? 'ok' : 'error',
                'writable' => $isWritable,
                'path' => $cacheDir
            ];

            if (!$isWritable) {
                if ($health['status'] === 'healthy') {
                    $health['status'] = 'degraded';
                }
            }
        } catch (\Exception $e) {
            $health['checks']['cache'] = [
                'status' => 'error',
                'message' => 'Cache check failed'
            ];
        }

        // 5. PHP version check
        $health['checks']['php'] = [
            'status' => 'ok',
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ];

        // 6. Response time
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        $health['response_time_ms'] = $responseTime;

        if ($responseTime > 1000) {
            $health['checks']['performance'] = [
                'status' => 'warning',
                'message' => 'Slow response time'
            ];
            if ($health['status'] === 'healthy') {
                $health['status'] = 'degraded';
            }
        }

        // Determine HTTP status code based on health status
        $statusCode = 200; // Default to OK
        if ($health['status'] === 'unhealthy') {
            $statusCode = 503; // Service Unavailable
        }

        $json = json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($json !== false ? $json : '{}');

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->withStatus($statusCode);
    }
}
