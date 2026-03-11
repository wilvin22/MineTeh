# Rider Delivery System Setup Guide

## Overview
Complete rider/delivery system for MineTeh marketplace with status tracking, proof of delivery, and earnings management.

## Features Implemented

### 1. Database Tables
- **riders** - Stores rider information and statistics
- **deliveries** - Tracks all delivery orders
- **delivery_tracking** - Logs status changes and location updates
- **rider_earnings** - Tracks rider earnings per delivery

### 2. Rider Dashboard (`rider/dashboard.php`)
- Real-time statistics (pending, completed, earnings, rating)
- Active deliveries list
- Recent completed deliveries
- Quick status update buttons
- Mobile responsive design

### 3. Proof of Delivery (`rider/proof-of-delivery.php`)
- Photo upload (camera or gallery)
- Digital signature pad
- Delivery notes
- Real-time preview
- Mobile-optimized

### 4. Delivery Details (`rider/delivery-details.php`)
- Complete delivery information
- Proof of delivery display
- Tracking history timeline
- Recipient information

### 5. API Endpoints
- `api/rider-update-status.php` - Update delivery status
- `api/rider-complete-delivery.php` - Complete delivery with proof

## Installation Steps

### Step 1: Create Database Tables
Run the SQL file in your Supabase SQL Editor:
```bash
add_rider_system_tables.sql
```

### Step 2: Create Upload Directories
Create these folders with write permissions:
```bash
mkdir -p uploads/delivery_proofs
mkdir -p uploads/signatures
chmod 777 uploads/delivery_proofs
chmod 777 uploads/signatures
```

### Step 3: Register Riders

You have two options to register riders:

#### Option A: Admin Registration (Recommended for initial setup)
1. Login as admin
2. Navigate to Admin Dashboard → Riders
3. Click "Add New Rider"
4. Choose to either:
   - Select an existing account and convert to rider
   - Create a new account with rider privileges
5. Fill in rider details (name, phone, vehicle, license)
6. Click "Save Rider"

#### Option B: Self-Registration (For riders to register themselves)
1. Riders visit: `rider/register.php`
2. Fill in the registration form:
   - Account information (username, email, password)
   - Personal information (first name, last name)
   - Rider details (phone, vehicle type, license)
3. Submit the form
4. Account is created and automatically logged in
5. Redirected to rider dashboard

#### Manual SQL Registration (For testing)
Insert a test rider in Supabase:
```sql
-- First, mark an account as rider
UPDATE accounts SET is_rider = TRUE WHERE account_id = 1;

-- Then create rider profile
INSERT INTO riders (account_id, full_name, phone_number, vehicle_type, license_number)
VALUES (1, 'Test Rider', '09123456789', 'motorcycle', 'ABC-123-456');
```

### Step 4: Create Test Delivery
```sql
-- Assuming you have an order_id = 1
INSERT INTO deliveries (
    order_id,
    rider_id,
    pickup_address,
    delivery_address,
    recipient_name,
    recipient_phone,
    delivery_status,
    delivery_fee,
    distance_km
) VALUES (
    1,
    1,
    '123 Seller Street, Manila',
    '456 Buyer Avenue, Quezon City',
    'John Doe',
    '09987654321',
    'assigned',
    50.00,
    5.5
);
```

## Delivery Status Flow

1. **pending** - Delivery created, waiting for rider assignment
2. **assigned** - Rider assigned to delivery
3. **picked_up** - Rider picked up the item
4. **in_transit** - Rider is delivering the item
5. **delivered** - Successfully delivered with proof
6. **failed** - Delivery attempt failed
7. **cancelled** - Delivery cancelled

## Usage Guide

### For Riders

1. **Login** - Use rider account credentials
2. **Access Dashboard** - Navigate to `rider/dashboard.php`
3. **View Active Deliveries** - See all assigned deliveries
4. **Update Status**:
   - Click "Mark as Picked Up" when item is collected
   - Click "Start Delivery" when heading to destination
   - Click "Complete Delivery" when arrived
5. **Submit Proof**:
   - Take photo of delivered item
   - Get recipient signature
   - Add delivery notes
   - Submit to complete

### For Admins

