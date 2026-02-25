-- Add minimum bid increment column to listings table
-- Run this in Supabase SQL Editor

ALTER TABLE listings 
ADD COLUMN IF NOT EXISTS min_bid_increment DECIMAL(10, 2) DEFAULT 1.00;

-- Add comment to explain the column
COMMENT ON COLUMN listings.min_bid_increment IS 'Minimum amount each bid must increase by (for auction listings)';

-- Update existing BID listings to have a default min_bid_increment if NULL
UPDATE listings 
SET min_bid_increment = 1.00 
WHERE listing_type = 'BID' AND min_bid_increment IS NULL;
