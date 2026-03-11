# PowerShell script to update all api/ references to actions/

Write-Host "Updating API paths to Actions paths..." -ForegroundColor Green

# List of files to update
$files = @(
    "rider/dashboard.php",
    "rider/proof-of-delivery.php",
    "home/cart.php",
    "home/create-listing.php",
    "home/homepage.php",
    "home/listing-details.php",
    "home/saved-items.php",
    "home/search.php",
    "home/your-listings.php",
    "debug_rider_system.php",
    "test_admin_riders_simple.php",
    "test_create_rider_direct.php"
)

$count = 0

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "Updating $file..." -ForegroundColor Yellow
        
        # Read file content
        $content = Get-Content $file -Raw
        
        # Replace api/ with actions/
        $newContent = $content -replace '([''"])(\.\./)api/', '$1$2actions/'
        $newContent = $newContent -replace '([''"])api/', '$1actions/'
        
        # Write back
        Set-Content $file -Value $newContent -NoNewline
        
        $count++
        Write-Host "  ✓ Updated" -ForegroundColor Green
    } else {
        Write-Host "  ✗ File not found: $file" -ForegroundColor Red
    }
}

Write-Host "`nTotal files updated: $count" -ForegroundColor Cyan
Write-Host "Done! All API paths have been changed to Actions paths." -ForegroundColor Green
