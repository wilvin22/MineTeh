# API Folder Issue - InfinityFree Blocking

## Problem
InfinityFree (and many free hosting providers) block the `api` folder name as it's a reserved keyword. This causes 404 errors when trying to access any files in the `api/` directory.

## Solution
Renamed `api/` folder to `actions/` folder.

## Files That Need Updating

### Already Updated:
- ✅ `admin/riders.php` - Changed to use `../actions/admin-rider-action.php`

### Need Manual Update (if you use these features):
1. **Rider Dashboard Files:**
   - `rider/dashboard.php` - Update API calls
   - `rider/proof-of-delivery.php` - Update API calls
   - `rider/delivery-details.php` - Update API calls

2. **Other Admin Files:**
   - Any other admin pages that call API endpoints

3. **Home/User Files:**
   - `home/create-listing.php` - Update upload-images.php path
   - `home/listing-details.php` - Update place-bid.php path
   - `home/cart.php` - Update cart-action.php path
   - `home/saved-items.php` - Update favorite-action.php path
   - Any other files calling API endpoints

## Quick Find & Replace

Search for: `api/`
Replace with: `actions/`

**Files to check:**
```
admin/*.php
home/*.php
rider/*.php
```

## Testing

After renaming, test these URLs:
- ✅ https://mineteh.infinityfreeapp.com/actions/test-simple.php (should work now)
- ✅ https://mineteh.infinityfreeapp.com/actions/admin-rider-action.php (should work now)

## Alternative Solutions (if renaming doesn't work)

1. **Move API logic into the same folder:**
   - Put `admin-rider-action.php` directly in `admin/` folder
   - Update path to just `admin-rider-action.php`

2. **Use a different folder name:**
   - Try `endpoints/`, `handlers/`, `backend/`, or `ajax/`

## Note for Future Development

When deploying to InfinityFree or similar free hosts:
- Avoid folder names: `api`, `admin` (sometimes), `system`, `server`
- Use alternative names like `actions`, `handlers`, `endpoints`
- Test folder accessibility before building features around them
