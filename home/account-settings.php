<?php
session_start();
include "../database/supabase.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user account info
$user_account = $supabase->select('accounts', '*', ['account_id' => $user_id], true);

// Get or create user profile
$user_profile = $supabase->select('user_profiles', '*', ['user_id' => $user_id], true);
if (!$user_profile) {
    // Create default profile
    $supabase->insert('user_profiles', ['user_id' => $user_id]);
    $user_profile = $supabase->select('user_profiles', '*', ['user_id' => $user_id], true);
}

// Get user addresses
$user_addresses = $supabase->select('user_addresses', '*', ['user_id' => $user_id]);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $profile_data = [
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'phone' => trim($_POST['phone']),
            'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null
        ];
        
        $result = $supabase->update('user_profiles', $profile_data, ['user_id' => $user_id]);
        if ($result) {
            $success_message = "Profile updated successfully!";
            $user_profile = $supabase->select('user_profiles', '*', ['user_id' => $user_id], true);
        } else {
            $error_message = "Failed to update profile.";
        }
    }
    
    if (isset($_POST['add_address'])) {
        // Add new address
        $address_data = [
            'user_id' => $user_id,
            'address_type' => $_POST['address_type'],
            'address_label' => trim($_POST['address_label']),
            'full_name' => trim($_POST['full_name']),
            'phone' => trim($_POST['phone']),
            'address_line1' => trim($_POST['address_line1']),
            'address_line2' => trim($_POST['address_line2']),
            'city' => trim($_POST['city']),
            'state_province' => trim($_POST['state_province']),
            'postal_code' => trim($_POST['postal_code']),
            'country' => trim($_POST['country']),
            'is_default' => isset($_POST['is_default'])
        ];
        
        // If this is set as default, unset other defaults
        if ($address_data['is_default']) {
            $supabase->update('user_addresses', ['is_default' => false], ['user_id' => $user_id]);
        }
        
        $result = $supabase->insert('user_addresses', $address_data);
        if ($result) {
            $success_message = "Address added successfully!";
            $user_addresses = $supabase->select('user_addresses', '*', ['user_id' => $user_id]);
        } else {
            $error_message = "Failed to add address.";
        }
    }
    
    if (isset($_POST['delete_address'])) {
        $address_id = (int)$_POST['address_id'];
        $result = $supabase->delete('user_addresses', ['address_id' => $address_id, 'user_id' => $user_id]);
        if ($result) {
            $success_message = "Address deleted successfully!";
            $user_addresses = $supabase->select('user_addresses', '*', ['user_id' => $user_id]);
        } else {
            $error_message = "Failed to delete address.";
        }
    }
    
    if (isset($_POST['set_default_address'])) {
        $address_id = (int)$_POST['address_id'];
        // Unset all defaults first
        $supabase->update('user_addresses', ['is_default' => false], ['user_id' => $user_id]);
        // Set new default
        $result = $supabase->update('user_addresses', ['is_default' => true], ['address_id' => $address_id, 'user_id' => $user_id]);
        if ($result) {
            $success_message = "Default address updated!";
            $user_addresses = $supabase->select('user_addresses', '*', ['user_id' => $user_id]);
        } else {
            $error_message = "Failed to update default address.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            min-height: 100vh;
        }

        .settings-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 42px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .page-header p {
            font-size: 18px;
            color: #666;
        }

        .settings-tabs {
            display: flex;
            background: white;
            border-radius: 15px 15px 0 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .tab-button {
            flex: 1;
            padding: 20px;
            background: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background: linear-gradient(135deg, #945a9b, #6a406e);
            color: white;
        }

        .tab-button:hover:not(.active) {
            background: #f8f4f9;
            color: #945a9b;
        }

        .tab-content {
            background: white;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 40px;
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: #333;
            margin-bottom: 25px;
            padding-bottom: 12px;
            border-bottom: 3px solid #945a9b;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #945a9b;
            box-shadow: 0 0 0 3px rgba(148, 90, 155, 0.1);
        }

        .form-group input:disabled {
            background: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
        }

        .form-group small {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: #6c757d;
            font-style: italic;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #adb5bd;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #945a9b;
            cursor: pointer;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #945a9b, #6a406e);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(148, 90, 155, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .address-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            background: white;
            transition: all 0.3s ease;
        }

        .address-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .address-card.default {
            border-color: #945a9b;
            background: #f8f4f9;
        }

        .address-type-badge {
            position: absolute;
            top: -12px;
            left: 25px;
            background: linear-gradient(135deg, #945a9b, #6a406e);
            color: white;
            padding: 6px 14px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 700;
        }

        .default-badge {
            position: absolute;
            top: -12px;
            right: 25px;
            background: #28a745;
            color: white;
            padding: 6px 14px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 700;
        }

        .address-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .message {
            padding: 18px 22px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .add-address-form {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 12px;
            margin-top: 25px;
            display: none;
        }

        .add-address-form.show {
            display: block;
        }

        .add-address-form h4 {
            margin: 0 0 25px 0;
            color: #333;
            font-size: 20px;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .settings-tabs {
                flex-direction: column;
            }
            
            .settings-container {
                padding: 15px;
            }
            
            .tab-content {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="settings-container">
            <div class="page-header">
                <h1>⚙️ Account Settings</h1>
                <p>Manage your profile and addresses with ease</p>
            </div>

            <?php if ($success_message): ?>
                <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="settings-tabs">
                <button class="tab-button active" onclick="showTab('profile')">👤 Profile</button>
                <button class="tab-button" onclick="showTab('addresses')">📍 Addresses</button>
            </div>

            <!-- Profile Tab -->
            <div id="profile-tab" class="tab-content active">
                <form method="POST">
                    <div class="form-section">
                        <h3 class="section-title">Personal Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user_profile['first_name'] ?? ''); ?>" placeholder="Enter your first name">
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user_profile['last_name'] ?? ''); ?>" placeholder="Enter your last name">
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_profile['phone'] ?? ''); ?>" placeholder="e.g., +63 912 345 6789">
                            </div>
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="date_of_birth" value="<?php echo $user_profile['date_of_birth'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">Account Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" value="<?php echo htmlspecialchars($user_account['username']); ?>" disabled>
                                <small style="color: #666;">Username cannot be changed</small>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" value="<?php echo htmlspecialchars($user_account['email']); ?>" disabled>
                                <small style="color: #666;">Email cannot be changed</small>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <button type="submit" name="update_profile" class="btn btn-primary">💾 Update Profile</button>
                        <button type="button" onclick="confirmLogout()" class="btn btn-danger">Logout</button>
                    </div>
                </form>
            </div>

            <!-- Addresses Tab -->
            <div id="addresses-tab" class="tab-content">
                <div class="form-section">
                    <h3 class="section-title">Your Addresses</h3>
                    
                    <?php if (empty($user_addresses)): ?>
                        <p style="text-align: center; color: #666; padding: 40px;">
                            📍 No addresses added yet. Add your first address below!
                        </p>
                    <?php else: ?>
                        <?php foreach ($user_addresses as $address): ?>
                        <div class="address-card <?php echo $address['is_default'] ? 'default' : ''; ?>">
                            <div class="address-type-badge">
                                <?php 
                                $icons = ['home' => '🏠', 'work' => '🏢', 'other' => '📍'];
                                echo $icons[$address['address_type']] ?? '📍';
                                echo ' ' . ucfirst($address['address_type']);
                                ?>
                            </div>
                            
                            <?php if ($address['is_default']): ?>
                                <div class="default-badge">✓ Default</div>
                            <?php endif; ?>
                            
                            <div style="margin-top: 10px;">
                                <strong><?php echo htmlspecialchars($address['full_name']); ?></strong>
                                <?php if ($address['address_label']): ?>
                                    <span style="color: #666;"> (<?php echo htmlspecialchars($address['address_label']); ?>)</span>
                                <?php endif; ?>
                            </div>
                            
                            <div style="margin-top: 8px; color: #666;">
                                <?php echo htmlspecialchars($address['address_line1']); ?><br>
                                <?php if ($address['address_line2']): ?>
                                    <?php echo htmlspecialchars($address['address_line2']); ?><br>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state_province']); ?> <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                <?php echo htmlspecialchars($address['country']); ?>
                            </div>
                            
                            <?php if ($address['phone']): ?>
                                <div style="margin-top: 8px; color: #666;">
                                    📞 <?php echo htmlspecialchars($address['phone']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="address-actions">
                                <?php if (!$address['is_default']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="address_id" value="<?php echo $address['address_id']; ?>">
                                        <button type="submit" name="set_default_address" class="btn btn-success">Set as Default</button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this address?')">
                                    <input type="hidden" name="address_id" value="<?php echo $address['address_id']; ?>">
                                    <button type="submit" name="delete_address" class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-primary" onclick="toggleAddressForm()">
                        ➕ Add New Address
                    </button>
                    
                    <div id="add-address-form" class="add-address-form">
                        <h4>Add New Address</h4>
                        <form method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Address Type</label>
                                    <select name="address_type" required>
                                        <option value="home">🏠 Home</option>
                                        <option value="work">🏢 Work</option>
                                        <option value="other">📍 Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Custom Label (Optional)</label>
                                    <input type="text" name="address_label" placeholder="e.g., Mom's House, Main Office">
                                </div>
                                <div class="form-group">
                                    <label>Full Name *</label>
                                    <input type="text" name="full_name" required placeholder="Enter recipient's full name">
                                </div>
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" placeholder="e.g., +63 912 345 6789">
                                </div>
                                <div class="form-group full-width">
                                    <label>Address Line 1 *</label>
                                    <input type="text" name="address_line1" required placeholder="Street address, building number, P.O. box">
                                </div>
                                <div class="form-group full-width">
                                    <label>Address Line 2</label>
                                    <input type="text" name="address_line2" placeholder="Apartment, suite, unit, floor (optional)">
                                </div>
                                <div class="form-group">
                                    <label>City *</label>
                                    <input type="text" name="city" required placeholder="Enter city name">
                                </div>
                                <div class="form-group">
                                    <label>State/Province</label>
                                    <input type="text" name="state_province" placeholder="Enter state or province">
                                </div>
                                <div class="form-group">
                                    <label>Postal Code</label>
                                    <input type="text" name="postal_code" placeholder="Enter postal/ZIP code">
                                </div>
                                <div class="form-group">
                                    <label>Country</label>
                                    <input type="text" name="country" value="Philippines" placeholder="Enter country name">
                                </div>
                                <div class="form-group full-width">
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="is_default" id="is_default">
                                        <label for="is_default">Set as default address</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px;">
                                <button type="submit" name="add_address" class="btn btn-primary">💾 Save Address</button>
                                <button type="button" class="btn btn-secondary" onclick="toggleAddressForm()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        function toggleAddressForm() {
            const form = document.getElementById('add-address-form');
            form.classList.toggle('show');
        }
        
        function confirmLogout() {
            if (confirm('Are you sure you want to logout? You will need to sign in again to access your account.')) {
                window.location.href = '../logout.php';
            }
        }
        
        // Add smooth animations to form inputs
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });
        });
    </script>
</body>
</html>