-- SQL Script to Remove Rider System Tables
-- Run this in your Supabase SQL Editor to clean up rider-related tables

-- Drop tables in correct order (respecting foreign key constraints)
DROP TABLE IF EXISTS delivery_tracking CASCADE;
DROP TABLE IF EXISTS rider_earnings CASCADE;
DROP TABLE IF EXISTS deliveries CASCADE;
DROP TABLE IF EXISTS riders CASCADE;

-- Remove is_rider column from accounts table
ALTER TABLE accounts DROP COLUMN IF EXISTS is_rider;

-- Clean up any orphaned data
-- (Optional: Remove any orders with delivery references if needed)

-- Verification queries (run these to confirm cleanup)
-- SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name LIKE '%rider%';
-- SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name LIKE '%delivery%';
