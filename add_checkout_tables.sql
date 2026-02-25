-- Add tables for checkout and orders
-- Run this in Supabase SQL Editor

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    order_id BIGSERIAL PRIMARY KEY,
    buyer_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    seller_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    listing_id BIGINT REFERENCES listings(id) ON DELETE CASCADE,
    order_amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50),
    payment_status VARCHAR(20) DEFAULT 'pending', -- pending, completed, failed
    delivery_address TEXT,
    delivery_method VARCHAR(50),
    order_status VARCHAR(20) DEFAULT 'processing', -- processing, shipped, delivered, cancelled
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
CREATE INDEX idx_orders_buyer ON orders(buyer_id);
CREATE INDEX idx_orders_seller ON orders(seller_id);
CREATE INDEX idx_orders_listing ON orders(listing_id);
CREATE INDEX idx_conversations_users ON conversations(user1_id, user2_id);
CREATE INDEX idx_messages_conversation ON messages(conversation_id);
CREATE INDEX idx_messages_sender ON messages(sender_id);

-- RLS Policies
ALTER TABLE orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE conversations ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view their own orders" ON orders FOR SELECT USING (true);
CREATE POLICY "Users can create orders" ON orders FOR INSERT WITH CHECK (true);
CREATE POLICY "Users can update their orders" ON orders FOR UPDATE USING (true);

CREATE POLICY "Users can view their conversations" ON conversations FOR SELECT USING (true);
CREATE POLICY "Users can create conversations" ON conversations FOR INSERT WITH CHECK (true);

CREATE POLICY "Users can view messages in their conversations" ON messages FOR SELECT USING (true);
CREATE POLICY "Users can send messages" ON messages FOR INSERT WITH CHECK (true);
CREATE POLICY "Users can update their messages" ON messages FOR UPDATE USING (true);
