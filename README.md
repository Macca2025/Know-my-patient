# Know My Patient

**Healthcare patient information management system**

A PHP-based web application built with Slim Framework 4 to help healthcare organizations manage patient profiles, coordinate care, and maintain secure medical information.

## Features

- 🔐 **Secure Authentication** - Role-based access control (Admin, Healthcare Worker, Patient, Family)
- 👥 **Patient Profiles** - Comprehensive patient information with QR code access
- 📊 **Admin Dashboard** - Manage users, testimonials, support requests, and card requests
- 🎫 **Card Requests** - Physical patient card ordering and tracking
- 📝 **Audit Logging** - Complete audit trail with real IP address tracking
- 💬 **Support System** - Built-in support ticket management
- 🏥 **Onboarding** - Healthcare organization inquiry system

## Technology Stack

- **Framework**: Slim 4.x
- **PHP**: 8.1+ (strict types enabled)
- **Database**: MySQL 8.0+
- **Template Engine**: Twig 3.x
- **Frontend**: Bootstrap 5, vanilla JavaScript
- **Security**: CSRF protection, password hashing (PASSWORD_DEFAULT)
- **Logging**: Monolog
- **Dependency Injection**: PHP-DI

## Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or 8.0+
- Composer 2.x
- Apache or Nginx with mod_rewrite
- Extensions: PDO, pdo_mysql, mbstring, json

## Installation

### 1. Clone Repository

```bash
git clone <repository-url>
cd know_my_patient
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` with your database credentials:

```env
APP_ENV=development
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=know_my_patient
DB_USER=your_username
DB_PASS=your_password
```

### 4. Set Up Database

```bash
# Import schema
mysql -u your_username -p know_my_patient < database/schema.sql

# Add indexes for performance
mysql -u your_username -p know_my_patient < database_indexes.sql
```

### 5. Set Permissions

```bash
chmod -R 775 var/cache logs/
```

### 6. Start Development Server

Using MAMP:
- Place project in `htdocs/`
- Start Apache and MySQL
- Visit: `http://localhost:8888/know_my_patient/public`

Or using PHP built-in server:
```bash
php -S localhost:8000 -t public/
```

## Project Structure

```
know_my_patient/
├── app/                      # Application configuration
│   ├── dependencies.php      # DI container bindings
│   ├── middleware.php        # Middleware stack
│   ├── routes.php            # Route definitions
│   └── settings.php          # App settings
├── public/                   # Web root
│   ├── index.php             # Entry point
│   ├── css/                  # Stylesheets
│   └── js/                   # JavaScript files
├── src/                      # Application source code
│   ├── Application/          # Application layer
│   │   ├── Actions/          # Controllers
│   │   ├── Handlers/         # Error handlers
│   │   ├── Middleware/       # Custom middleware
│   │   └── Services/         # Business logic services
│   ├── Domain/               # Domain models
│   └── Infrastructure/       # Data persistence
├── templates/                # Twig templates
│   ├── admin/                # Admin panel views
│   ├── layouts/              # Base layouts
│   └── users_pages/          # User-facing pages
├── tests/                    # Unit & integration tests
├── var/                      # Cache & temporary files
├── vendor/                   # Composer dependencies
├── .env                      # Environment config (not in git)
├── .env.example              # Environment template
└── composer.json             # PHP dependencies
```

## Usage

### Default Admin Login

After fresh installation:
- Email: `admin@example.com`
- Password: (check database or create new admin)

### Creating Users

Admins can create users via:
```
Admin Dashboard → Users → Add New User
```

### Patient Profiles

1. Healthcare workers can add patient profiles
2. Generate QR codes for quick access
3. Family members can view assigned patients

## Development

### Code Quality

Run static analysis:
```bash
vendor/bin/phpstan analyse
```

Check coding standards:
```bash
vendor/bin/phpcs
```

Fix coding standards:
```bash
vendor/bin/phpcbf
```

### Testing

Run unit tests:
```bash
vendor/bin/phpunit
```

Run specific test:
```bash
vendor/bin/phpunit tests/Unit/Services/IpAddressServiceTest.php
```

### Debugging

Logs are stored in `logs/app.log`:
```bash
tail -f logs/app.log
```

## Security

- All user inputs are validated and sanitized
- CSRF protection on all forms
- Password hashing with `PASSWORD_DEFAULT`
- SQL injection prevention via prepared statements
- Session security with regeneration
- Rate limiting on sensitive endpoints (optional middleware available)

## Performance

- Database indexes on frequently queried columns
- Query result caching (CacheService available)
- OPcache enabled in production
- Minified assets

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed deployment instructions.

Quick production checklist:
- Set `APP_ENV=production`
- Use strong database credentials
- Enable HTTPS
- Run database indexes
- Clear caches
- Set proper file permissions

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidelines.

## Support

For issues or questions:
- Submit via in-app Support page
- Email: support@knowmypatient.com
- Check logs: `logs/app.log`

## License

[Add your license here]

## Changelog

### Recent Updates

- ✅ Fixed modal flickering in admin tables
- ✅ Resolved CSRF validation failures
- ✅ Added IP address detection service
- ✅ Enhanced onboarding form validation
- ✅ Filtered PHP-DI deprecation warnings
- ✅ Database IP address cleanup

## Credits

Built with ❤️ for healthcare professionals.
