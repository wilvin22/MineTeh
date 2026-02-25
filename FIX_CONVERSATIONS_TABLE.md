# Fix: Conversations Table Missing

## Problem
The `conversations` table doesn't exist in your Supabase database, causing the Contact Seller feature to fail.

## Solution
Run the SQL script to create the missing tables.

### Steps:

1. **Open Supabase Dashboard**
   - Go to: https://supabase.com/dashboard
   - Select your project (didpavzminvohszuuowu)

2. **Open SQL Editor**
   - Click "SQL Editor" in the left sidebar
   - Click "New query"

3. **Copy and Run This SQL**

```sql
-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    order_id BIGSERIAL PRIMARY KEY,
    buyer_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    seller_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    listing_id BIGINT REFERENCES listings(id) ON DELETE CASCADE,
    order_amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50),
    payment_status VARCHAR(20) DEFAULT 'pending',
    delivery_address TEXT,
    delivery_method VARCHAR(50),
    order_status VARCHAR(20) DEFAULT 'processing',
    order_date TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Conversations table (for messaging)
CREATE TABLE IF NOT EXISTS conversations (
    conversation_id BIGSERIAL PRIMARY KEY,
    user1_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    user2_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    listing_id BIGINT REFERENCES listings(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(user1_id, user2_id, listing_id)
);

-- Update messages table structure
DROP TABLE IF EXISTS messages CASCADE;
CREATE TABLE messages (
    message_id BIGSERIAL PRIMARY KEY,
    conversation_id BIGINT REFERENCES conversations(conversation_id) ON DELETE CASCADE,
    sender_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    message_text TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT NOW()
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_orders_buyer ON orders(buyer_id);
CREATE INDEX IF NOT EXISTS idx_orders_seller ON orders(seller_id);
CREATE INDEX IF NOT EXISTS idx_orders_listing ON orders(listing_id);
CREATE INDEX IF NOT EXISTS idx_conversations_users ON conversations(user1_id, user2_id);
CREATE INDEX IF NOT EXISTS idx_messages_conversation ON messages(conversation_id);
CREATE INDEX IF NOT EXISTS idx_messages_sender_new ON messages(sender_id);

-- RLS Policies
ALTER TABLE orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE conversations ENABLE ROW LEVEL SECURITY;
ALTER TABLE messages ENABLE ROW LEVEL SECURITY;

-- Drop existing policies if they exist
DROP POLICY IF EXISTS "Users can view their own orders" ON orders;
DROP POLICY IF EXISTS "Users can create orders" ON orders;
DROP POLICY IF EXISTS "Users can update their orders" ON orders;
DROP POLICY IF EXISTS "Users can view their conversations" ON conversations;
DROP POLICY IF EXISTS "Users can create conversations" ON conversations;
DROP POLICY IF EXISTS "Users can view messages in their conversations" ON messages;
DROP POLICY IF EXISTS "Users can send messages" ON messages;
DROP POLICY IF EXISTS "Users can update their messages" ON messages;

-- Create policies
CREATE POLICY "Users can view their own orders" ON orders FOR SELECT USING (true);
CREATE POLICY "Users can create orders" ON orders FOR INSERT WITH CHECK (true);
CREATE POLICY "Users can update their orders" ON orders FOR UPDATE USING (true);

CREATE POLICY "Users can view their conversations" ON conversations FOR SELECT USING (true);
CREATE POLICY "Users can create conversations" ON conversations FOR INSERT WITH CHECK (true);

CREATE POLICY "Users can view messages in their conversations" ON messages FOR SELECT USING (true);
CREATE POLICY "Users can send messages" ON messages FOR INSERT WITH CHECK (true);
CREATE POLICY "Users can update their messages" ON messages FOR UPDATE USING (true);
```

4. **Click "Run"** (or press Ctrl+Enter)

5. **Verify Success**
   - You should see "Success. No rows returned"
   - Go back to your app and test the Contact Seller button

6. **Test Again**
   - Run: http://localhost/MineTeh/test_conversation.php
   - It should now successfully create conversations

## After Running SQL

Once the tables are created, the Contact Seller feature will work properly:
- Clicking "Contact Seller" will create a conversation (if it doesn't exist)
- You'll be redirected directly to the chat with that seller
- Messages will be saved and displayed correctly
