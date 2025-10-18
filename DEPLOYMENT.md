# Stella AI - Production Deployment Guide

## 🚀 Performance Optimizations

### 1. OPcache Configuration
Add to your `php.ini` or server configuration:

```ini
; OPcache settings for production
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.enable_cli=1
opcache.validate_timestamps=0  ; Set to 0 in production
```

### 2. Database Optimizations
- Enable MySQL query cache
- Use connection pooling
- Optimize database indexes
- Enable slow query logging

### 3. Web Server Configuration

#### Apache (.htaccess)
```apache
# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Browser caching
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</IfModule>
```

#### Nginx
```nginx
# Enable gzip compression
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

# Browser caching
location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}

# Security headers
add_header X-Frame-Options DENY;
add_header X-Content-Type-Options nosniff;
add_header X-XSS-Protection "1; mode=block";
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";
```

## 🔒 Security Configuration

### 1. File Permissions
Set secure file permissions on your server:

```bash
# Core application files (read-only)
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type f -name "*.html" -exec chmod 644 {} \;
find . -type f -name "*.js" -exec chmod 644 {} \;
find . -type f -name "*.css" -exec chmod 644 {} \;

# Directories (read and execute)
find . -type d -exec chmod 755 {} \;

# Writable directories only
chmod 755 uploads/
chmod 755 cache/
chmod 755 logs/
chmod 755 public/

# Sensitive files (read-only, owner only)
chmod 600 config/database.php
chmod 600 config/production.php
```

### 2. Environment Variables
Set these environment variables on your server:

```bash
# Database
export DB_HOST="your-db-host"
export DB_DATABASE="your-db-name"
export DB_USERNAME="your-db-user"
export DB_PASSWORD="your-secure-password"

# Application
export APP_ENV="production"
export APP_DEBUG="false"
export APP_URL="https://your-domain.com"

# Security
export APP_KEY="your-32-character-secret-key"
export SESSION_LIFETIME="120"
```

### 3. Disable Debugging
Ensure all debugging is disabled in production:

```php
// In your main application files
define('APP_DEBUG', false);
define('APP_ENV', 'production');

// Disable error display
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
```

## 📁 Directory Structure
Ensure your production directory structure is secure:

```
stella-ai/
├── api/                    # API endpoints (644)
├── app/                    # Application logic (644)
├── config/                 # Configuration (600)
├── dashboard/              # Dashboard files (644)
├── database/               # Database files (644)
├── public/                 # Public assets (755)
├── uploads/                # User uploads (755)
├── cache/                  # Cache files (755)
├── logs/                   # Log files (755)
├── index.html              # Main page (644)
├── admin.html              # Admin panel (644)
└── .htaccess               # Apache config (644)
```

## 🔧 Production Checklist

### Before Deployment:
- [ ] Set `PRODUCTION_MODE = true` in `config/production.php`
- [ ] Configure OPcache in `php.ini`
- [ ] Set secure file permissions
- [ ] Configure environment variables
- [ ] Enable HTTPS/SSL
- [ ] Set up database backups
- [ ] Configure log rotation
- [ ] Test all API endpoints
- [ ] Verify security headers
- [ ] Test rate limiting

### After Deployment:
- [ ] Monitor error logs
- [ ] Check performance metrics
- [ ] Verify all features work
- [ ] Test API authentication
- [ ] Monitor database performance
- [ ] Check cache hit rates
- [ ] Verify backup procedures

## 🚨 Security Considerations

### 1. Database Security
- Use strong passwords
- Limit database user permissions
- Enable SSL for database connections
- Regular security updates
- Monitor for suspicious activity

### 2. API Security
- Rate limiting implemented
- API key authentication
- Input validation and sanitization
- SQL injection prevention
- XSS protection

### 3. File Upload Security
- Validate file types
- Scan for malware
- Limit file sizes
- Store uploads outside web root
- Regular cleanup of old files

### 4. Session Security
- Secure session cookies
- Session timeout
- CSRF protection
- Secure session storage

## 📊 Monitoring

### 1. Error Logging
Monitor these log files:
- `/logs/php_errors.log` - PHP errors
- `/logs/application.log` - Application logs
- Web server error logs
- Database slow query logs

### 2. Performance Monitoring
- Response times
- Database query performance
- Memory usage
- CPU usage
- Disk space

### 3. Security Monitoring
- Failed login attempts
- API rate limit violations
- Suspicious file uploads
- Database access patterns

## 🔄 Maintenance

### Regular Tasks:
- Update dependencies
- Review and rotate API keys
- Clean up old cache files
- Monitor disk space
- Review error logs
- Update security patches
- Test backup restoration

### Weekly:
- Review performance metrics
- Check for security updates
- Monitor user activity
- Review error logs

### Monthly:
- Full security audit
- Performance optimization review
- Backup testing
- Dependency updates
