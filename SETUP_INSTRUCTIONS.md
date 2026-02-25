# Setup Instructions for Windows

## Quick Setup with XAMPP (Recommended)

### 1. Download and Install XAMPP
1. Go to https://www.apachefriends.org/
2. Download XAMPP for Windows (includes PHP, MySQL, Apache)
3. Run the installer
4. Install to `C:\xampp` (default)

### 2. Move Your Project
1. Copy your entire project folder to: `C:\xampp\htdocs\mineteh\`
2. Your files should be at: `C:\xampp\htdocs\mineteh\login.php`, etc.

### 3. Start XAMPP
1. Open XAMPP Control Panel
2. Click "Start" next to Apache
3. Apache should turn green

### 4. Test Supabase Connection
1. Make sure you've updated `database/supabase.php` with your Supabase credentials
2. Open browser: `http://localhost/mineteh/test_supabase.php`

### 5. Access Your App
- Login page: `http://localhost/mineteh/login.php`
- Homepage: `http://localhost/mineteh/home/homepage.php`

---

## Alternative: Standalone PHP Installation

### 1. Download PHP
1. Go to https://windows.php.net/download/
2. Download "VS16 x64 Thread Safe" ZIP
3. Extract to `C:\php`

### 2. Add PHP to PATH
1. Press `Win + X`, select "System"
2. Click "Advanced system settings"
3. Click "Environment Variables"
4. Under "System variables", find "Path"
5. Click "Edit" → "New"
6. Add: `C:\php`
7. Click OK on all windows
8. **Restart your terminal/PowerShell**

### 3. Test PHP Installation
Open new PowerShell and run:
```powershell
php -v
```

You should see PHP version info.

### 4. Start PHP Server
In your project directory:
```powershell
php -S localhost:8000
```

### 5. Test Your App
Open browser: `http://localhost:8000/test_supabase.php`

---

## Troubleshooting

### "php is not recognized"
- Make sure you restarted PowerShell after adding to PATH
- Or use full path: `C:\php\php.exe -S localhost:8000`
- Or use XAMPP instead (easier)

### "Call to undefined function curl_init"
Edit `C:\php\php.ini`:
1. Find line: `;extension=curl`
2. Remove the `;` to make it: `extension=curl`
3. Restart PHP server

### Port 8000 already in use
Try a different port:
```powershell
php -S localhost:8080
```

### XAMPP Apache won't start
- Port 80 might be in use
- Stop IIS or Skype
- Or change Apache port in XAMPP config

---

## What You Need Before Testing

1. ✅ PHP installed (via XAMPP or standalone)
2. ✅ Supabase project created
3. ✅ Credentials updated in `database/supabase.php`
4. ✅ SQL schema run in Supabase
5. ✅ Web server running (Apache or `php -S`)

---

## Quick Test Checklist

Once server is running:

1. **Test Supabase**: `http://localhost/mineteh/test_supabase.php`
   - Should see all green checkmarks ✅

2. **Test Signup**: `http://localhost/mineteh/login.php`
   - Click "Sign up"
   - Create a test account
   - Should redirect to homepage

3. **Test Login**: 
   - Login with your test account
   - Should see homepage

4. **Test Create Listing**:
   - Go to create listing page
   - Fill in details
   - Upload images
   - Submit

5. **Check Supabase**:
   - Go to Supabase dashboard
   - Table Editor → accounts
   - You should see your test user
   - Check listings table for your listing

---

## Need Help?

If you get stuck:
1. Check what error message you see
2. Check browser console (F12) for JavaScript errors
3. Check PHP errors in XAMPP logs: `C:\xampp\apache\logs\error.log`
4. Make sure Supabase credentials are correct
