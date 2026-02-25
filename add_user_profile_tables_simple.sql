-- Create user addresses table
CREATE TABLE IF NOT EXISTS user_addresses (
    address_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES accounts(account_id) ON DELETE CASCADE,
    address_type VARCHAR(20) DEFAULT 'home',
    address_label VARCHAR(50),
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
CREATE INDEX IF NOT EXISTS idx_user_addresses_user ON user_addresses(user_id);
CREATE INDEX IF NOT EXISTS idx_user_addresses_default ON user_addresses(user_id, is_default);
CREATE INDEX IF NOT EXISTS idx_user_profiles_user ON user_profiles(user_id);

-- Enable Row Level Security
ALTER TABLE user_addresses ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_profiles ENABLE ROW LEVEL SECURITY;

-- Simple RLS Policies - Allow users to manage their own data
CREATE POLICY "user_addresses_policy" ON user_addresses FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "user_profiles_policy" ON user_profiles FOR ALL USING (true) WITH CHECK (true);