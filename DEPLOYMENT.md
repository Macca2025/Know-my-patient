# Deployment Checklist

## Pre-Deployment

### Security
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Generate strong `APP_SECRET` key
- [ ] Use strong database credentials
- [ ] Enable HTTPS/SSL certificate
- [ ] Configure CORS properly
- [ ] Review and update `.gitignore`
- [ ] Remove or secure `phpinfo()` pages

### Code Quality
- [ ] Run PHPStan: `vendor/bin/phpstan analyse`
- [ ] Run PHP_CodeSniffer: `vendor/bin/phpcs`
- [ ] Run PHPUnit tests: `vendor/bin/phpunit`
- [ ] Clear all `var_dump()` and `die()` statements
- [ ] Remove test files from production

### Database
- [ ] Run database migrations
- [ ] Create database indexes (see `database_indexes.sql`)
- [ ] Backup production database
- [ ] Test database connections

### Performance
- [ ] Enable OPcache in `php.ini`
- [ ] Configure caching (Redis/Memcached if available)
- [ ] Minify CSS/JS assets
- [ ] Optimize images
- [ ] Enable Gzip compression

### Logging & Monitoring
- [ ] Configure error logging to files (not display)
- [ ] Set up log rotation
- [ ] Configure monitoring (e.g., New Relic, Sentry)
- [ ] Test error reporting

## Deployment Steps

1. **Backup Current Production**
   ```bash
   # Backup database
   mysqldump -u user -p database_name > backup_$(date +%Y%m%d).sql
   
   # Backup files
   tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/app
   ```

2. **Update Code**
   ```bash
   git pull origin main
   composer install --no-dev --optimize-autoloader
   ```

3. **Update Database**
   ```bash
   mysql -u user -p database_name < database_indexes.sql
   ```

4. **Clear Caches**
   ```bash
   rm -rf var/cache/*
   rm -rf var/twig-cache/*
   ```

5. **Set Permissions**
   ```bash
   chmod -R 755 public/
   chmod -R 775 var/cache/ logs/
   chown -R www-data:www-data /path/to/app
   ```

6. **Verify**
   - [ ] Test homepage loads
   - [ ] Test login/logout
   - [ ] Test admin functions
   - [ ] Test database writes
   - [ ] Check error logs

## Post-Deployment

- [ ] Monitor error logs for 24 hours
- [ ] Test all critical user flows
- [ ] Verify email sending (if applicable)
- [ ] Check performance metrics
- [ ] Update documentation if needed

## Rollback Plan

If deployment fails:
```bash
# Restore database
mysql -u user -p database_name < backup_YYYYMMDD.sql

# Restore code
git reset --hard <previous-commit-hash>
composer install --no-dev
```

## Server Requirements

- PHP 8.1+
- MySQL 5.7+ or 8.0+
- Apache/Nginx with mod_rewrite
- Composer 2.x
- SSL Certificate
- Minimum 512MB RAM
- Recommended: Redis or Memcached

## Environment Variables (Production)

```env
APP_ENV=production
APP_DEBUG=false
DB_HOST=your_prod_host
DB_PORT=3306
DB_NAME=know_my_patient_prod
DB_USER=prod_user
DB_PASS=strong_password_here
```

## Nginx Configuration Example

```nginx
server {
    listen 80;
    server_name knowmypatient.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name knowmypatient.com;
    
    root /var/www/know_my_patient/public;
    index index.php;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Apache .htaccess (Already Configured)

Ensure `AllowOverride All` is set in Apache config.
