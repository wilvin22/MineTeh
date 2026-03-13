<?php
session_start();
include '../database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all cart items
$cart_items = $supabase->select('cart', '*', ['user_id' => $user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            background: #f5f5f5;
        }

        #main-content {
            flex-grow: 1;
            padding: 30px;
            max-width: 1200px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .page-subtitle {
            font-size: 16px;
            color: #666;
        }

        .cart-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
        }

        .cart-items {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .cart-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s ease;
        }

        .cart-item:hover {
            background: #f8f9fa;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
        }

        .item-details {
            flex: 1;
        }

        .item-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            cursor: pointer;
        }

        .item-title:hover {
            color: #945a9b;
        }

        .item-price {
            font-size: 20px;
            font-weight: bold;
            color: #945a9b;
            margin-bottom: 8px;
        }

        .item-seller {
            font-size: 13px;
            color: #666;
        }

        .item-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            justify-content: center;
        }

        .btn-remove {
            padding: 8px 16px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-remove:hover {
            background: #bb2d3b;
        }

        .btn-checkout-item {
            padding: 8px 16px;
            background: #945a9b;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-checkout-item:hover {
            background: #6a406e;
        }

        .cart-summary {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .summary-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
        }

        .summary-label {
            color: #666;
        }

        .summary-value {
            font-weight: 600;
            color: #333;
        }

        .summary-total {
            border-top: 2px solid #e9ecef;
            padding-top: 15px;
            margin-top: 15px;
        }

        .summary-total .summary-value {
            font-size: 24px;
            color: #945a9b;
        }

        .btn-checkout-all {
            width: 100%;
            padding: 15px;
            background: #945a9b;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.2s ease;
        }

        .btn-checkout-all:hover {
            background: #6a406e;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .browse-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #945a9b;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .browse-btn:hover {
            background: #6a406e;
            transform: translateY(-2px);
        }

        @media (max-width: 968px) {
            .cart-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div id="main-content" class="main-wrapper">
        <div class="page-header">
            <div class="page-title">🛒 Your Cart</div>
            <div class="page-subtitle">Review items before checkout</div>
        </div>

        <?php if (empty($cart_items)): ?>
            <div class="cart-items">
                <div class="empty-state">
                    <div class="empty-state-icon">🛒</div>
                    <h2>Your cart is empty</h2>
                    <p>Add items to your cart to get started</p>
                    <a href="homepage.php" class="browse-btn">Browse Items</a>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <?php
                    $total = 0;
                    foreach ($cart_items as $item) {
                        $listing = $supabase->select('listings', '*', ['id' => $item['listing_id']], true);
                        
                        if ($listing && $listing['listing_type'] === 'FIXED') {
                            $listing_id = $listing['id'];
                            $title = htmlspecialchars($listing['title']);
                            $price = $listing['price'];
                            $total += $price;
                            
                            // Get seller info
                            $seller = $supabase->select('accounts', 'username,first_name', ['account_id' => $listing['seller_id']], true);
                            
                            // Get first image
                            $images = $supabase->select('listing_images', 'image_path', ['listing_id' => $listing_id]);
                            $listing_image = !empty($images) ? $images[0]['image_path'] : '../assets/no-image.png';
                            
                            echo "
                            <div class='cart-item'>
                                <img src='$listing_image' 
                                     alt='Item' 
                                     class='item-image'
                                     onclick='window.location.href=\"listing-details.php?id=$listing_id\"'>
                                <div class='item-details'>
                                    <div class='item-title' onclick='window.location.href=\"listing-details.php?id=$listing_id\"'>
                                        $title
                                    </div>
                                    <div class='item-price'>₱" . number_format($price, 2) . "</div>
                                    <div class='item-seller'>Sold by: " . htmlspecialchars($seller['first_name']) . "</div>
                                </div>
                                <div class='item-actions'>
                                    <a href='checkout.php?listing_id=$listing_id' class='btn-checkout-item'>
                                        Checkout
                                    </a>
                                    <button onclick='removeFromCart($listing_id)' class='btn-remove'>
                                        Remove
                                    </button>
                                </div>
                            </div>
                            ";
                        }
                    }
                    ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-title">Order Summary</div>
                    <div class="summary-row">
                        <span class="summary-label">Items (<?php echo count($cart_items); ?>)</span>
                        <span class="summary-value">₱<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="summary-row summary-total">
                        <span class="summary-label">Total</span>
                        <span class="summary-value">₱<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div style="font-size: 12px; color: #666; margin-top: 15px; text-align: center;">
                        Note: Checkout each item individually
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function removeFromCart(listingId) {
            if (!confirm('Remove this item from your cart?')) return;

            // Show loading state
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Removing...';
            button.disabled = true;

            fetch('../actions/cart-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    listing_id: listingId,
                    action: 'remove'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message briefly before reload
                    button.textContent = 'Removed!';
                    button.style.background = '#28a745';
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    alert('Failed to remove item: ' + (data.message || 'Unknown error'));
                    // Restore button state
                    button.textContent = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error removing item from cart');
                // Restore button state
                button.textContent = originalText;
                button.disabled = false;
            });
        }
    </script>
</body>
</html>
