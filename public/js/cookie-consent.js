/**
 * Cookie Consent Manager
 * Handles GDPR-compliant cookie consent for the website
 * 
 * @author Know My Patient
 * @version 1.0.0
 */

(function(window, document) {
    'use strict';

    // Configuration
    const CONFIG = {
        COOKIE_NAME: 'cookie_consent_status',
        COOKIE_EXPIRY_DAYS: 365,
        ANIMATION_DELAY: 1000, // Delay before showing banner (ms)
        FADE_OUT_DURATION: 300, // Fade out animation duration (ms)
        CONSENT_TYPES: {
            ALL: 'all',
            ESSENTIAL: 'essential',
            DECLINED: 'declined'
        }
    };

    // Cookie Consent Manager Class
    class CookieConsentManager {
        constructor() {
            this.banner = null;
            this.acceptAllBtn = null;
            this.acceptEssentialBtn = null;
            this.consentStatus = null;
        }

        /**
         * Initialize the cookie consent manager
         */
        init() {
            this.banner = document.getElementById('cookieConsentBanner');
            
            if (!this.banner) {
                console.warn('Cookie consent banner element not found');
                return;
            }

            this.acceptAllBtn = document.getElementById('acceptAllCookies');
            this.acceptEssentialBtn = document.getElementById('acceptEssentialCookies');
            
            // Check if user has already made a choice
            this.consentStatus = this.getCookieConsent();
            
            if (!this.consentStatus) {
                this.showBanner();
            } else {
                this.applyConsentSettings(this.consentStatus);
            }
            
            this.attachEventListeners();
        }

        /**
         * Get the current cookie consent status
         * @returns {string|null} The consent status or null if not set
         */
        getCookieConsent() {
            const name = CONFIG.COOKIE_NAME + '=';
            const decodedCookie = decodeURIComponent(document.cookie);
            const cookieArray = decodedCookie.split(';');
            
            for(let i = 0; i < cookieArray.length; i++) {
                let cookie = cookieArray[i].trim();
                if (cookie.indexOf(name) === 0) {
                    return cookie.substring(name.length, cookie.length);
                }
            }
            return null;
        }

        /**
         * Set the cookie consent status
         * @param {string} value - The consent type (all, essential, declined)
         */
        setCookieConsent(value) {
            const date = new Date();
            date.setTime(date.getTime() + (CONFIG.COOKIE_EXPIRY_DAYS * 24 * 60 * 60 * 1000));
            const expires = 'expires=' + date.toUTCString();
            document.cookie = CONFIG.COOKIE_NAME + '=' + value + ';' + expires + ';path=/;SameSite=Strict';
            
            // Log consent to console (for debugging)
            console.log('[Cookie Consent] User consent: ' + value);
            
            // Apply consent settings
            this.applyConsentSettings(value);
        }

        /**
         * Apply consent settings based on user choice
         * @param {string} consentType - The type of consent given
         */
        applyConsentSettings(consentType) {
            switch(consentType) {
                case CONFIG.CONSENT_TYPES.ALL:
                    this.enableAllCookies();
                    break;
                case CONFIG.CONSENT_TYPES.ESSENTIAL:
                    this.enableEssentialCookies();
                    break;
                case CONFIG.CONSENT_TYPES.DECLINED:
                    this.disableOptionalCookies();
                    break;
                default:
                    console.warn('[Cookie Consent] Unknown consent type: ' + consentType);
            }
        }

        /**
         * Enable all cookies (essential + analytics + marketing)
         */
        enableAllCookies() {
            console.log('[Cookie Consent] Enabling all cookies');
            
            // Enable Google Analytics if configured
            if (typeof window.gtag !== 'undefined') {
                window.gtag('consent', 'update', {
                    'analytics_storage': 'granted',
                    'ad_storage': 'granted'
                });
            }
            
            // Enable other tracking services here
            // Example: Facebook Pixel, Hotjar, etc.
            
            // Dispatch custom event for other scripts to listen to
            this.dispatchConsentEvent('all');
        }

        /**
         * Enable only essential cookies
         */
        enableEssentialCookies() {
            console.log('[Cookie Consent] Enabling essential cookies only');
            
            // Deny Google Analytics if configured
            if (typeof window.gtag !== 'undefined') {
                window.gtag('consent', 'update', {
                    'analytics_storage': 'denied',
                    'ad_storage': 'denied'
                });
            }
            
            // Disable other tracking services
            
            // Dispatch custom event
            this.dispatchConsentEvent('essential');
        }

        /**
         * Disable all optional cookies
         */
        disableOptionalCookies() {
            console.log('[Cookie Consent] Disabling optional cookies');
            this.enableEssentialCookies(); // Same as essential-only
        }

        /**
         * Dispatch a custom event when consent is given
         * @param {string} consentType - The type of consent
         */
        dispatchConsentEvent(consentType) {
            const event = new CustomEvent('cookieConsentChanged', {
                detail: { consentType: consentType },
                bubbles: true,
                cancelable: true
            });
            document.dispatchEvent(event);
        }

        /**
         * Show the cookie consent banner with animation
         */
        showBanner() {
            if (!this.banner) return;
            
            setTimeout(() => {
                this.banner.style.display = 'block';
                // Trigger reflow to ensure animation plays
                void this.banner.offsetWidth;
                this.banner.classList.add('show');
            }, CONFIG.ANIMATION_DELAY);
        }

        /**
         * Hide the cookie consent banner with animation
         */
        hideBanner() {
            if (!this.banner) return;
            
            this.banner.classList.remove('show');
            setTimeout(() => {
                this.banner.style.display = 'none';
            }, CONFIG.FADE_OUT_DURATION);
        }

        /**
         * Attach event listeners to buttons
         */
        attachEventListeners() {
            if (this.acceptAllBtn) {
                this.acceptAllBtn.addEventListener('click', () => {
                    this.handleAcceptAll();
                });
            }
            
            if (this.acceptEssentialBtn) {
                this.acceptEssentialBtn.addEventListener('click', () => {
                    this.handleAcceptEssential();
                });
            }
        }

        /**
         * Handle "Accept All" button click
         */
        handleAcceptAll() {
            this.setCookieConsent(CONFIG.CONSENT_TYPES.ALL);
            this.hideBanner();
            
            // Optional: Show a brief success message
            this.showConsentMessage('All cookies accepted. Thank you!');
        }

        /**
         * Handle "Essential Only" button click
         */
        handleAcceptEssential() {
            this.setCookieConsent(CONFIG.CONSENT_TYPES.ESSENTIAL);
            this.hideBanner();
            
            // Optional: Show a brief success message
            this.showConsentMessage('Essential cookies accepted.');
        }

        /**
         * Show a brief success message (optional)
         * @param {string} message - The message to display
         */
        showConsentMessage(message) {
            // You can implement a toast notification here if desired
            console.log('[Cookie Consent] ' + message);
        }

        /**
         * Revoke consent (useful for testing or user settings page)
         */
        revokeConsent() {
            document.cookie = CONFIG.COOKIE_NAME + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
            console.log('[Cookie Consent] Consent revoked');
            this.showBanner();
        }

        /**
         * Get current consent status (public method)
         * @returns {string|null} Current consent status
         */
        getStatus() {
            return this.consentStatus;
        }

        /**
         * Check if specific cookie type is allowed
         * @param {string} cookieType - Type of cookie (essential, analytics, marketing)
         * @returns {boolean} Whether the cookie type is allowed
         */
        isAllowed(cookieType) {
            const status = this.getCookieConsent();
            
            if (!status) return false;
            if (cookieType === 'essential') return true;
            if (status === CONFIG.CONSENT_TYPES.ALL) return true;
            
            return false;
        }
    }

    // Create and expose global instance
    window.CookieConsentManager = new CookieConsentManager();

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.CookieConsentManager.init();
        });
    } else {
        // DOM already loaded
        window.CookieConsentManager.init();
    }

})(window, document);
