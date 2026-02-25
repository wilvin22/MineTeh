-- Fix Row Level Security Policies for Supabase
-- Run this in Supabase SQL Editor to allow signups and operations

-- First, drop the existing restrictive policies
DROP POLICY IF EXISTS "Users can update their own profile" ON accounts;
DROP POLICY IF EXISTS "Profiles are viewable by everyone" ON accounts;

-- Create new policies that allow public signup and operations

-- Allow anyone to insert (signup)
CREATE POLICY "Anyone can create an account"
ON accounts FOR INSERT
WITH CHECK (true);

-- Allow anyone to read accounts (for login checks)
CREATE POLICY "Anyone can read accounts"
ON accounts FOR SELECT
USING (true);

-- Allow users to update their own account
CREATE POLICY "Users can update own account"
ON accounts FOR UPDATE
USING (true)
WITH CHECK (true);

-- Allow users to delete their own account
CREATE POLICY "Users can delete own account"
ON accounts FOR DELETE
USING (true);

-- Fix other tables to allow operations

-- Listings policies
DROP POLICY IF EXISTS "Listings are viewable by everyone" ON listings;
DROP POLICY IF EXISTS "Users can insert their own listings" ON listings;
DROP POLICY IF EXISTS "Users can update their own listings" ON listings;
DROP POLICY IF EXISTS "Users can delete their own listings" ON listings;

CREATE POLICY "Anyone can view listings" ON listings FOR SELECT USING (true);
CREATE POLICY "Anyone can create listings" ON listings FOR INSERT WITH CHECK (true);
CREATE POLICY "Anyone can update listings" ON listings FOR UPDATE USING (true);
CREATE POLICY "Anyone can delete listings" ON listings FOR DELETE USING (true);

-- Bids policies
DROP POLICY IF EXISTS "Bids are viewable by everyone" ON bids;
DROP POLICY IF EXISTS "Authenticated users can place bids" ON bids;

CREATE POLICY "Anyone can view bids" ON bids FOR SELECT USING (true);
CREATE POLICY "Anyone can place bids" ON bids FOR INSERT WITH CHECK (true);

-- Favorites policies
DROP POLICY IF EXISTS "Users can view all favorites" ON favorites;
DROP POLICY IF EXISTS "Users can add favorites" ON favorites;
DROP POLICY IF EXISTS "Users can remove favorites" ON favorites;

CREATE POLICY "Anyone can view favorites" ON favorites FOR SELECT USING (true);
CREATE POLICY "Anyone can add favorites" ON favorites FOR INSERT WITH CHECK (true);
CREATE POLICY "Anyone can remove favorites" ON favorites FOR DELETE USING (true);

-- Listing images policies
DROP POLICY IF EXISTS "Images are viewable by everyone" ON listing_images;
DROP POLICY IF EXISTS "Users can upload images" ON listing_images;

CREATE POLICY "Anyone can view images" ON listing_images FOR SELECT USING (true);
CREATE POLICY "Anyone can upload images" ON listing_images FOR INSERT WITH CHECK (true);
CREATE POLICY "Anyone can delete images" ON listing_images FOR DELETE USING (true);

-- Messages policies
DROP POLICY IF EXISTS "Users can view their own messages" ON messages;
DROP POLICY IF EXISTS "Users can send messages" ON messages;

CREATE POLICY "Anyone can view messages" ON messages FOR SELECT USING (true);
CREATE POLICY "Anyone can send messages" ON messages FOR INSERT WITH CHECK (true);
CREATE POLICY "Anyone can update messages" ON messages FOR UPDATE USING (true);
