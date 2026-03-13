# MineTeh Deployment Guide

This guide will help you deploy MineTeh to a production server.

## Prerequisites

- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Supabase account (already configured)
- Domain name (for production)

## Deployment Steps

### 1. Prepare Your Server

Upload all project files to your web server via FTP, SFTP, or Git.

```bash
# If using Git
git clone your-repository-url
cd MineTeh
```

### 2. Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` and update:

```env
ENVIRONMENT=production
PRODUCTION_URL=https://yourdomain.com
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_KEY=your-anon-key-here
```

### 3. Update config.php

Open `config.php` and verify the `BASE_URL` for production:

```php
if (ENVIRONMENT === 'production') {
    define('BASE_URL', 'https://yourdomain.com');
}
```

### 4. Set File Permissions

```bash
# Make uploads directory writable
chmod 755 uploads/
chmod 644 uploads/.gitkeep

# Protect sensitive files
chmod 600 .env
chmod 644 config.php
chmod 644 database/supabase.php
```

### 5. Configure Web Server

#### Apache (.htaccess)

Create/update `.htaccess` in the root directory:

```apache
# Enable rewrite engine
RewriteEngine On

# Redirect to HTTPS (production only)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Protect sensitive files
<FilesMatch "^\.env$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Set default timezone
php_value date.timezone "Asia/Manila"

# Increase upload limits
php_value upload_max_filesize 10M
php_value post_max_size 10M
```

#### Nginx

Add to your Nginx configuration:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/MineTeh;
    index index.php index.html;

    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name yourdomain.com;
    root /path/to/MineTeh;
    index index.php index.html;

    # SSL configuration
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Protect sensitive files
    location ~ /\.env {
        deny all;
    }
}
```

### 6. Update Android App Configuration

Update the API URL in your Android app's `ApiClient.kt`:

```kotlin
private const val BASE_URL = "https://yourdomain.com/api/v1/"
```

Or use BuildConfig for different environments:

```kotlin
private val BASE_URL = if (BuildConfig.DEBUG) {
    "http://10.0.2.2/MineTeh/api/v1/" // Development
} else {
    "https://yourdomain.com/api/v1/" // Production
}
```

### 7. Test Your Deployment

1. Visit your domain: `https://yourdomain.com`
2. Test login functionality
3. Test listing creation
4. Test bidding functionality
5. Test image uploads
6. Test Android app connectivity

### 8. Security Checklist

- [ ] HTTPS enabled with valid SSL certificate
- [ ] `.env` file is not publicly accessible
- [ ] Error reporting disabled in production
- [ ] Supabase RLS policies configured
- [ ] File upload directory has proper permissions
- [ ] Session security configured
- [ ] CORS headers configured for API

## Troubleshooting

### Issue: "Page not found" errors

- Check that `config.php` has the correct `BASE_URL`
- Verify web server configuration
- Check file permissions

### Issue: Database connection errors

- Verify Supabase credentials in `database/supabase.php`
- Check that Supabase project is active
- Verify RLS policies are disabled or properly configured

### Issue: Image uploads not working

- Check `uploads/` directory permissions (755)
- Verify `upload_max_filesize` in PHP configuration
- Check disk space on server

### Issue: Android app can't connect

- Update `BASE_URL` in `ApiClient.kt`
- Verify API endpoints are accessible
- Check CORS configuration if needed

## Maintenance

### Backup Strategy

1. **Database**: Supabase handles automatic backups
2. **Uploaded Images**: Backup `uploads/` directory regularly
3. **Code**: Use Git for version control

### Monitoring

- Monitor error logs: `error_log` file
- Check Supabase dashboard for database performance
- Monitor server resources (CPU, memory, disk)

### Updates

```bash
# Pull latest changes
git pull origin main

# Clear any caches if needed
# Restart PHP-FPM if needed
sudo systemctl restart php7.4-fpm
```

## Support

For issues or questions, refer to:
- `README.md` for project overview
- `API_SETUP_GUIDE.md` for API documentation
- Supabase documentation: https://supabase.com/docs
