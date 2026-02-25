-- Create user addresses table
-- Run this in Supabase SQL Editor

CREATE TABLE IF NOT EXISTS user_addresses (
    address_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    address_type VARCHAR(20) DEFAULT 'home', -- 'home', 'work', 'other'
    address_label VARCHAR(50), -- Custom label like "Mom's House", "Office", etc.
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state_province VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Philippines',
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Create user profile settings table (simplified)
CREATE TABLE IF NOT EXISTS user_profiles (
    profile_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE UNIQUE,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20),
    date_of_birth DATE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Create indexes for better performance
CREATE INDEX idx_user_addresses_user ON user_addresses(user_id);
CREATE INDEX idx_user_addresses_default ON user_addresses(user_id, is_default);
CREATE INDEX idx_user_profiles_user ON user_profiles(user_id);

-- Enable Row Level Security
ALTER TABLE user_addresses ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_profiles ENABLE ROW LEVEL SECURITY;

-- RLS Policies for user_addresses (simplified)
CREATE POLICY "Users can manage their own addresses"
ON user_addresses FOR ALL
USING (user_id = auth.uid()::bigint)
WITH CHECK (user_id = auth.uid()::bigint);

-- RLS Policies for user_profiles (simplified)
CREATE POLICY "Users can manage their own profile"
ON user_profiles FOR ALL
USING (user_id = auth.uid()::bigint)
WITH CHECK (user_id = auth.uid()::bigint);

-- Function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers to auto-update updated_at
CREATE TRIGGER update_user_addresses_updated_at 
    BEFORE UPDATE ON user_addresses
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_user_profiles_updated_at 
    BEFORE UPDATE ON user_profiles
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();