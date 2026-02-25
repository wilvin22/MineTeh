-- Create categories table
-- Run this in Supabase SQL Editor

CREATE TABLE IF NOT EXISTS categories (
    category_id BIGSERIAL PRIMARY KEY,
    category_name VARCHAR(50) UNIQUE NOT NULL,
    category_slug VARCHAR(50) UNIQUE NOT NULL,
    category_icon VARCHAR(10),
    category_description TEXT,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Insert default categories
INSERT INTO categories (category_name, category_slug, category_icon, category_description, display_order) VALUES
('Electronics', 'electronics', '📱', 'Phones, laptops, tablets, and electronic devices', 1),
('Vehicles', 'vehicle', '🚗', 'Cars, motorcycles, bikes, and other vehicles', 2),
('Property', 'property', '🏠', 'Houses, apartments, land, and real estate', 3),
('Fashion', 'fashion', '👕', 'Clothing, shoes, accessories, and fashion items', 4),
('Home & Garden', 'home', '🛋️', 'Furniture, appliances, and home decor', 5),
('Sports', 'sports', '⚽', 'Sports equipment, fitness gear, and outdoor items', 6),
('Books', 'books', '📚', 'Books, magazines, and educational materials', 7),
('Other', 'other', '📦', 'Miscellaneous items and other categories', 8);

-- Create index for faster lookups
CREATE INDEX idx_categories_slug ON categories(category_slug);
CREATE INDEX idx_categories_active ON categories(is_active);

-- Enable Row Level Security
ALTER TABLE categories ENABLE ROW LEVEL SECURITY;

-- Allow everyone to read categories
CREATE POLICY "Categories are viewable by everyone"
ON categories FOR SELECT
USING (true);

-- Only admins can modify categories (you can adjust this later)
CREATE POLICY "Admins can manage categories"
ON categories FOR ALL
USING (true)
WITH CHECK (true);

-- Update listings table to use category_id instead of category string
-- First, add the new column
ALTER TABLE listings ADD COLUMN IF NOT EXISTS category_id BIGINT REFERENCES categories(category_id);

-- Create index for category lookups
CREATE INDEX IF NOT EXISTS idx_listings_category ON listings(category_id);

-- Optional: Migrate existing category data
-- This maps old string categories to new category IDs
UPDATE listings 
SET category_id = (
    SELECT category_id 
    FROM categories 
    WHERE category_slug = listings.category
)
WHERE category IS NOT NULL;

-- After migration is complete, you can optionally drop the old category column
-- ALTER TABLE listings DROP COLUMN IF EXISTS category;
