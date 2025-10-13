# Cookie Consent System

A comprehensive, GDPR-compliant cookie consent management system for the Know My Patient application.

## Overview

This cookie consent system is designed to comply with GDPR, PECR, and UK data protection regulations. It provides users with clear choices about cookie usage and maintains their preferences.

## Architecture

The system is organized into separate, maintainable components:

### Frontend Components

1. **Template**: `templates/components/cookie_consent.html.twig`
   - Responsive banner UI with Bootstrap styling
   - Teal theme matching website branding
   - Two action buttons: "Accept All" and "Essential Only"

2. **JavaScript Controller**: `public/js/cookie-consent.js`
   - `CookieConsentManager` class for all cookie consent logic
   - Handles consent storage, retrieval, and application
   - Supports Google Analytics consent mode
   - Dispatches custom events for third-party integrations

### Backend Components

3. **Service**: `src/Application/Services/CookieConsentService.php`
   - Server-side consent validation and checking
   - Helper methods for templates
   - Optional database logging
   - Analytics script generation

4. **API Controller**: `src/Application/Actions/Api/CookieConsentAction.php`
   - REST API endpoint for consent updates
   - Logs consent choices (optional)
   - Tracks user consent with IP and user agent

## Features

- ✅ **GDPR Compliant**: Clear consent options with granular control
- ✅ **Persistent Storage**: Cookies stored for 365 days
- ✅ **Responsive Design**: Works on mobile, tablet, and desktop
- ✅ **Smooth Animations**: Slides up from bottom with fade effects
- ✅ **Teal Theme**: Matches website branding (#17a2b8)
- ✅ **Google Analytics Integration**: Supports GA consent mode
- ✅ **Event System**: Custom events for third-party integrations
- ✅ **Server-Side Validation**: PHP service for consent checking
- ✅ **Optional Logging**: Track consent in database

## Usage

### Frontend (Automatic)

The cookie consent banner is automatically included in all pages via `base.html.twig`:

```twig
{% include 'components/cookie_consent.html.twig' %}
```

The JavaScript controller auto-initializes on page load.

### JavaScript API

Access the consent manager in your scripts:

```javascript
// Check consent status
const hasConsent = window.CookieConsentManager.getStatus();

// Check if specific cookie type is allowed
if (window.CookieConsentManager.isAllowed('analytics')) {
    // Initialize analytics
}

// Listen for consent changes
document.addEventListener('cookieConsentChanged', (event) => {
    console.log('Consent changed:', event.detail.consentType);
});

// Revoke consent (for testing or settings page)
window.CookieConsentManager.revokeConsent();
```

### PHP Service

Use the service in your controllers:

```php
use App\Application\Services\CookieConsentService;

$cookieService = new CookieConsentService();

// Check if user has given consent
if ($cookieService->hasConsent()) {
    // ...
}

// Check if analytics are allowed
if ($cookieService->canUseAnalytics()) {
    // Load analytics scripts
}

// Get consent data for template
$consentData = $cookieService->getConsentData();
```

## Configuration

### Cookie Settings

Edit configuration in `public/js/cookie-consent.js`:

```javascript
const CONFIG = {
    COOKIE_NAME: 'cookie_consent_status',
    COOKIE_EXPIRY_DAYS: 365,
    ANIMATION_DELAY: 1000,
    FADE_OUT_DURATION: 300,
    CONSENT_TYPES: {
        ALL: 'all',
        ESSENTIAL: 'essential',
        DECLINED: 'declined'
    }
};
```

### Customizing the Banner

Edit `templates/components/cookie_consent.html.twig` to change:
- Text content
- Button labels
- Privacy policy link
- Icon or styling

### Styling

Cookie banner styles are in `public/css/custom_styles.css`:

```css
/* Lines 1172-1266 */
.cookie-consent-banner {
    /* ... */
}
```

## API Endpoint (Optional)

If you want to log consent server-side:

### Route Setup

Add to `app/routes.php`:

```php
$app->post('/api/cookie-consent', \App\Application\Actions\Api\CookieConsentAction::class);
```

### Request Format

```javascript
fetch('/api/cookie-consent', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        consent_type: 'all' // or 'essential'
    })
});
```

### Response Format

```json
{
    "success": true,
    "message": "Cookie consent recorded successfully",
    "consent_type": "all"
}
```

## Database Logging (Optional)

To track consent in the database, create a table:

```sql
CREATE TABLE cookie_consent_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    consent_type VARCHAR(20) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);
```

Then implement the logging method in `CookieConsentService::logConsentToDatabase()`.

## Cookie Types

### Essential Cookies
Always enabled, required for website functionality:
- Session management
- CSRF protection
- User authentication
- Cookie consent preference

### Analytics Cookies
Enabled only with "Accept All":
- Google Analytics
- User behavior tracking
- Performance monitoring

### Marketing Cookies
Enabled only with "Accept All":
- Advertising pixels
- Retargeting
- Social media integrations

## Testing

### Test Consent Flow

1. Clear cookies in your browser
2. Reload page - banner should appear
3. Click "Accept All" - banner should hide
4. Check console: `[Cookie Consent] User consent: all`
5. Reload page - banner should not appear

### Test Essential Only

1. Clear cookies
2. Click "Essential Only"
3. Check console: `[Cookie Consent] User consent: essential`
4. Analytics should not load

### Revoke Consent

Open browser console:
```javascript
window.CookieConsentManager.revokeConsent();
```

Banner should reappear immediately.

## Browser Support

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## Compliance

This system complies with:
- **GDPR** (EU General Data Protection Regulation)
- **PECR** (UK Privacy and Electronic Communications Regulations)
- **ePrivacy Directive** (Cookie Law)
- **ICO Guidelines** (UK Information Commissioner's Office)

### Key Compliance Features

- ✅ Clear information about cookie usage
- ✅ Explicit consent required before non-essential cookies
- ✅ Easy opt-out mechanism
- ✅ Consent recorded with timestamp (optional)
- ✅ Link to detailed privacy policy
- ✅ Granular consent options

## Troubleshooting

### Banner Not Showing

1. Check browser console for errors
2. Verify `cookie-consent.js` is loaded
3. Clear cookies and reload
4. Check if element exists: `document.getElementById('cookieConsentBanner')`

### Consent Not Persisting

1. Check browser allows cookies
2. Verify cookie domain and path settings
3. Check for JavaScript errors
4. Ensure SameSite attribute is supported

### Styling Issues

1. Clear browser cache
2. Check Bootstrap Icons are loaded
3. Verify custom styles are loaded after Bootstrap
4. Check for CSS conflicts

## Maintenance

### Updating Text

Edit `templates/components/cookie_consent.html.twig`

### Changing Duration

Edit `COOKIE_EXPIRY_DAYS` in `cookie-consent.js`

### Adding New Cookie Types

1. Add type to `CONFIG.CONSENT_TYPES`
2. Update `CookieConsentService` constants
3. Add corresponding enable/disable methods
4. Update UI to include new option

## Future Enhancements

Possible improvements:
- [ ] Cookie preferences management page
- [ ] Detailed cookie list by category
- [ ] Multi-language support
- [ ] A/B testing for consent rates
- [ ] Advanced analytics on consent choices
- [ ] Integration with Consent Management Platform (CMP)

## Support

For issues or questions:
- Check documentation above
- Review browser console logs
- Test in incognito mode
- Contact development team

---

**Last Updated**: October 13, 2025  
**Version**: 1.0.0  
**Maintainer**: Know My Patient Development Team