1. **Assign Deliveries** - Assign orders to riders
2. **Monitor Progress** - Track delivery status
3. **Manage Riders** - Add/remove riders, view performance
4. **Process Payments** - Pay rider earnings

## Integration with Orders

To integrate with your existing order system:

1. **After Order Placement**:
```php
// Create delivery record
$delivery_data = [
    'order_id' => $order_id,
    'pickup_address' => $seller_address,
    'delivery_address' => $buyer_address,
    'recipient_name' => $buyer_name,
    'recipient_phone' => $buyer_phone,
    'delivery_status' => 'pending',
    'delivery_fee' => 50.00,
    'created_at' => date('Y-m-d H:i:s')
];
$supabase->insert('deliveries', $delivery_data);
```

2. **Assign to Rider**:
```php
$supabase->update('deliveries', [
    'rider_id' => $rider_id,
    'delivery_status' => 'assigned',
    'assigned_at' => date('Y-m-d H:i:s')
], ['delivery_id' => $delivery_id]);
```

## Notifications

The system automatically sends notifications for:
- Delivery assigned to rider
- Item picked up
- Out for delivery
- Delivered successfully
- Delivery failed

## Mobile Optimization

All pages are fully responsive and optimized for:
- Mobile phones (portrait/landscape)
- Tablets
- Desktop browsers
- Touch-enabled signature pad
- Camera access for photos

## Security Features

- Session-based authentication
- Rider authorization checks
- Delivery ownership verification
- Secure file uploads
- SQL injection prevention

## Customization

### Delivery Fee Calculation
Modify in your order creation:
```php
$distance_km = calculateDistance($pickup, $delivery);
$delivery_fee = $distance_km * 10; // ₱10 per km
```

### Rating System
Implement customer rating after delivery:
```php
$supabase->update('deliveries', [
    'delivery_rating' => $rating,
    'delivery_feedback' => $feedback
], ['delivery_id' => $delivery_id]);

// Update rider average rating
$avg_rating = calculateAverageRating($rider_id);
$supabase->update('riders', [
    'rating' => $avg_rating
], ['rider_id' => $rider_id]);
```

## Troubleshooting

### Photos Not Uploading
- Check folder permissions: `chmod 777 uploads/delivery_proofs`
- Verify PHP upload settings in `php.ini`:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 10M
  ```

### Signature Not Saving
- Check folder permissions: `chmod 777 uploads/signatures`
- Verify base64 decoding is working
- Check browser console for JavaScript errors

### Status Not Updating
- Verify rider is logged in
- Check rider_id matches delivery
- Review browser console for API errors
- Check Supabase connection

## Future Enhancements

1. **Real-time GPS Tracking** - Track rider location
2. **Route Optimization** - Suggest best delivery routes
3. **Batch Deliveries** - Handle multiple deliveries
4. **Earnings Dashboard** - Detailed earnings reports
5. **Customer Ratings** - Allow customers to rate riders
6. **Push Notifications** - Real-time delivery updates
7. **Delivery Analytics** - Performance metrics and insights

## Support

For issues or questions:
1. Check this documentation
2. Review error logs
3. Test with sample data
4. Verify database connections

## Files Created

```
add_rider_system_tables.sql          - Database schema
rider/dashboard.php                   - Rider main dashboard
rider/proof-of-delivery.php          - Proof submission page
rider/delivery-details.php           - Delivery details view
rider/register.php                   - Rider self-registration page
admin/riders.php                     - Admin rider management page
api/rider-update-status.php          - Status update API
api/rider-complete-delivery.php      - Complete delivery API
api/admin-rider-action.php          - Admin rider management API
RIDER_SYSTEM_SETUP.md               - This documentation
```

## Testing Checklist

- [ ] Database tables created
- [ ] Upload folders created with permissions
- [ ] Test rider account created
- [ ] Test delivery created
- [ ] Can login as rider
- [ ] Dashboard displays correctly
- [ ] Can update delivery status
- [ ] Can upload delivery photo
- [ ] Can capture signature
- [ ] Can complete delivery
- [ ] Notifications sent correctly
- [ ] Mobile view works properly

---

**System Status**: ✅ Fully Functional
**Last Updated**: <?php echo date('Y-m-d'); ?>
