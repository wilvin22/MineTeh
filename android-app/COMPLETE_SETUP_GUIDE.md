# Complete Android App Setup Guide

## Prerequisites

1. **Android Studio** (latest version)
2. **JDK 11 or higher**
3. **Android device or emulator** (Android 7.0+)

## Step 1: Create New Android Studio Project

1. Open Android Studio
2. Click "New Project"
3. Select "Empty Activity"
4. Configure:
   - Name: `MineTeh`
   - Package name: `com.example.mineteh`
   - Save location: Choose your preferred location
   - Language: `Kotlin`
   - Minimum SDK: `API 24 (Android 7.0)`
5. Click "Finish"

## Step 2: Copy Project Files

Copy all files from this `android-app` directory to your Android Studio project:

### Java/Kotlin Files
Copy to `app/src/main/java/com/example/mineteh/`:
- `SplashActivity.kt`
- `LoginActivity.kt`
- `MainActivity.kt`
- `utils/TokenManager.kt`
- `network/ApiClient.kt`
- `network/ApiService.kt`

### Layout Files
Copy to `app/src/main/res/layout/`:
- `activity_splash.xml`
- `activity_login.xml`
- `activity_main.xml`

### Resource Files
Copy to `app/src/main/res/`:
- `values/colors.xml`
- `values/strings.xml`
- `values/themes.xml`
- `menu/main_menu.xml`
- `drawable/splash_background.xml`

### Configuration Files
- Replace `app/build.gradle` with the provided version
- Replace `app/src/main/AndroidManifest.xml` with the provided version

## Step 3: Add Logo Image

1. Place your MineTeh logo in `app/src/main/res/drawable/`
2. Name it `logo.png` (or update references in splash_background.xml)

## Step 4: Sync Gradle

1. Click "Sync Now" when prompted
2. Wait for Gradle sync to complete
3. Resolve any dependency issues if they appear

## Step 5: Update API Base URL (if needed)

In `app/src/main/java/com/example/mineteh/network/ApiClient.kt`:
```kotlin
private const val BASE_URL = "https://mineteh.infinityfreeapp.com/"
```

## Step 6: Run on Device

1. Connect Android device via USB (enable USB debugging)
   OR start Android emulator
2. Click the green "Run" button in Android Studio
3. Select your device
4. Wait for app to install and launch

## Step 7: Test the App

1. **Splash Screen**: Should show MineTeh logo for 2 seconds
2. **Login Screen**: Try logging in with your credentials
3. **Main Screen**: Should load your website in WebView
4. **Navigation**: Test menu and back button

## Step 8: Build Release APK

1. Go to `Build` > `Generate Signed Bundle / APK`
2. Select `APK`
3. Create a new keystore (save it securely!)
4. Fill in keystore details
5. Select `release` build variant
6. Click `Finish`
7. APK will be in `app/release/app-release.apk`

## Troubleshooting

### Gradle Sync Failed
- Update Android Studio to latest version
- Check internet connection
- Try `File` > `Invalidate Caches / Restart`

### App Crashes on Launch
- Check Logcat for error messages
- Verify all files are in correct directories
- Ensure package names match

### WebView Not Loading
- Check internet connection on device
- Verify BASE_URL is correct
- Check website is accessible from device browser

### Login Not Working
- Verify API endpoint: `https://mineteh.infinityfreeapp.com/api/v1/auth/login.php`
- Check API is returning correct response format
- Review Logcat for network errors

## Features Included

✅ Splash screen with logo
✅ Native login screen
✅ Session management with tokens
✅ WebView for main content
✅ Pull-to-refresh
✅ Menu with logout
✅ Back button handling
✅ Network error handling
✅ Automatic session persistence

## What Works Out of the Box

Since the app loads your website in a WebView, ALL your existing features work:
- Browse listings
- Place bids
- Add to cart
- Checkout
- Messages
- Notifications
- User profile
- Order history
- Everything else!

## Next Steps

- Customize colors in `colors.xml`
- Update app name in `strings.xml`
- Add your logo to `drawable/`
- Test thoroughly on different devices
- Build release APK
- Submit to Google Play Store

## Support

If you encounter issues:
1. Check Logcat in Android Studio
2. Verify all files are copied correctly
3. Ensure Gradle sync completed successfully
4. Test on a real device (emulators can be unreliable)
