# MineTeh Android App

Complete Android application for MineTeh marketplace using hybrid WebView approach.

## 📱 What This Is

A professional Android app that combines:
- Native splash screen with MineTeh branding
- Native login screen with beautiful UI
- WebView loading your full website
- All existing features work instantly (no rebuilding required)

## ⚡ Quick Start (30 Minutes)

See `QUICK_START_CHECKLIST.md` for step-by-step instructions.

## 📚 Documentation

- **FASTEST_SOLUTION.md** - Why this approach and what you get
- **COMPLETE_SETUP_GUIDE.md** - Detailed setup instructions
- **QUICK_START_CHECKLIST.md** - Quick checklist format

## 🏗️ Project Structure

```
android-app/
├── app/
│   ├── src/
│   │   └── main/
│   │       ├── java/com/example/mineteh/
│   │       │   ├── SplashActivity.kt          # Splash screen
│   │       │   ├── LoginActivity.kt           # Login screen
│   │       │   ├── MainActivity.kt            # Main WebView
│   │       │   ├── utils/
│   │       │   │   └── TokenManager.kt        # Session management
│   │       │   └── network/
│   │       │       ├── ApiClient.kt           # API calls
│   │       │       └── ApiService.kt          # API interface
│   │       ├── res/
│   │       │   ├── layout/                    # UI layouts
│   │       │   ├── values/                    # Colors, strings, themes
│   │       │   ├── drawable/                  # Images and backgrounds
│   │       │   ├── menu/                      # Menu items
│   │       │   └── xml/                       # Backup rules
│   │       └── AndroidManifest.xml            # App configuration
│   ├── build.gradle                           # App dependencies
│   └── proguard-rules.pro                     # ProGuard rules
├── build.gradle                               # Project config
├── settings.gradle                            # Project settings
└── gradle.properties                          # Gradle properties
```

## ✅ Features Included

### Native Features
- ✅ Professional splash screen
- ✅ Beautiful login UI
- ✅ Session management with tokens
- ✅ Pull-to-refresh
- ✅ Menu with logout
- ✅ Back button handling
- ✅ Network error handling

### Website Features (via WebView)
All your existing features work immediately:
- ✅ Browse listings
- ✅ Place bids
- ✅ Shopping cart
- ✅ Checkout
- ✅ Messages
- ✅ Notifications
- ✅ User profile
- ✅ Order history
- ✅ Everything else!

## 🔧 Configuration

### API Endpoint
Located in `app/src/main/java/com/example/mineteh/network/ApiClient.kt`:
```kotlin
private const val BASE_URL = "https://mineteh.infinityfreeapp.com/"
```

### Website URL
Located in `app/src/main/java/com/example/mineteh/MainActivity.kt`:
```kotlin
private val websiteUrl = "https://mineteh.infinityfreeapp.com/home/homepage.php"
```

### App Name & Colors
- App name: `app/src/main/res/values/strings.xml`
- Colors: `app/src/main/res/values/colors.xml`
- Theme: `app/src/main/res/values/themes.xml`

## 📦 Dependencies

All dependencies are managed in `app/build.gradle`:
- AndroidX Core & AppCompat
- Material Design Components
- WebView & SwipeRefreshLayout
- OkHttp for networking
- Retrofit for API calls
- Kotlin Coroutines
- Lifecycle components

## 🚀 Building the App

### Debug Build (for testing)
1. Connect device or start emulator
2. Click Run button in Android Studio
3. App installs and launches automatically

### Release Build (for distribution)
1. Go to `Build` → `Generate Signed Bundle / APK`
2. Select `APK`
3. Create/select keystore
4. Choose `release` variant
5. APK generated in `app/release/`

## 🎨 Customization

### Change Colors
Edit `app/src/main/res/values/colors.xml`:
```xml
<color name="primary">#FF6B35</color>
<color name="accent">#FFA500</color>
```

### Change Logo
1. Add your logo to `app/src/main/res/drawable/logo.png`
2. See `drawable/LOGO_INSTRUCTIONS.md` for details

### Change App Icon
1. Right-click `res` folder
2. New → Image Asset
3. Select your icon image
4. Generate all sizes

## 🔐 Security

- Session tokens stored securely in SharedPreferences
- HTTPS for all network communication
- Sensitive data excluded from backups
- ProGuard rules for code obfuscation

## 🐛 Troubleshooting

### Gradle Sync Failed
```bash
File → Invalidate Caches / Restart
```

### App Crashes
Check Logcat in Android Studio for error messages

### WebView Not Loading
1. Check internet connection
2. Verify website URL is correct
3. Test website in device browser

### Login Not Working
1. Verify API endpoint URL
2. Check API response format
3. Review Logcat for network errors

## 📱 Testing

### Test Checklist
- [ ] Splash screen displays correctly
- [ ] Login with valid credentials works
- [ ] Login with invalid credentials shows error
- [ ] WebView loads website
- [ ] All website features work
- [ ] Pull-to-refresh works
- [ ] Menu opens and logout works
- [ ] Back button navigation works
- [ ] App survives rotation
- [ ] App works on different screen sizes

## 📤 Deployment

### Google Play Store
1. Build release APK
2. Create Google Play Developer account ($25 one-time fee)
3. Create app listing
4. Upload APK
5. Fill in store listing details
6. Submit for review

### Alternative Distribution
- Direct APK download from your website
- Third-party app stores
- Enterprise distribution

## 🆘 Support

If you encounter issues:
1. Check the documentation files
2. Review Logcat for errors
3. Verify all files are in correct locations
4. Test on a real device (emulators can be unreliable)
5. Ensure Gradle sync completed successfully

## 📝 Notes

- Minimum Android version: 7.0 (API 24)
- Target Android version: 14 (API 34)
- Language: Kotlin
- Build system: Gradle

## 🎉 You're Ready!

Follow the QUICK_START_CHECKLIST.md to get your app running in 30 minutes!
