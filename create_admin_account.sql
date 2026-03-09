-- Create admin account
-- Username: admin1
-- Password: Admin1!
-- This SQL will create the admin account directly in Supabase

INSERT INTO accounts (username, email, password_hash, first_name, last_name, is_admin)
VALUES (
    'admin1',
    'admin1@mineteh.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Admin',
    'User',
    true
);

-- Note: The password hash above is for 'Admin1!'
-- After running this, you can login at admin/login.php with:
-- Username: admin1
-- Password: Admin1!
