-- Create password_resets table for storing reset codes
CREATE TABLE IF NOT EXISTS password_resets (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    reset_code VARCHAR(6) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE
);

-- Add index for faster lookups
CREATE INDEX idx_password_resets_email ON password_resets(email);
CREATE INDEX idx_password_resets_code ON password_resets(reset_code);
