<?php

declare(strict_types=1);

namespace App\Application\Services;

/**
 * Cookie Consent Service
 * 
 * Handles cookie consent management including:
 * - Checking consent status
 * - Recording consent choices (optional database logging)
 * - Validating consent types
 * - Providing helper methods for templates
 * 
 * @package App\Application\Services
 */
class CookieConsentService
{
    /**
     * Cookie consent types
     */
    public const CONSENT_ALL = 'all';
    public const CONSENT_ESSENTIAL = 'essential';
    public const CONSENT_DECLINED = 'declined';
    
    /**
     * Cookie consent cookie name
     */
    private const COOKIE_NAME = 'cookie_consent_status';
    
    /**
     * Cookie expiry in days
     */
    private const COOKIE_EXPIRY_DAYS = 365;
    
    /**
     * Check if user has given cookie consent
     * 
     * @return bool
     */
    public function hasConsent(): bool
    {
        return isset($_COOKIE[self::COOKIE_NAME]);
    }
    
    /**
     * Get the current consent status
     * 
     * @return string|null Returns the consent type or null if not set
     */
    public function getConsentStatus(): ?string
    {
        return $_COOKIE[self::COOKIE_NAME] ?? null;
    }
    
    /**
     * Check if user accepted all cookies
     * 
     * @return bool
     */
    public function hasAcceptedAll(): bool
    {
        return $this->getConsentStatus() === self::CONSENT_ALL;
    }
    
    /**
     * Check if user accepted only essential cookies
     * 
     * @return bool
     */
    public function hasAcceptedEssentialOnly(): bool
    {
        return $this->getConsentStatus() === self::CONSENT_ESSENTIAL;
    }
    
    /**
     * Check if analytics cookies are allowed
     * 
     * @return bool
     */
    public function canUseAnalytics(): bool
    {
        return $this->hasAcceptedAll();
    }
    
    /**
     * Check if marketing cookies are allowed
     * 
     * @return bool
     */
    public function canUseMarketing(): bool
    {
        return $this->hasAcceptedAll();
    }
    
    /**
     * Validate consent type
     * 
     * @param string $consentType
     * @return bool
     */
    public function isValidConsentType(string $consentType): bool
    {
        return in_array($consentType, [
            self::CONSENT_ALL,
            self::CONSENT_ESSENTIAL,
            self::CONSENT_DECLINED
        ], true);
    }
    
    /**
     * Set cookie consent (server-side)
     * Note: This should typically be done client-side via JavaScript
     * 
     * @param string $consentType
     * @return bool
     */
    public function setConsent(string $consentType): bool
    {
        if (!$this->isValidConsentType($consentType)) {
            return false;
        }
        
        $expiry = time() + (self::COOKIE_EXPIRY_DAYS * 24 * 60 * 60);
        
        return setcookie(
            self::COOKIE_NAME,
            $consentType,
            [
                'expires' => $expiry,
                'path' => '/',
                'secure' => true, // Only send over HTTPS
                'httponly' => false, // Allow JavaScript access
                'samesite' => 'Strict'
            ]
        );
    }
    
    /**
     * Revoke consent (for testing or user settings)
     * 
     * @return bool
     */
    public function revokeConsent(): bool
    {
        if (!$this->hasConsent()) {
            return true;
        }
        
        return setcookie(
            self::COOKIE_NAME,
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => false,
                'samesite' => 'Strict'
            ]
        );
    }
    
    /**
     * Log consent to database (optional)
     * You can implement this if you want to track consent in your database
     * 
     * @param int|null $userId User ID if logged in
     * @param string $consentType
     * @param string $ipAddress
     * @return bool
     */
    public function logConsentToDatabase(?int $userId, string $consentType, string $ipAddress): bool
    {
        // TODO: Implement database logging if needed
        // Example:
        // INSERT INTO cookie_consent_log (user_id, consent_type, ip_address, created_at)
        // VALUES (?, ?, ?, NOW())
        
        return true;
    }
    
    /**
     * Get consent data for template rendering
     * 
     * @return array
     */
    public function getConsentData(): array
    {
        return [
            'hasConsent' => $this->hasConsent(),
            'consentStatus' => $this->getConsentStatus(),
            'hasAcceptedAll' => $this->hasAcceptedAll(),
            'hasAcceptedEssentialOnly' => $this->hasAcceptedEssentialOnly(),
            'canUseAnalytics' => $this->canUseAnalytics(),
            'canUseMarketing' => $this->canUseMarketing(),
        ];
    }
    
    /**
     * Get Google Analytics initialization code if consent given
     * 
     * @param string|null $trackingId Google Analytics tracking ID
     * @return string HTML script tag or empty string
     */
    public function getAnalyticsScript(?string $trackingId = null): string
    {
        if (!$this->canUseAnalytics() || empty($trackingId)) {
            return '';
        }
        
        return <<<HTML
        <!-- Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={$trackingId}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{$trackingId}');
        </script>
        HTML;
    }
}
