# Rider Registration & Account Creation Guide

## Overview
Complete guide for creating and managing rider accounts in the MineTeh delivery system.

## Registration Methods

### 1. Admin Registration (Recommended)
**Best for**: Initial setup, controlled onboarding, converting existing users

**Access**: `admin/riders.php`

**Steps**:
1. Login as admin
2. Go to Admin Dashboard → Riders
3. Click "Add New Rider" button
4. Choose registration method:
   - **Option A**: Select existing account from dropdown
   - **Option B**: Create new account (fill all fields)
5. Fill rider information:
   - Full Name (as on license)
   - Phone Number
   - Vehicle Type (motorcycle, car, bicycle, van, truck)
   - License Number
   - Status (active, inactive, suspended)
6. Click "Save Rider"

**Features**:
- Convert existing users to riders
- Create new accounts with rider privileges
- Edit rider information
- Activate/deactivate riders
- View rider statistics
- Manage rider status

### 2. Self-Registration (Public)
**Best for**: Open rider recruitment, self-service onboarding

**Access**: `rider/register.php`

**Steps**:
1. Visit the rider registration page
2. Fill in account information:
   - Username (unique)
   - Email (unique)
   - Password (min 6 characters)
   - Confirm Password
   - First Name
   - Last Name
3. Fill in rider information:
   - Full Name (as on license)
   - Phone Number (09XXXXXXXXX format)
   - Vehicle Type
   - License Number (optional)
4. Click "Register as Rider"
5. Automatically logged in and redirected to dashboard

**Features**:
- Self-service registration
- Automatic account creation
- Auto-login after registration
- Email and username uniqueness validation
- Password confirmation

### 3. Manual SQL Registration (Testing)
**Best for**: Testing, database seeding, bulk imports

**Steps**:
```sql
-- Step 1: Create or update account
INSERT INTO accounts (username, email, password_hash, first_name, last_name, is_rider, is_admin)
VALUES ('rider1', 'rider1@example.com', '$2y$10$...', 'John', 'Doe', TRUE, FALSE);

-- Or update existing account
UPDATE accounts SET is_rider = TRUE WHERE account_id = 1;

-- Step 2: Create rider profile
INSERT INTO riders (account_id, full_name, phone_number, vehicle_type, license_number, status)
VALUES (1, 'John Doe', '09123456789', 'motorcycle', 'ABC-123-456', 'active');
```

## Account Structure

### Accounts Table
- `account_id` - Primary key
- `username` - Unique username
- `email` - Unique email
- `password_hash` - Hashed password
- `first_name` - User's first name
- `last_name` - User's last name
- `is_rider` - Boolean flag (TRUE for riders)
- `is_admin` - Boolean flag (FALSE for riders)

### Riders Table
- `rider_id` - Primary key
- `account_id` - Foreign key to accounts
- `full_name` - Full name as on license
- `phone_number` - Contact number
- `vehicle_type` - Type of vehicle
- `license_number` - Driver's license number
- `status` - active, inactive, or suspended
- `rating` - Average rating (default 5.00)
- `total_deliveries` - Completed delivery count

## Rider Status Types

1. **Active** - Can receive and complete deliveries
2. **Inactive** - Cannot receive new deliveries (temporary)
3. **Suspended** - Account suspended (disciplinary action)

## Admin Management Features

### View All Riders
- List of all registered riders
- Statistics: Total riders, active riders, total deliveries
- Sortable and searchable table

### Add New Rider
- Create new account or use existing
- Set initial status
- Assign vehicle type and license

### Edit Rider
- Update rider information
- Change vehicle type
- Update license number
- Modify status

### Status Management
- Quick activate/deactivate buttons
- Suspend riders when needed
- Track status changes

## Access URLs

- **Admin Management**: `https://yourdomain.com/admin/riders.php`
- **Self Registration**: `https://yourdomain.com/rider/register.php`
- **Rider Dashboard**: `https://yourdomain.com/rider/dashboard.php`
- **Rider Login**: `https://yourdomain.com/login.php` (use rider credentials)

## Security Features

### Password Security
- Minimum 6 characters required
- Hashed using PHP `password_hash()` with bcrypt
- Password confirmation on registration

### Account Validation
- Username uniqueness check
- Email uniqueness check
- Required field validation
- Phone number format validation

### Access Control
- Admin-only access to rider management
- Rider-only access to delivery dashboard
- Session-based authentication
- Authorization checks on all pages

## Integration with Existing System

### Login System
Riders use the same login page as regular users:
- URL: `login.php`
- After login, check `is_rider` flag
- Redirect to `rider/dashboard.php` if rider
- Redirect to `home/homepage.php` if regular user

### Session Variables
```php
$_SESSION['user_id']    // Account ID
$_SESSION['username']   // Username
$_SESSION['is_admin']   // Admin flag (optional)
```

### Checking if User is Rider
```php
$rider = $supabase->select('riders', '*', ['account_id' => $_SESSION['user_id']], true);
if ($rider) {
    // User is a rider
    // Access rider_id: $rider['rider_id']
}
```

## Common Tasks

### Convert Existing User to Rider
1. Go to Admin → Riders
2. Click "Add New Rider"
3. Select user from "Select Existing Account" dropdown
4. Fill rider details
5. Save

### Deactivate Rider
1. Go to Admin → Riders
2. Find rider in list
3. Click "Deactivate" button
4. Confirm action

### Reset Rider Password
1. Go to Admin → Users
2. Find rider's account
3. Use password reset feature
4. Or manually update in database:
```sql
UPDATE accounts 
SET password_hash = '$2y$10$...' 
WHERE account_id = 1;
```

## Troubleshooting

### Rider Can't Login
- Check if account exists in `accounts` table
- Verify `is_rider = TRUE`
- Check if rider profile exists in `riders` table
- Verify password is correct

### Registration Fails
- Check for duplicate username/email
- Verify all required fields filled
- Check database connection
- Review PHP error logs

### Can't Access Rider Dashboard
- Verify user is logged in
- Check if rider profile exists
- Verify `account_id` matches in both tables
- Check rider status is not suspended

## Best Practices

1. **Always use admin registration** for initial riders
2. **Enable self-registration** only when ready for public recruitment
3. **Verify license numbers** before activating riders
4. **Set status to inactive** for new riders until verified
5. **Regular audits** of rider accounts and performance
6. **Backup database** before bulk operations

## Next Steps

After creating rider accounts:
1. Assign deliveries to riders (see admin/orders.php)
2. Monitor rider performance (see admin/riders.php)
3. Process rider earnings (see rider_earnings table)
4. Handle customer ratings and feedback

---

**Created**: <?php echo date('Y-m-d'); ?>
**Version**: 1.0
**Status**: ✅ Fully Functional
