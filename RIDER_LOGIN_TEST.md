# Rider Login Test Guide

## What Was Changed

Updated `login.php` to redirect riders to their dashboard after successful login.

### Changes Made:
1. Added `$_SESSION['is_rider']` to store rider status in session
2. Added rider check in login redirect logic
3. Riders now redirect to `rider/dashboard.php` instead of `home/homepage.php`

## Login Flow

### For Regular Users:
- Login → `home/homepage.php`

### For Admins:
- Login → `admin-dashboard.php`

### For Riders:
- Login → `rider/dashboard.php`

## How to Test

### Step 1: Create a Rider Account
1. Login as admin at `admin/index.php`
2. Go to "Riders" in the navigation
3. Click "Add New Rider"
4. Either:
   - **Option A**: Create a new account with rider privileges
   - **Option B**: Convert an existing user to a rider

### Step 2: Test Rider Login
1. Logout from admin
2. Go to `login.php`
3. Login with the rider credentials
4. **Expected Result**: Should redirect to `rider/dashboard.php`
5. **Verify**: You should see the rider dashboard with:
   - Statistics (Total Deliveries, Completed, Pending, Earnings)
   - Active Deliveries section
   - Recent Completed Deliveries section

### Step 3: Verify Session
The rider session should have:
- `$_SESSION['user_id']` - The rider's account ID
- `$_SESSION['username']` - The rider's username
- `$_SESSION['is_admin']` - FALSE
- `$_SESSION['is_rider']` - TRUE
- `$_SESSION['user_status']` - 'active'

## Troubleshooting

### Rider redirects to homepage instead of dashboard
- Check if `is_rider` column is set to TRUE in the database
- Verify the rider was created properly via `admin/riders.php`
- Check browser console for any JavaScript errors

### "Access Denied" on rider dashboard
- Verify `rider/dashboard.php` has proper session checks
- Make sure `$_SESSION['is_rider']` is TRUE

### Login fails
- Check database connection
- Verify rider account exists in `accounts` table
- Check if `is_rider` column exists (run `add_rider_system_tables.sql` if needed)

## Next Steps

After successful rider login:
1. Admin should create a test delivery via `admin/orders.php`
2. Rider can view the delivery on their dashboard
3. Rider can update delivery status
4. Rider can submit proof of delivery with photo and signature
5. Rider can view their earnings

## Database Schema

The `accounts` table should have:
- `account_id` (primary key)
- `username`
- `email`
- `password_hash`
- `is_admin` (boolean)
- `is_rider` (boolean) ← NEW COLUMN
- `user_status` ('active', 'restricted', 'banned')

The `riders` table should have:
- `rider_id` (primary key)
- `account_id` (foreign key to accounts)
- `vehicle_type`
- `license_number`
- `phone_number`
- `status` ('active', 'inactive', 'suspended')
- `created_at`
