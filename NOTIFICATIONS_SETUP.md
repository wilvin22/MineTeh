# Notifications System Setup Guide

## Overview
A complete notification system has been added to your MineTeh website. Users will receive notifications for:
- New bids on their listings
- Being outbid on auctions
- Listings being sold
- New messages
- Order updates

## Setup Steps

### 1. Create the Notifications Table
Run the SQL file to create the notifications table in your Supabase database:

```bash
# Execute the SQL in Supabase SQL Editor
cat add_notifications_table.sql
```

Or manually run the SQL commands from `add_notifications_table.sql` in your Supabase dashboard.

### 2. Files Created

**Database:**
- `add_notifications_table.sql` - Database schema
- `database/notifications_helper.php` - Helper class for creating notifications

**Pages:**
- `home/notifications.php` - Notifications page with beautiful UI

**Updated Files:**
- `sidebar/sidebar.php` - Added notifications link with badge
- `api/place-bid.php` - Now creates notifications when bids are placed

## How to Use

### Viewing Notifications
Users can access notifications by clicking the "🔔 Notifications" link in the sidebar. The badge shows unread count.

### Creating Notifications Programmatically

Include the helper in your PHP file:
```php
include_once '../database/notifications_helper.php';
$notificationHelper = new NotificationsHelper();
```

#### Available Methods:

**1. Bid Received Notification**
```php
$notificationHelper->notifyBidReceived(
    $seller_id,      // User ID of seller
    $listing_id,     // Listing ID
    $listing_title,  // Title of listing
    $bid_amount,     // Bid amount
    $bidder_name     // Name of bidder
);
```

**2. Outbid Notification**
```php
$notificationHelper->notifyOutbid(
    $bidder_id,      // User ID of outbid user
    $listing_id,     // Listing ID
    $listing_title,  // Title of listing
    $new_bid_amount  // New higher bid amount
);
```

**3. Listing Sold Notification**
```php
$notificationHelper->notifyListingSold(
    $seller_id,      // User ID of seller
    $listing_id,     // Listing ID
    $listing_title,  // Title of listing
    $final_price,    // Final sale price
    $buyer_name      // Name of buyer
);
```

**4. New Message Notification**
```php
$notificationHelper->notifyNewMessage(
    $user_id,         // User ID to notify
    $sender_name,     // Name of message sender
    $conversation_id  // Conversation ID
);
```

**5. Order Update Notification**
```php
$notificationHelper->notifyOrderUpdate(
    $buyer_id,       // User ID of buyer
    $order_id,       // Order ID
    $status,         // New status (e.g., "shipped", "delivered")
    $listing_title   // Title of ordered item
);
```

**6. Custom Notification**
```php
$notificationHelper->createNotification(
    $user_id,   // User ID to notify
    $type,      // Type: 'bid_received', 'outbid', 'listing_sold', 'new_message', 'order_update'
    $title,     // Notification title
    $message,   // Notification message
    $link       // Optional: URL to redirect when clicked
);
```

### Utility Methods

**Get Unread Count:**
```php
$count = $notificationHelper->getUnreadCount($user_id);
```

**Mark as Read:**
```php
$notificationHelper->markAsRead($notification_id, $user_id);
```

**Mark All as Read:**
```php
$notificationHelper->markAllAsRead($user_id);
```

## Example Integration

### When Closing an Auction (in close-bid.php):
```php
include_once '../database/notifications_helper.php';
$notificationHelper = new NotificationsHelper();

// Get winner info
$winner = $supabase->select('accounts', 'username', ['account_id' => $winner_id], true);

// Notify seller
$notificationHelper->notifyListingSold(
    $listing['seller_id'],
    $listing_id,
    $listing['title'],
    $winning_bid,
    $winner['username']
);
```

### When Sending a Message (in your messaging system):
```php
include_once '../database/notifications_helper.php';
$notificationHelper = new NotificationsHelper();

// After inserting message
$notificationHelper->notifyNewMessage(
    $recipient_id,
    $_SESSION['username'],
    $conversation_id
);
```

## Features

✅ Real-time notification badges in sidebar
✅ Beautiful notification UI with icons
✅ Mark as read/unread functionality
✅ Delete notifications
✅ Mark all as read
✅ Time ago display (e.g., "5 minutes ago")
✅ Click to view related content
✅ Different icons for different notification types
✅ Unread notifications highlighted

## Notification Types & Icons

- 🔨 **bid_received** - Blue - New bid on your listing
- ⚠️ **outbid** - Orange - You've been outbid
- ✅ **listing_sold** - Green - Your listing sold
- 💌 **new_message** - Purple - New message received
- 📦 **order_update** - Teal - Order status changed

## Testing

1. Create two user accounts
2. User A creates an auction listing
3. User B places a bid
4. User A should see notification "New bid on your listing!"
5. User C places a higher bid
6. User B should see notification "You've been outbid!"

## Next Steps

You can add notifications to other parts of your system:
- Order confirmations
- Payment confirmations
- Listing approvals
- Account updates
- System announcements

Just use the `NotificationsHelper` class methods wherever you need to notify users!
