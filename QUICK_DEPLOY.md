# Quick Deployment Guide

## For Localhost (Current Setup)

Your project is already configured for localhost. No changes needed!

## For Production Deployment

### Option 1: Quick Deploy (5 minutes)

1. **Upload files to your web server**
   - Upload all files via FTP/SFTP to your hosting
   - Or use Git: `git clone your-repo-url`

2. **Update one line in config.php**
   ```php
   // Line 13 in config.php
   define('BASE_URL', 'https://yourdomain.com'); // Change this!
   ```

3. **Done!** Your site is live at `https://yourdomain.com`

### Option 2: Professional Deploy (with environment variables)

1. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

2. **Edit .env file**
   ```env
   ENVIRONMENT=production
   PRODUCTION_URL=https://yourdomain.com
   ```

3. **Upload and test**

## For Android App

Update the API URL in `ApiClient.kt`:

```kotlin
// For localhost testing
private const val BASE_URL = "http://10.0.2.2/MineTeh/api/v1/"

// For production
private const val BASE_URL = "https://yourdomain.com/api/v1/"
```

## Common Hosting Providers

### Hostinger / Namecheap / GoDaddy
1. Upload files to `public_html/` folder
2. Update `config.php` with your domain
3. Done!

### DigitalOcean / AWS / VPS
1. Follow DEPLOYMENT_GUIDE.md for full setup
2. Configure Nginx/Apache
3. Set up SSL certificate

## Need Help?

- Full guide: See `DEPLOYMENT_GUIDE.md`
- API docs: See `API_SETUP_GUIDE.md`
- Database: Already using Supabase (cloud-based, no changes needed!)

## What's Already Cloud-Ready?

✅ Database (Supabase)
✅ All PHP code uses relative paths
✅ Session management
✅ File uploads

## What Needs Updating for Production?

⚠️ `config.php` - Update BASE_URL
⚠️ Android app - Update API URL
⚠️ Enable HTTPS (recommended)

That's it! Your project is designed to work anywhere.
