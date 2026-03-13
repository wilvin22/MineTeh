<?php
session_start();
date_default_timezone_set('Asia/Manila');

require_once '../database/supabase.php';

// If already logged in as rider, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    $rider = $supabase->select('riders', '*', ['account_id' => $_SESSION['user_id']], true);
    if ($rider) {
        header('Location: dashboard.php');
        exit;
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $vehicle_type = $_POST['vehicle_type'] ?? 'motorcycle';
    $license_number = trim($_POST['license_number'] ?? '');

    // Validation
    if (!$username || !$email || !$password || !$first_name || !$last_name || !$full_name || !$phone_number) {
        $error = 'All required fields must be filled';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if username exists
        $existing = $supabase->select('accounts', 'account_id', ['username' => $username], true);
        if ($existing) {
            $error = 'Username already exists';
        } else {
            // Check if email exists
            $existing = $supabase->select('accounts', 'account_id', ['email' => $email], true);
            if ($existing) {
                $error = 'Email already exists';
            } else {
                // Create account
                $account_data = [
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'is_admin' => false,
                    'is_rider' => true,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $result = $supabase->insert('accounts', $account_data);
                
                if ($result) {
                    // Get the new account
                    $account = $supabase->select('accounts', 'account_id', ['username' => $username], true);
                    
                    if ($account) {
                        // Create rider profile
                        $rider_data = [
                            'account_id' => $account['account_id'],
                            'full_name' => $full_name,
                            'phone_number' => $phone_number,
                            'vehicle_type' => $vehicle_type,
                            'license_number' => $license_number,
                            'status' => 'active',
                            'rating' => 5.00,
                            'total_deliveries' => 0,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];

                        $rider_result = $supabase->insert('riders', $rider_data);
                        
                        if ($rider_result) {
                            $success = 'Registration successful! You can now login.';
                            // Auto-login
                            $_SESSION['user_id'] = $account['account_id'];
                            $_SESSION['username'] = $username;
                            header('Location: dashboard.php');
                            exit;
                        } else {
                            $error = 'Failed to create rider profile';
                        }
                    } else {
                        $error = 'Failed to retrieve account';
                    }
                } else {
                    $error = 'Failed to create account';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Registration - MineTeh</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .required {
            color: #e74c3c;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏍️ Become a Rider</h1>
            <p>Join MineTeh delivery team and start earning today!</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST">
            <h3 style="margin-bottom: 15px; color: #333;">Account Information</h3>
            
            <div class="form-group">
                <label>Username <span class="required">*</span></label>
                <input type="text" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <input type="password" name="password" required minlength="6">
                </div>

                <div class="form-group">
                    <label>Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>First Name <span class="required">*</span></label>
                    <input type="text" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Last Name <span class="required">*</span></label>
                    <input type="text" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>
            </div>

            <h3 style="margin: 25px 0 15px; color: #333;">Rider Information</h3>

            <div class="form-group">
                <label>Full Name (as on license) <span class="required">*</span></label>
                <input type="text" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Phone Number <span class="required">*</span></label>
                <input type="text" name="phone_number" required placeholder="09XXXXXXXXX" value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Vehicle Type <span class="required">*</span></label>
                    <select name="vehicle_type" required>
                        <option value="motorcycle">Motorcycle</option>
                        <option value="car">Car</option>
                        <option value="bicycle">Bicycle</option>
                        <option value="van">Van</option>
                        <option value="truck">Truck</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>License Number</label>
                    <input type="text" name="license_number" placeholder="ABC-123-456" value="<?php echo htmlspecialchars($_POST['license_number'] ?? ''); ?>">
                </div>
            </div>

            <button type="submit" class="btn">Register as Rider</button>
        </form>

        <div class="links">
            <p>Already have an account? <a href="../login.php">Login here</a></p>
            <p><a href="../index.php">Back to Home</a></p>
        </div>
    </div>
</body>
</html>
