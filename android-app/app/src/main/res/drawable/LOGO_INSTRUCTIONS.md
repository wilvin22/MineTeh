# Logo Instructions

## Required Logo File

You need to add your MineTeh logo to this directory.

### File Requirements:
- **Filename**: `logo.png`
- **Location**: Place it in this `drawable` folder
- **Format**: PNG with transparent background (recommended)
- **Size**: 512x512 pixels (recommended)
- **Alternative sizes**: 
  - 192x192 (minimum)
  - 1024x1024 (maximum)

### How to Add:

1. Prepare your logo image (PNG format)
2. Rename it to `logo.png`
3. Copy it to: `app/src/main/res/drawable/logo.png`

### If You Don't Have a Logo Yet:

You can use a temporary placeholder:
1. Create a simple colored square in any image editor
2. Add text "MineTeh" in the center
3. Save as `logo.png`
4. Replace it later with your actual logo

### Alternative:

If you want to use a different filename:
1. Name your logo file (e.g., `mineteh_logo.png`)
2. Update references in:
   - `activity_splash.xml` (line with `android:src="@drawable/logo"`)
   - `activity_login.xml` (line with `android:src="@drawable/logo"`)
   - Change `@drawable/logo` to `@drawable/mineteh_logo`

### App Icon:

For the app icon (launcher icon), you should also update:
- `app/src/main/res/mipmap-*/ic_launcher.png`
- Use Android Studio's Image Asset tool: Right-click `res` → New → Image Asset
