-- Fix accounts table RLS so the PHP backend (anon key) can INSERT new accounts
-- Run this in your Supabase SQL editor

-- Option 1 (Recommended): Disable RLS on accounts entirely
-- since this is a server-side PHP app using the anon key directly
ALTER TABLE accounts DISABLE ROW LEVEL SECURITY;

-- Option 2 (Alternative): If you want to keep RLS, add a permissive insert policy
-- DROP POLICY IF EXISTS "Allow anon insert" ON accounts;
-- CREATE POLICY "Allow anon insert" ON accounts FOR INSERT TO anon WITH CHECK (true);
-- CREATE POLICY "Allow anon select" ON accounts FOR SELECT TO anon USING (true);
-- CREATE POLICY "Allow anon update" ON accounts FOR UPDATE TO anon USING (true) WITH CHECK (true);
