# Caching Implementation

## Overview
File-based caching has been implemented across the application to reduce database queries and improve performance for frequently accessed data.

## Cache Service
- **Location**: `src/Application/Services/CacheService.php`
- **Storage**: File-based in `var/cache/app_cache/`
- **Default TTL**: 3600 seconds (1 hour)

## Cached Items

### 1. Homepage Testimonials
- **Cache Key**: `testimonials_homepage`
- **TTL**: 3600 seconds (1 hour)
- **Location**: `HomeController::home()`
- **Purpose**: Reduce database load on the most frequently accessed page
- **Invalidation**: Automatically cleared when admin deletes a testimonial

### 2. User Roles
- **Cache Key**: `user_role_{user_id}`
- **TTL**: 900 seconds (15 minutes)
- **Location**: `AuthController::login()`
- **Purpose**: Reduce repeated role lookups during user session
- **Invalidation**: Automatically expires after 15 minutes

### 3. Admin Resources
- **Cache Key**: `admin_resources`
- **TTL**: 1800 seconds (30 minutes)
- **Location**: `AdminController::resources()`
- **Purpose**: Reduce database queries on admin resources page
- **Invalidation**: Manual (see Cache Management section)

## Cache Management

### Clear Specific Cache
```php
// In your controller
$this->cacheService->forget('cache_key_here');
```

### Clear All Cache
```php
// In your controller
$this->cacheService->flush();
```

### Clear Cache via Command Line
```bash
# Delete all cache files
rm -rf /Applications/MAMP/htdocs/know_my_patient/var/cache/app_cache/*
```

## Cache Invalidation Triggers

### Testimonials Cache
The testimonials cache is automatically cleared when:
- An admin deletes a testimonial via `AdminController::deleteTestimonial()`

**Future Improvements**: Add cache clearing when testimonials are:
- Created (via admin panel)
- Updated (via admin panel)

### Resources Cache
The resources cache should be manually cleared when:
- A new resource is uploaded
- A resource is updated
- A resource is deleted

**Implementation Example**:
```php
// After creating/updating/deleting a resource
$this->cacheService->forget('admin_resources');
```

### User Role Cache
User role cache automatically expires after 15 minutes. Consider clearing manually when:
- User role is changed by admin
- User permissions are modified

## Performance Benefits

### Before Caching
- Homepage: Query testimonials on every page load
- Admin resources: Query all resources on every view
- User roles: Potential repeated lookups per request

### After Caching
- Homepage: Query testimonials once per hour
- Admin resources: Query once per 30 minutes
- User roles: Store in cache for 15 minutes per user

### Expected Improvements
- **Homepage load time**: 20-30% faster (eliminates testimonials query)
- **Admin resources page**: 25-35% faster (eliminates resources query)
- **Session validation**: Minimal improvement (session already fast)

## Production Recommendations

### Upgrade to Redis or Memcached
For production environments with multiple web servers, consider upgrading to Redis or Memcached:

1. **Install Redis**:
```bash
# macOS
brew install redis
brew services start redis

# Ubuntu/Debian
sudo apt-get install redis-server
sudo systemctl start redis-server
```

2. **Update CacheService** to use Redis:
```php
// Use predis/predis or phpredis extension
$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);
```

### Cache Configuration
Current configuration (file-based) is suitable for:
- ✅ Development environments
- ✅ Single-server deployments
- ✅ Low to moderate traffic

Consider Redis/Memcached for:
- ⚠️ Multi-server deployments (load balanced)
- ⚠️ High traffic applications (>10,000 requests/day)
- ⚠️ Frequent cache invalidations

## Monitoring

### Check Cache Directory Size
```bash
du -sh /Applications/MAMP/htdocs/know_my_patient/var/cache/app_cache/
```

### List Cached Items
```bash
ls -lh /Applications/MAMP/htdocs/know_my_patient/var/cache/app_cache/
```

### View Cache File Contents
```bash
# Example: view testimonials cache
cat /Applications/MAMP/htdocs/know_my_patient/var/cache/app_cache/testimonials_homepage
```

## Troubleshooting

### Cache Not Working
1. Check directory permissions:
```bash
chmod 755 /Applications/MAMP/htdocs/know_my_patient/var/cache/app_cache/
```

2. Check web server has write access
3. Verify CacheService is registered in `app/dependencies.php`

### Stale Data Displayed
1. Clear specific cache: `$this->cacheService->forget('cache_key')`
2. Clear all cache: `$this->cacheService->flush()`
3. Check TTL values are appropriate

### Cache Directory Full
1. Implement cache cleanup cron job:
```bash
# Add to crontab: clear cache files older than 24 hours
0 2 * * * find /Applications/MAMP/htdocs/know_my_patient/var/cache/app_cache/ -type f -mtime +1 -delete
```

## Future Enhancements

1. **Database Query Caching**: Cache frequently used database queries
2. **Template Caching**: Enable Twig template caching
3. **API Response Caching**: Cache external API responses
4. **Opcache**: Enable PHP Opcache for production
5. **CDN Integration**: Use CDN for static assets
6. **Cache Warming**: Pre-populate cache on deployment
7. **Cache Tags**: Implement cache tagging for easier invalidation

## Testing

### Test Homepage Caching
1. Visit homepage and note testimonials displayed
2. Check cache file created: `var/cache/app_cache/testimonials_homepage`
3. Update database testimonials directly
4. Refresh homepage - old data shown (cached)
5. Wait 1 hour or clear cache
6. Refresh homepage - new data shown

### Test User Role Caching
1. Login as user
2. Check cache file: `var/cache/app_cache/user_role_{user_id}`
3. Logout and login again within 15 minutes
4. Role loaded from cache (faster login)

### Test Admin Resources Caching
1. Login as admin
2. Visit `/admin/resources`
3. Check cache file: `var/cache/app_cache/admin_resources`
4. Refresh page multiple times (same data, no DB query)
5. Clear cache: `$this->cacheService->forget('admin_resources')`
6. Refresh page - new query executed
