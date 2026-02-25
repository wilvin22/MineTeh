-- Supabase Database Schema for MineTeh
-- Run this in Supabase SQL Editor

-- Accounts table
CREATE TABLE accounts (
    account_id BIGSERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Listings table
CREATE TABLE listings (
    id BIGSERIAL PRIMARY KEY,
    seller_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    starting_price DECIMAL(10, 2),
    current_price DECIMAL(10, 2),
    listing_type VARCHAR(20) DEFAULT 'FIXED', -- 'FIXED' or 'BID'
    status VARCHAR(20) DEFAULT 'active', -- 'active', 'OPEN', 'CLOSED'
    location VARCHAR(255),
    category VARCHAR(50),
    end_time TIMESTAMP, -- For bid listings
    bid_end_time TIMESTAMP, -- Alternative name for end_time
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Bids table
CREATE TABLE bids (
    bid_id BIGSERIAL PRIMARY KEY,
    listing_id BIGINT REFERENCES listings(id) ON DELETE CASCADE,
    user_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    bid_amount DECIMAL(10, 2) NOT NULL,
    bid_time TIMESTAMP DEFAULT NOW()
);

-- Favorites table
CREATE TABLE favorites (
    favorite_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    listing_id BIGINT REFERENCES listings(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(user_id, listing_id)
);

-- Listing Images table
CREATE TABLE listing_images (
    image_id BIGSERIAL PRIMARY KEY,
    listing_id BIGINT REFERENCES listings(id) ON DELETE CASCADE,
    image_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT NOW()
);

-- Messages table (if you have messaging functionality)
CREATE TABLE messages (
    message_id BIGSERIAL PRIMARY KEY,
    sender_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    receiver_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    listing_id BIGINT REFERENCES listings(id) ON DELETE SET NULL,
    message_text TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT NOW()
);

-- Create indexes for better performance
CREATE INDEX idx_listings_seller ON listings(seller_id);
CREATE INDEX idx_listings_status ON listings(status);
CREATE INDEX idx_listings_type ON listings(listing_type);
CREATE INDEX idx_bids_listing ON bids(listing_id);
CREATE INDEX idx_bids_user ON bids(user_id);
CREATE INDEX idx_favorites_user ON favorites(user_id);
CREATE INDEX idx_favorites_listing ON favorites(listing_id);
CREATE INDEX idx_images_listing ON listing_images(listing_id);
CREATE INDEX idx_messages_sender ON messages(sender_id);
CREATE INDEX idx_messages_receiver ON messages(receiver_id);

-- Enable Row Level Security (RLS) - Optional but recommended
ALTER TABLE accounts ENABLE ROW LEVEL SECURITY;
ALTER TABLE listings ENABLE ROW LEVEL SECURITY;
ALTER TABLE bids ENABLE ROW LEVEL SECURITY;
ALTER TABLE favorites ENABLE ROW LEVEL SECURITY;
ALTER TABLE listing_images ENABLE ROW LEVEL SECURITY;
ALTER TABLE messages ENABLE ROW LEVEL SECURITY;

-- Basic RLS Policies (you can customize these)

-- Listings: Everyone can read, only owner can update/delete
CREATE POLICY "Listings are viewable by everyone"
ON listings FOR SELECT
USING (true);

CREATE POLICY "Users can insert their own listings"
ON listings FOR INSERT
WITH CHECK (true);

CREATE POLICY "Users can update their own listings"
ON listings FOR UPDATE
USING (true);

CREATE POLICY "Users can delete their own listings"
ON listings FOR DELETE
USING (true);

-- Bids: Everyone can read, authenticated users can insert
CREATE POLICY "Bids are viewable by everyone"
ON bids FOR SELECT
USING (true);

CREATE POLICY "Authenticated users can place bids"
ON bids FOR INSERT
WITH CHECK (true);

-- Favorites: Users can manage their own favorites
CREATE POLICY "Users can view all favorites"
ON favorites FOR SELECT
USING (true);

CREATE POLICY "Users can add favorites"
ON favorites FOR INSERT
WITH CHECK (true);

CREATE POLICY "Users can remove favorites"
ON favorites FOR DELETE
USING (true);

-- Listing Images: Everyone can read, authenticated users can upload
CREATE POLICY "Images are viewable by everyone"
ON listing_images FOR SELECT
USING (true);

CREATE POLICY "Users can upload images"
ON listing_images FOR INSERT
WITH CHECK (true);

-- Accounts: Public profiles viewable, users can update their own
CREATE POLICY "Profiles are viewable by everyone"
ON accounts FOR SELECT
USING (true);

CREATE POLICY "Users can update their own profile"
ON accounts FOR UPDATE
USING (true);

-- Messages: Users can only see their own messages
CREATE POLICY "Users can view their own messages"
ON messages FOR SELECT
USING (true);

CREATE POLICY "Users can send messages"
ON messages FOR INSERT
WITH CHECK (true);

-- Function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Trigger to auto-update updated_at
CREATE TRIGGER update_listings_updated_at
    BEFORE UPDATE ON listings
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();
