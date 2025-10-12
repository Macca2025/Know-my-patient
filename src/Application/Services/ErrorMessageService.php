<?php
declare(strict_types=1);

namespace App\Application\Services;

use Psr\Log\LoggerInterface;

/**
 * Error Message Service
 * 
 * NHS DCB0129 Compliance: Hazard H-003 (Unauthorized Access Prevention)
 * 
 * Provides secure error messages to users while logging detailed errors
 * for developers. Prevents information disclosure vulnerabilities.
 * 
 * Security Principles:
 * - Never expose stack traces to users
 * - Never expose database errors to users
 * - Never expose file paths to users
 * - Log full error details for debugging
 * - Provide user-friendly generic messages
 */
class ErrorMessageService
{
    private LoggerInterface $logger;
    private bool $isProduction;
    
    /**
     * Generic user-facing error messages (safe to display)
     */
    private const GENERIC_MESSAGES = [
        'database' => 'A database error occurred. Please try again later.',
        'validation' => 'Invalid input provided. Please check your data and try again.',
        'authentication' => 'Authentication failed. Please check your credentials.',
        'authorization' => 'You do not have permission to access this resource.',
        'not_found' => 'The requested resource was not found.',
        'server_error' => 'An unexpected error occurred. Our team has been notified.',
        'file_upload' => 'File upload failed. Please check the file and try again.',
        'network' => 'A network error occurred. Please check your connection.',
        'timeout' => 'The request timed out. Please try again.',
        'rate_limit' => 'Too many requests. Please wait a moment and try again.',
    ];
    
    public function __construct(LoggerInterface $logger, bool $isProduction = true)
    {
        $this->logger = $logger;
        $this->isProduction = $isProduction;
    }
    
    /**
     * Get a safe error message for users
     * Logs the actual error for developers
     * 
     * @param \Throwable $exception The actual exception
     * @param string $category Error category (database, validation, etc.)
     * @param string|null $customMessage Optional custom user message
     * @param array<string, mixed> $context Additional logging context
     * @return string Safe message to display to users
     */
    public function getUserMessage(
        \Throwable $exception,
        string $category = 'server_error',
        ?string $customMessage = null,
        array $context = []
    ): string {
        // Log the actual error with full details
        $this->logger->error($exception->getMessage(), array_merge($context, [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]));
        
        // In development, return actual error (for debugging)
        if (!$this->isProduction) {
            return $exception->getMessage();
        }
        
        // In production, return safe generic message
        if ($customMessage !== null) {
            return $customMessage;
        }
        
        return self::GENERIC_MESSAGES[$category] ?? self::GENERIC_MESSAGES['server_error'];
    }
    
    /**
     * Sanitize error message to remove sensitive information
     * Use this when you must show a custom error but want to ensure safety
     * 
     * @param string $message Original error message
     * @return string Sanitized message
     */
    public function sanitize(string $message): string
    {
        if (!$this->isProduction) {
            return $message;
        }
        
        // Remove file paths
        $message = preg_replace('#/[a-zA-Z0-9/_\-\.]+\.php#', '[file]', $message) ?? $message;
        
        // Remove SQL syntax
        $message = preg_replace('/\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b/i', '[SQL]', $message) ?? $message;
        
        // Remove stack trace indicators
        $message = preg_replace('/#\d+\s+/', '', $message) ?? $message;
        
        // Remove specific table/column names
        $message = preg_replace('/\btable\s+`[^`]+`/i', 'table [redacted]', $message) ?? $message;
        $message = preg_replace('/\bcolumn\s+`[^`]+`/i', 'column [redacted]', $message) ?? $message;
        
        return $message;
    }
    
    /**
     * Check if a message is safe to display to users
     * 
     * @param string $message Message to check
     * @return bool True if safe, false if potentially dangerous
     */
    public function isSafeMessage(string $message): bool
    {
        // Check for file paths
        if (preg_match('#/[a-zA-Z0-9/_\-\.]+\.php#', $message)) {
            return false;
        }
        
        // Check for SQL keywords
        if (preg_match('/\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER)\b/i', $message)) {
            return false;
        }
        
        // Check for stack traces
        if (preg_match('/#\d+\s+/', $message)) {
            return false;
        }
        
        // Check for database error indicators
        if (preg_match('/\b(SQLSTATE|mysqli|PDO|database|query)\b/i', $message)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get generic message by category
     * 
     * @param string $category Error category
     * @return string Generic safe message
     */
    public function getGenericMessage(string $category = 'server_error'): string
    {
        return self::GENERIC_MESSAGES[$category] ?? self::GENERIC_MESSAGES['server_error'];
    }
    
    /**
     * Log and return safe error message for JSON responses
     * 
     * @param \Throwable $exception The actual exception
     * @param string $category Error category
     * @param array<string, mixed> $context Additional logging context
     * @return array<string, mixed> Safe error data for JSON response
     */
    public function getJsonErrorData(
        \Throwable $exception,
        string $category = 'server_error',
        array $context = []
    ): array {
        $message = $this->getUserMessage($exception, $category, null, $context);
        
        return [
            'success' => false,
            'error' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
}
