# Know My Patient

A secure, role-based patient management platform built with Slim Framework 4, Twig, and PHP-DI.

## Features

- **Role-Based Access Control**: Admin, NHS user, Patient, and Family roles, each with custom dashboards and permissions.
- **Modern UI**: Responsive, accessible design using Twig templates and Bootstrap icons.
- **Security Best Practices**:
  - CSRF protection on all forms
  - Secure session cookies (`httponly`, `secure`, `samesite`)
  - Prepared statements for all database queries
  - Custom error pages (403, 404, 500)
- **Logging**: Monolog integration for error and action logging.
- **Environment Management**: Uses `.env` for configuration (via `vlucas/phpdotenv`).
- **Dependency Injection**: Clean, testable code with PHP-DI.
- **Twig Caching**: Enabled for production performance.

## Main Components

- `app/routes.php`: All route definitions, grouped and protected by middleware.
- `src/Application/Middleware/`: Custom middleware for authentication and role checks.
- `src/Application/Actions/`: Controllers for dashboard, admin, and user actions.
- `templates/`: Twig templates for all pages, with role-based logic for UI sections.
- `public/`: Entry point (`index.php`), static assets (CSS, JS, images).

## How It Works

- **Authentication**: Users log in and are assigned a role. Middleware enforces access to routes based on role.
- **Dashboards**: Each role sees a tailored dashboard. Template logic ensures only relevant sections are visible.
- **Admin Section**: All `/admin` routes are protected by `AdminOnlyMiddleware` and hidden from non-admins in the UI.
- **Session Security**: Sessions are protected and validated on every request.

## Setup

1. Clone the repo and run `composer install`.
2. Copy `.env.example` to `.env` and set your environment variables.
3. Set up your database and update credentials in `.env`.
4. Run the app with your preferred PHP server or via Docker Compose.

## Development Notes

- All new features should use prepared statements and be protected by appropriate middleware.
- Add new roles by creating middleware, updating routes, and adding template logic.
- Keep documentation and code comments up to date.

## License

MIT License. See LICENSE file for details.

## Automated Dependency Security Checks

You can automate Composer security checks using a simple script or by adding a GitHub Actions workflow.

### Local Script (optional)
Add this to your CI/CD pipeline or run manually:

```
composer install
composer audit
```

### GitHub Actions Example
Create a file at `.github/workflows/composer-audit.yml`:

```yaml
name: Composer Security Audit
on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install Composer dependencies
        run: composer install --no-interaction --no-progress
      - name: Run Composer Audit
        run: composer audit
```

This will automatically check for dependency vulnerabilities on every push and pull request.
