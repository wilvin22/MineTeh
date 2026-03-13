<?php
session_start();
include '../config.php';
include '../database/supabase.php';
include '../database/notifications_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['listing_id'])) {
    header("Location: homepage.php");
    exit;
}

$listing_id = (int)$_GET['listing_id'];
$user_id = $_SESSION['user_id'];

// Get listing details
$listing = $supabase->select('listings', '*', ['id' => $listing_id], true);

if (!$listing) {
    die("Listing not found");
}

// Get seller info
$seller = $supabase->select('accounts', 'username,first_name,last_name,email', ['account_id' => $listing['seller_id']], true);

// Get buyer info
$buyer = $supabase->select('accounts', '*', ['account_id' => $user_id], true);

// Get user addresses
$user_addresses = $supabase->select('user_addresses', '*', ['user_id' => $user_id]);
$default_address = null;
foreach ($user_addresses as $addr) {
    if ($addr['is_default']) {
        $default_address = $addr;
        break;
    }
}

// Process order
if (isset($_POST['place_order'])) {
    $delivery_address = '';
    
    // Handle address selection
    if ($_POST['address_option'] === 'saved' && !empty($_POST['saved_address_id'])) {
        $address_id = (int)$_POST['saved_address_id'];
        $selected_address = $supabase->select('user_addresses', '*', ['address_id' => $address_id, 'user_id' => $user_id], true);
        if ($selected_address) {
            $delivery_address = $selected_address['full_name'] . "\n" . 
                               $selected_address['address_line1'] . "\n" . 
                               ($selected_address['address_line2'] ? $selected_address['address_line2'] . "\n" : '') .
                               $selected_address['city'] . ", " . $selected_address['state_province'] . " " . $selected_address['postal_code'] . "\n" .
                               $selected_address['country'] . "\n" .
                               ($selected_address['phone'] ? "Phone: " . $selected_address['phone'] : '');
        }
    } else {
        $delivery_address = trim($_POST['delivery_address']);
    }
    
    $delivery_method = $_POST['delivery_method'];
    $payment_method = $_POST['payment_method'];
    
    $order_data = [
        'buyer_id' => $user_id,
        'seller_id' => $listing['seller_id'],
        'listing_id' => $listing_id,
        'order_amount' => $listing['price'],
        'payment_method' => $payment_method,
        'payment_status' => 'pending',
        'delivery_address' => $delivery_address,
        'delivery_method' => $delivery_method,
        'order_status' => 'pending'
    ];
    
    $result = $supabase->insert('orders', $order_data);
    
    if ($result && !empty($result[0])) {
        $order_id = $result[0]['order_id'];
        
        // Update listing status to sold
        $supabase->update('listings', ['status' => 'sold'], ['id' => $listing_id]);
        
        // Auto-assign delivery if needed
        if ($delivery_method !== 'pickup') {
            require_once '../services/AutoDeliveryAssignment.php';
            $deliveryService = new AutoDeliveryAssignment($supabase);
            $assignment_result = $deliveryService->assignDeliveryForOrder($order_id);
            
            // Log assignment result
            if ($assignment_result['success']) {
                error_log("Auto-delivery assigned for order $order_id: " . $assignment_result['message']);
            } else {
                error_log("Auto-delivery assignment failed for order $order_id: " . $assignment_result['message']);
            }
        }
        
        // Create notification helper
        $notificationHelper = new NotificationsHelper();
        
        // Notify buyer
        $notificationHelper->notifyOrderUpdate(
            $user_id,
            $order_id,
            'confirmed',
            $listing['title']
        );
        
        // Notify seller
        $notificationHelper->createNotification(
            $listing['seller_id'],
            'order_update',
            'New Order Received!',
            $buyer['username'] . ' ordered "' . $listing['title'] . '" for ₱' . number_format($listing['price'], 2),
            'your-orders.php'
        );
        
        header("Location: order-confirmation.php?order_id=$order_id");
        exit;
    } else {
        // Log the actual error for debugging
        error_log("Order creation failed. Result: " . print_r($result, true));
        error_log("Order data: " . print_r($order_data, true));
        $error = "Failed to place order. Please try again. If the problem persists, please contact support.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        .checkout-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        .checkout-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #945a9b;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .radio-option:hover {
            border-color: #945a9b;
            background: #f8f9fa;
        }

        .radio-option input[type="radio"] {
            width: auto;
            margin-right: 12px;
        }

        .radio-option.selected {
            border-color: #945a9b;
            background: #f8f4f9;
        }

        .order-summary {
            position: sticky;
            top: 20px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 20px 0;
            font-size: 20px;
            font-weight: bold;
            color: #945a9b;
        }

        .place-order-btn {
            width: 100%;
            padding: 15px;
            background: #945a9b;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .place-order-btn:hover {
            background: #6a406e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(148, 90, 155, 0.3);
        }

        .item-preview {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .item-details {
            flex: 1;
        }

        .item-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .item-price {
            color: #945a9b;
            font-weight: bold;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        @media (max-width: 968px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="checkout-container">
            <h1>Checkout</h1>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="checkout-grid">
                    <!-- Left Column -->
                    <div>
                        <!-- Delivery Information -->
                        <div class="checkout-section">
                            <h2 class="section-title">Delivery Information</h2>
                            
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" value="<?php echo htmlspecialchars($buyer['first_name'] . ' ' . $buyer['last_name']); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" value="<?php echo htmlspecialchars($buyer['email']); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label>Delivery Address *</label>
                                
                                <?php if (!empty($user_addresses)): ?>
                                <div class="address-selection">
                                    <div class="radio-group" style="margin-bottom: 15px;">
                                        <label class="radio-option">
                                            <input type="radio" name="address_option" value="saved" <?php echo !empty($user_addresses) ? 'checked' : ''; ?>>
                                            <div>
                                                <strong>Use Saved Address</strong>
                                                <div style="font-size: 12px; color: #666;">Select from your saved addresses</div>
                                            </div>
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" name="address_option" value="new">
                                            <div>
                                                <strong>Enter New Address</strong>
                                                <div style="font-size: 12px; color: #666;">Type a different address</div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div id="saved-addresses" style="<?php echo !empty($user_addresses) ? '' : 'display: none;'; ?>">
                                        <select name="saved_address_id" class="address-select">
                                            <?php foreach ($user_addresses as $address): ?>
                                            <option value="<?php echo $address['address_id']; ?>" <?php echo ($address['is_default']) ? 'selected' : ''; ?>>
                                                <?php 
                                                $icons = ['home' => '🏠', 'work' => '🏢', 'other' => '📍'];
                                                echo $icons[$address['address_type']] ?? '📍';
                                                echo ' ' . $address['full_name'];
                                                if ($address['address_label']) echo ' (' . $address['address_label'] . ')';
                                                echo ' - ' . $address['address_line1'] . ', ' . $address['city'];
                                                if ($address['is_default']) echo ' (Default)';
                                                ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div style="margin-top: 10px; font-size: 12px;">
                                            <a href="account-settings.php" target="_blank" style="color: #945a9b;">Manage your addresses</a>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div id="manual-address" style="<?php echo empty($user_addresses) ? '' : 'display: none;'; ?>">
                                    <textarea id="delivery_address" name="delivery_address" placeholder="Enter your complete delivery address" <?php echo empty($user_addresses) ? 'required' : ''; ?>></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Delivery Method *</label>
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="delivery_method" value="standard" checked>
                                        <div>
                                            <strong>Standard Delivery</strong>
                                            <div style="font-size: 12px; color: #666;">3-5 business days</div>
                                        </div>
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="delivery_method" value="express">
                                        <div>
                                            <strong>Express Delivery</strong>
                                            <div style="font-size: 12px; color: #666;">1-2 business days</div>
                                        </div>
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="delivery_method" value="pickup">
                                        <div>
                                            <strong>Pickup</strong>
                                            <div style="font-size: 12px; color: #666;">Arrange with seller</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="checkout-section">
                            <h2 class="section-title">Payment Method</h2>
                            
                            <div class="form-group">
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="payment_method" value="cod" checked>
                                        <div>
                                            <strong>Cash on Delivery</strong>
                                            <div style="font-size: 12px; color: #666;">Pay when you receive</div>
                                        </div>
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="payment_method" value="gcash">
                                        <div>
                                            <strong>GCash</strong>
                                            <div style="font-size: 12px; color: #666;">Digital payment</div>
                                        </div>
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="payment_method" value="bank">
                                        <div>
                                            <strong>Bank Transfer</strong>
                                            <div style="font-size: 12px; color: #666;">Direct bank deposit</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Order Summary -->
                    <div>
                        <div class="checkout-section order-summary">
                            <h2 class="section-title">Order Summary</h2>
                            
                            <!-- Item Preview -->
                            <div class="item-preview">
                                <?php 
                                $image = $supabase->select('listing_images', 'image_path', ['listing_id' => $listing_id]);
                                $image_path = !empty($image) ? getImageUrl($image[0]['image_path']) : BASE_URL . '/assets/no-image.png';
                                ?>
                                <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Item" class="item-image">
                                <div class="item-details">
                                    <div class="item-title"><?php echo htmlspecialchars($listing['title']); ?></div>
                                    <div style="font-size: 12px; color: #666;">
                                        Sold by: <?php echo htmlspecialchars($seller['username']); ?>
                                    </div>
                                    <div class="item-price">₱<?php echo number_format($listing['price'], 2); ?></div>
                                </div>
                            </div>

                            <!-- Price Breakdown -->
                            <div class="summary-item">
                                <span>Subtotal</span>
                                <span>₱<?php echo number_format($listing['price'], 2); ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Shipping Fee</span>
                                <span>₱0.00</span>
                            </div>
                            <div class="summary-total">
                                <span>Total</span>
                                <span>₱<?php echo number_format($listing['price'], 2); ?></span>
                            </div>

                            <button type="submit" name="place_order" class="place-order-btn">
                                Place Order
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add selected class to radio options
        document.querySelectorAll('.radio-option input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                this.closest('.radio-group').querySelectorAll('.radio-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                if (this.checked) {
                    this.closest('.radio-option').classList.add('selected');
                }
            });
            
            // Set initial selected state
            if (radio.checked) {
                radio.closest('.radio-option').classList.add('selected');
            }
        });

        // Handle address selection toggle
        document.querySelectorAll('input[name="address_option"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const savedAddresses = document.getElementById('saved-addresses');
                const manualAddress = document.getElementById('manual-address');
                const deliveryTextarea = document.getElementById('delivery_address');
                
                if (this.value === 'saved') {
                    savedAddresses.style.display = 'block';
                    manualAddress.style.display = 'none';
                    deliveryTextarea.removeAttribute('required');
                } else {
                    savedAddresses.style.display = 'none';
                    manualAddress.style.display = 'block';
                    deliveryTextarea.setAttribute('required', 'required');
                }
            });
        });
    </script>
</body>
</html>
