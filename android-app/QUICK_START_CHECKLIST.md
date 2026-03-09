# Quick Start Checklist (30 Minutes)

## ☐ Step 1: Create Project (5 min)
- [ ] Open Android Studio
- [ ] New Project → Empty Activity
- [ ] Name: MineTeh
- [ ] Package: com.example.mineteh
- [ ] Language: Kotlin
- [ ] Min SDK: API 24

## ☐ Step 2: Copy Files (5 min)
- [ ] Copy all `.kt` files to `app/src/main/java/com/example/mineteh/`
- [ ] Copy all `.xml` layout files to `app/src/main/res/layout/`
- [ ] Copy resource files to `app/src/main/res/`
- [ ] Replace `build.gradle` (app level)
- [ ] Replace `AndroidManifest.xml`

## ☐ Step 3: Add Logo (2 min)
- [ ] Add `logo.png` to `app/src/main/res/drawable/`

## ☐ Step 4: Sync & Build (5 min)
- [ ] Click "Sync Now" in Android Studio
- [ ] Wait for Gradle sync to complete
- [ ] Resolve any errors

## ☐ Step 5: Test on Device (10 min)
- [ ] Connect device or start emulator
- [ ] Click Run button
- [ ] Test splash screen
- [ ] Test login
- [ ] Test WebView loading
- [ ] Test navigation

## ☐ Step 6: Build APK (3 min)
- [ ] Build → Generate Signed Bundle / APK
- [ ] Create keystore (save it!)
- [ ] Build release APK
- [ ] Test APK on device

## Done! 🎉

Your Android app is ready with all MineTeh features!

## Quick Fixes

**If login fails:**
- Check API URL in `ApiClient.kt`
- Verify internet connection
- Check Logcat for errors

**If WebView is blank:**
- Check website URL in `MainActivity.kt`
- Test website in device browser
- Check internet permissions in manifest

**If app crashes:**
- Check Logcat for stack trace
- Verify all files are in correct locations
- Clean and rebuild project
