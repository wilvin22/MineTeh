-- Rider System Tables for MineTeh
-- Run this SQL in your Supabase SQL Editor

-- 1. Riders table
CREATE TABLE IF NOT EXISTS riders (
    rider_id SERIAL PRIMARY KEY,
    account_id INTEGER REFERENCES accounts(account_id) ON DELETE CASCADE,
    full_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    vehicle_type VARCHAR(50), -- motorcycle, car, bicycle, etc.
    license_number VARCHAR(100),
    status VARCHAR(20) DEFAULT 'active', -- active, inactive, suspended
    rating DECIMAL(3,2) DEFAULT 5.00,
    total_deliveries INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Deliveries table
CREATE TABLE IF NOT EXISTS deliveries (
    delivery_id SERIAL PRIMARY KEY,
    order_id INTEGER REFERENCES orders(order_id) ON DELETE CASCADE,
    rider_id INTEGER REFERENCES riders(rider_id) ON DELETE SET NULL,
    pickup_address TEXT NOT NULL,
    delivery_address TEXT NOT NULL,
    recipient_name VARCHAR(255),
    recipient_phone VARCHAR(20),
    delivery_status VARCHAR(50) DEFAULT 'pending', -- pending, assigned, picked_up, in_transit, delivered, failed, cancelled
    assigned_at TIMESTAMP,
    picked_up_at TIMESTAMP,
    delivered_at TIMESTAMP,
    delivery_notes TEXT,
    delivery_fee DECIMAL(10,2) DEFAULT 0.00,
    distance_km DECIMAL(10,2),
    estimated_delivery_time TIMESTAMP,
    actual_delivery_time TIMESTAMP,
    proof_of_delivery_photo VARCHAR(500), -- URL to photo
    recipient_signature VARCHAR(500), -- URL to signature image or base64
    delivery_rating INTEGER, -- 1-5 stars from customer
    delivery_feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Delivery tracking/history table
CREATE TABLE IF NOT EXISTS delivery_tracking (
    tracking_id SERIAL PRIMARY KEY,
    delivery_id INTEGER REFERENCES deliveries(delivery_id) ON DELETE CASCADE,
    status VARCHAR(50) NOT NULL,
    location_lat DECIMAL(10,8),
    location_lng DECIMAL(11,8),
    notes TEXT,
    created_by INTEGER REFERENCES accounts(account_id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Rider earnings table
CREATE TABLE IF NOT EXISTS rider_earnings (
    earning_id SERIAL PRIMARY KEY,
    rider_id INTEGER REFERENCES riders(rider_id) ON DELETE CASCADE,
    delivery_id INTEGER REFERENCES deliveries(delivery_id) ON DELETE CASCADE,
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending', -- pending, paid, cancelled
    paid_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_deliveries_rider ON deliveries(rider_id);
CREATE INDEX IF NOT EXISTS idx_deliveries_order ON deliveries(order_id);
CREATE INDEX IF NOT EXISTS idx_deliveries_status ON deliveries(delivery_status);
CREATE INDEX IF NOT EXISTS idx_delivery_tracking_delivery ON delivery_tracking(delivery_id);
CREATE INDEX IF NOT EXISTS idx_rider_earnings_rider ON rider_earnings(rider_id);

-- Add rider_id column to accounts table if it doesn't exist
ALTER TABLE accounts ADD COLUMN IF NOT EXISTS is_rider BOOLEAN DEFAULT FALSE;
ALTER TABLE accounts ADD COLUMN IF NOT EXISTS rider_id INTEGER REFERENCES riders(rider_id);

-- Insert sample rider (optional - for testing)
-- INSERT INTO riders (account_id, full_name, phone_number, vehicle_type, license_number)
-- VALUES (1, 'John Rider', '09123456789', 'motorcycle', 'ABC-123-456');

COMMENT ON TABLE riders IS 'Stores rider/delivery personnel information';
COMMENT ON TABLE deliveries IS 'Tracks all delivery orders and their status';
COMMENT ON TABLE delivery_tracking IS 'Logs all status changes and location updates for deliveries';
COMMENT ON TABLE rider_earnings IS 'Tracks rider earnings per delivery';
