<?php

declare(strict_types=1);

namespace App\Application\Services;

/**
 * Service to retrieve the user's actual IP address
 * Handles proxies, load balancers, and localhost scenarios
 */
class IpAddressService
{
    /**
     * Get the real IP address of the client
     * 
     * Checks various headers in order of preference:
     * 1. HTTP_CF_CONNECTING_IP (Cloudflare)
     * 2. HTTP_X_REAL_IP (Nginx proxy)
     * 3. HTTP_X_FORWARDED_FOR (Standard proxy header)
     * 4. REMOTE_ADDR (Direct connection)
     * 
     * @return string The client's IP address or 'unknown' if not determinable
     */
    public static function getClientIp(): string
    {
        // Check for Cloudflare
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return self::validateIp($_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        
        // Check for proxy headers
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return self::validateIp($_SERVER['HTTP_X_REAL_IP']);
        }
        
        // Check X-Forwarded-For (can contain multiple IPs, get the first one)
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
            return self::validateIp($ip);
        }
        
        // Fall back to REMOTE_ADDR
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
            
            // If it's localhost/127.0.0.1/::1, try to get a better IP
            if (self::isLocalhost($ip)) {
                // Check if we can get a better IP from other sources
                if (!empty($_SERVER['SERVER_ADDR']) && !self::isLocalhost($_SERVER['SERVER_ADDR'])) {
                    return self::validateIp($_SERVER['SERVER_ADDR']);
                }
                
                // For development, return a placeholder
                return 'localhost';
            }
            
            return self::validateIp($ip);
        }
        
        return 'unknown';
    }
    
    /**
     * Validate and sanitize an IP address
     * 
     * @param string $ip The IP address to validate
     * @return string The validated IP or 'unknown' if invalid
     */
    private static function validateIp(string $ip): string
    {
        $ip = trim($ip);
        
        // Remove port if present
        if (strpos($ip, ':') !== false && strpos($ip, '.') !== false) {
            // IPv4 with port
            $parts = explode(':', $ip);
            $ip = $parts[0];
        }
        
        // Validate IP format
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
        
        // Allow private/reserved IPs if validation failed (for local development)
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        return 'unknown';
    }
    
    /**
     * Check if an IP address is localhost
     * 
     * @param string $ip The IP address to check
     * @return bool True if localhost, false otherwise
     */
    private static function isLocalhost(string $ip): bool
    {
        $localhostIps = [
            '127.0.0.1',
            '::1',
            'localhost',
            '0.0.0.0',
            '::',
        ];
        
        return in_array($ip, $localhostIps, true);
    }
    
    /**
     * Get detailed IP information for debugging
     * 
     * @return array An array of all IP-related headers
     */
    public static function getIpDebugInfo(): array
    {
        return [
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
            'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'HTTP_X_REAL_IP' => $_SERVER['HTTP_X_REAL_IP'] ?? null,
            'HTTP_CF_CONNECTING_IP' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            'HTTP_CLIENT_IP' => $_SERVER['HTTP_CLIENT_IP'] ?? null,
            'SERVER_ADDR' => $_SERVER['SERVER_ADDR'] ?? null,
            'detected_ip' => self::getClientIp(),
        ];
    }
}
