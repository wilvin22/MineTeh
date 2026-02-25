-- Create cart table for shopping cart functionality
-- Run this in Supabase SQL Editor

CREATE TABLE IF NOT EXISTS cart (
    cart_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    listing_id BIGINT REFERENCES listings(id) ON DELETE CASCADE,
    quantity INT DEFAULT 1,
    added_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(user_id, listing_id)
);

-- Create index for faster lookups
CREATE INDEX IF NOT EXISTS idx_cart_user ON cart(user_id);
CREATE INDEX IF NOT EXISTS idx_cart_listing ON cart(listing_id);

-- Enable Row Level Security
ALTER TABLE cart ENABLE ROW LEVEL SECURITY;

-- Users can view, add, update, and delete their own cart items
-- Using simple policies that allow all operations (since we handle auth in PHP)
CREATE POLICY "Allow all cart operations"
ON cart FOR ALL
USING (true)
WITH CHECK (true);
