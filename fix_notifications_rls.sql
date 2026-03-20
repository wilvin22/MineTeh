-- Fix notifications RLS so server-side PHP can insert notifications freely
-- The current INSERT policy blocks inserts because app.current_user_id is not set via REST API

-- Drop the restrictive policies
DROP POLICY IF EXISTS "Users can view own notifications" ON notifications;
DROP POLICY IF EXISTS "System can insert notifications" ON notifications;
DROP POLICY IF EXISTS "Users can update own notifications" ON notifications;

-- Disable RLS entirely (simplest fix for a server-side app using service role key)
ALTER TABLE notifications DISABLE ROW LEVEL SECURITY;
