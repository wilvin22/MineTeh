-- Add user_status columns to accounts table
-- This allows admins to ban or restrict users with reasons and duration

-- Add status column with default value 'active'
ALTER TABLE accounts 
ADD COLUMN IF NOT EXISTS user_status VARCHAR(20) DEFAULT 'active';

-- Add restriction_until column for temporary restrictions
ALTER TABLE accounts 
ADD COLUMN IF NOT EXISTS restriction_until TIMESTAMP NULL;

-- Add status_reason column for ban/restriction remarks
ALTER TABLE accounts 
ADD COLUMN IF NOT EXISTS status_reason TEXT NULL;

-- Update existing users to have 'active' status
UPDATE accounts 
SET user_status = 'active' 
WHERE user_status IS NULL;

-- Possible values:
-- 'active' - Normal user, can use all features
-- 'banned' - Cannot login at all (permanent)
-- 'restricted' - Can login but cannot create listings or place bids (can be temporary)

COMMENT ON COLUMN accounts.user_status IS 'User account status: active, banned, or restricted';
COMMENT ON COLUMN accounts.restriction_until IS 'Timestamp when restriction expires (NULL for permanent or if not restricted)';
COMMENT ON COLUMN accounts.status_reason IS 'Admin remarks explaining why user was banned or restricted';
