# Checkout & Messaging System - Setup Guide

## Step 1: Run the SQL Schema

1. Go to your Supabase dashboard
2. Click **SQL Editor**
3. Copy the contents of `add_checkout_tables.sql`
4. Paste and click **Run**

This creates:
- `orders` table - Stores all purchase orders
- `conversations` table - Manages chat conversations
- `messages` table - Stores individual messages

## Step 2: Test the Features

### Checkout System

1. **Browse Listings**: Go to `http://localhost/Mineteh/home/homepage.php`
2. **Click a Listing**: View details
3. **Click "Buy Now"**: Goes to checkout page
4. **Fill in Details**:
   - Delivery address
   - Choose delivery method (Standard/Express/Pickup)
   - Choose payment method (COD/GCash/Bank Transfer)
5. **Place Order**: Redirects to confirmation page
6. **Order Confirmed**: Shows order number and details

### Messaging System

1. **From Listing Details**: Click "Contact Seller"
2. **From Order Confirmation**: Click "Message Seller"
3. **Direct Access**: Go to `http://localhost/Mineteh/home/messages.php`

**Features**:
- Real-time messaging (auto-refreshes every 5 seconds)
- Conversation list on the left
- Chat area on the right
- Shows listing context in chat header
- Message history
- Timestamps

## Features Overview

### Checkout Page (`checkout.php`)
- ✅ Item preview with image
- ✅ Seller information
- ✅ Delivery address input
- ✅ Delivery method selection (Standard/Express/Pickup)
- ✅ Payment method selection (COD/GCash/Bank Transfer)
- ✅ Order summary with price breakdown
- ✅ Responsive design

### Order Confirmation (`order-confirmation.php`)
- ✅ Success message with order number
- ✅ Complete order details
- ✅ Links to continue shopping or message seller
- ✅ Order tracking information

### Messaging System (`messages.php`)
- ✅ Conversation list with avatars
- ✅ Real-time chat interface
- ✅ Message bubbles (sent/received)
- ✅ Timestamps
- ✅ Listing context in header
- ✅ Auto-scroll to latest message
- ✅ Auto-refresh every 5 seconds
- ✅ Responsive design

## Database Tables

### orders
- `order_id` - Primary key
- `buyer_id` - User who purchased
- `seller_id` - User who sold
- `listing_id` - Item purchased
- `order_amount` - Total price
- `payment_method` - COD/GCash/Bank
- `payment_status` - pending/completed/failed
- `delivery_address` - Shipping address
- `delivery_method` - Standard/Express/Pickup
- `order_status` - processing/shipped/delivered/cancelled
- `order_date` - When order was placed

### conversations
- `conversation_id` - Primary key
- `user1_id` - First user
- `user2_id` - Second user
- `listing_id` - Related listing (optional)
- `created_at` - When conversation started
- `updated_at` - Last message time

### messages
- `message_id` - Primary key
- `conversation_id` - Which conversation
- `sender_id` - Who sent the message
- `message_text` - Message content
- `is_read` - Read status
- `sent_at` - When message was sent

## User Flow

### Buying an Item:
1. User browses homepage
2. Clicks on listing → Views details
3. Clicks "Buy Now" → Checkout page
4. Fills in delivery & payment info
5. Places order → Confirmation page
6. Can message seller from confirmation

### Messaging:
1. User clicks "Contact Seller" on listing
2. Creates/opens conversation
3. Sends messages back and forth
4. Messages auto-refresh
5. Can access all conversations from Messages page

## Security Features

- ✅ Session-based authentication
- ✅ User ID verification
- ✅ SQL injection prevention (using Supabase)
- ✅ XSS protection (htmlspecialchars)
- ✅ Row Level Security policies in Supabase

## Next Steps (Optional Enhancements)

1. **Email Notifications**: Send order confirmations via email
2. **Order Tracking**: Add tracking numbers and status updates
3. **Payment Integration**: Integrate real payment gateways
4. **Image Upload**: Allow image sharing in messages
5. **Notifications**: Add unread message badges
6. **Search**: Add search functionality for messages
7. **Order History**: Create a page to view all past orders

## Troubleshooting

### Orders not saving?
- Check if SQL schema was run in Supabase
- Verify RLS policies are set correctly
- Check browser console for errors

### Messages not showing?
- Ensure conversations table exists
- Check if conversation was created
- Verify user IDs match

### Checkout button not working?
- Make sure user is logged in
- Check if listing exists
- Verify listing is not user's own listing
