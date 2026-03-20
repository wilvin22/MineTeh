-- Migration: update reviews unique constraint to allow one review per listing
-- Run this if the reviews table was already created with the old constraint

-- Drop old unique constraint (one review per seller globally)
ALTER TABLE reviews DROP CONSTRAINT IF EXISTS reviews_seller_id_reviewer_id_key;

-- Add listing_id column if it doesn't exist yet
ALTER TABLE reviews ADD COLUMN IF NOT EXISTS listing_id INT REFERENCES listings(id) ON DELETE SET NULL;

-- Add new unique constraint: one review per buyer per listing
ALTER TABLE reviews DROP CONSTRAINT IF EXISTS reviews_seller_id_reviewer_id_listing_id_key;
ALTER TABLE reviews ADD CONSTRAINT reviews_seller_id_reviewer_id_listing_id_key
    UNIQUE (seller_id, reviewer_id, listing_id);
