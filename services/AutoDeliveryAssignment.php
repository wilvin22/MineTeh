<?php
/**
 * Automated Delivery Assignment Service
 * Automatically assigns deliveries to riders when orders are placed
 */

class AutoDeliveryAssignment {
    private $supabase;
    
    public function __construct($supabase) {
        $this->supabase = $supabase;
    }
    
    /**
     * Automatically assign a delivery when an order is created
     * @param int $order_id The order ID
     * @return array Result with success status and message
     */
    public function assignDeliveryForOrder($order_id) {
        try {
            // Get order details
            $order = $this->supabase->select('orders', '*', ['order_id' => $order_id], true);
            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }
            
            // Skip if delivery method is pickup
            if ($order['delivery_method'] === 'pickup') {
                return ['success' => true, 'message' => 'Pickup order - no delivery needed'];
            }
            
            // Get listing details for pickup address
            $listing = $this->supabase->select('listings', '*', ['id' => $order['listing_id']], true);
            if (!$listing) {
                return ['success' => false, 'message' => 'Listing not found'];
            }
            
            // Get seller details for pickup address
            $seller = $this->supabase->select('accounts', '*', ['account_id' => $listing['seller_id']], true);
            if (!$seller) {
                return ['success' => false, 'message' => 'Seller not found'];
            }
            
            // Get buyer details for customer info
            $buyer = $this->supabase->select('accounts', '*', ['account_id' => $order['buyer_id']], true);
            if (!$buyer) {
                return ['success' => false, 'message' => 'Buyer not found'];
            }
            
            // Find best available rider
            $rider = $this->findBestRider($order['delivery_address']);
            if (!$rider) {
                return ['success' => false, 'message' => 'No available riders found'];
            }
            
            // Calculate delivery fee based on delivery method
            $delivery_fee = $this->calculateDeliveryFee($order['delivery_method'], $order['order_amount']);
            
            // Create pickup address from seller info
            $pickup_address = $this->formatPickupAddress($seller, $listing);
            
            // Create delivery record
            $delivery_data = [
                'order_id' => $order_id,
                'rider_id' => $rider['rider_id'],
                'pickup_address' => $pickup_address,
                'delivery_address' => $order['delivery_address'],
                'customer_name' => $buyer['first_name'] . ' ' . $buyer['last_name'],
                'customer_phone' => $buyer['phone'] ?? 'Not provided',
                'delivery_fee' => $delivery_fee,
                'notes' => 'Auto-assigned delivery for order #' . $order_id,
                'status' => 'assigned',
                'delivery_status' => 'assigned',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'assigned_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->supabase->insert('deliveries', $delivery_data);
            
            if ($result) {
                // Update order with delivery info
                $this->supabase->update('orders', [
                    'delivery_status' => 'assigned',
                    'delivery_fee' => $delivery_fee,
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['order_id' => $order_id]);
                
                // Update rider stats
                $this->updateRiderStats($rider['rider_id']);
                
                // Create notifications
                $this->createDeliveryNotifications($order, $rider, $delivery_data);
                
                return [
                    'success' => true, 
                    'message' => 'Delivery assigned to ' . $rider['full_name'],
                    'rider' => $rider,
                    'delivery_fee' => $delivery_fee
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create delivery record'];
            }
            
        } catch (Exception $e) {
            error_log('Auto delivery assignment error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'System error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Find the best available rider for a delivery
     * Uses intelligent assignment algorithm considering:
     * - Rider availability and status
     * - Current workload
     * - Rating and performance
     * - Geographic proximity (future enhancement)
     */
    private function findBestRider($delivery_address) {
        // Get all active riders
        $riders = $this->supabase->customQuery('riders', '*', 'status=eq.active&order=rating.desc,total_deliveries.asc');
        
        if (!$riders || empty($riders)) {
            return null;
        }
        
        $best_rider = null;
        $best_score = -1;
        
        foreach ($riders as $rider) {
            // Get rider's current active deliveries
            $active_deliveries = $this->supabase->count('deliveries', [
                'rider_id' => $rider['rider_id'],
                'delivery_status' => ['assigned', 'picked_up', 'in_transit']
            ]);
            
            // Skip if rider has too many active deliveries (max 3)
            if ($active_deliveries >= 3) {
                continue;
            }
            
            // Calculate rider score
            $score = $this->calculateRiderScore($rider, $active_deliveries);
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_rider = $rider;
            }
        }
        
        return $best_rider;
    }
    
    /**
     * Calculate rider score for assignment priority
     */
    private function calculateRiderScore($rider, $active_deliveries) {
        $score = 0;
        
        // Rating factor (0-50 points)
        $score += ($rider['rating'] / 5.0) * 50;
        
        // Availability factor (0-30 points) - fewer active deliveries = higher score
        $availability_score = max(0, (3 - $active_deliveries) / 3.0) * 30;
        $score += $availability_score;
        
        // Experience factor (0-20 points) - more deliveries = higher score, but with diminishing returns
        $experience_score = min(20, ($rider['total_deliveries'] / 10.0) * 20);
        $score += $experience_score;
        
        return $score;
    }
    
    /**
     * Calculate delivery fee based on delivery method and order amount
     */
    private function calculateDeliveryFee($delivery_method, $order_amount) {
        switch ($delivery_method) {
            case 'express':
                return max(100, $order_amount * 0.15); // 15% of order or ₱100 minimum
            case 'standard':
                return max(50, $order_amount * 0.10);  // 10% of order or ₱50 minimum
            default:
                return 50; // Default fee
        }
    }
    
    /**
     * Format pickup address from seller and listing info
     */
    private function formatPickupAddress($seller, $listing) {
        $address = $seller['first_name'] . ' ' . $seller['last_name'] . "\n";
        
        // Use listing location if available, otherwise use seller email as contact
        if (!empty($listing['location'])) {
            $address .= $listing['location'];
        } else {
            $address .= "Contact seller: " . $seller['email'];
        }
        
        return $address;
    }
    
    /**
     * Update rider statistics
     */
    private function updateRiderStats($rider_id) {
        // Get current stats
        $rider = $this->supabase->select('riders', 'total_deliveries', ['rider_id' => $rider_id], true);
        
        if ($rider) {
            $this->supabase->update('riders', [
                'total_deliveries' => $rider['total_deliveries'] + 1,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['rider_id' => $rider_id]);
        }
    }
    
    /**
     * Create notifications for delivery assignment
     */
    private function createDeliveryNotifications($order, $rider, $delivery_data) {
        try {
            // Include notifications helper if available
            if (file_exists('../database/notifications_helper.php')) {
                require_once '../database/notifications_helper.php';
                $notificationHelper = new NotificationsHelper();
                
                // Notify rider about new delivery
                $rider_account = $this->supabase->select('accounts', 'account_id', ['account_id' => $rider['account_id']], true);
                if ($rider_account) {
                    $notificationHelper->createNotification(
                        $rider_account['account_id'],
                        'delivery_assigned',
                        'New Delivery Assigned',
                        'You have been assigned a new delivery. Pickup: ' . substr($delivery_data['pickup_address'], 0, 50) . '...',
                        'rider/dashboard.php'
                    );
                }
                
                // Notify buyer about delivery assignment
                $notificationHelper->createNotification(
                    $order['buyer_id'],
                    'delivery_assigned',
                    'Delivery Assigned',
                    'Your order has been assigned to rider ' . $rider['full_name'] . ' for delivery.',
                    'your-orders.php'
                );
            }
        } catch (Exception $e) {
            error_log('Notification creation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get delivery assignment statistics
     */
    public function getAssignmentStats() {
        try {
            $stats = [
                'total_deliveries' => $this->supabase->count('deliveries', []),
                'assigned_today' => $this->supabase->customQuery('deliveries', 'delivery_id', 
                    'created_at=gte.' . date('Y-m-d') . 'T00:00:00'),
                'active_deliveries' => $this->supabase->count('deliveries', [
                    'delivery_status' => ['assigned', 'picked_up', 'in_transit']
                ]),
                'completed_deliveries' => $this->supabase->count('deliveries', [
                    'delivery_status' => 'delivered'
                ])
            ];
            
            $stats['assigned_today'] = $stats['assigned_today'] ? count($stats['assigned_today']) : 0;
            
            return $stats;
        } catch (Exception $e) {
            return [
                'total_deliveries' => 0,
                'assigned_today' => 0,
                'active_deliveries' => 0,
                'completed_deliveries' => 0
            ];
        }
    }
}
?>