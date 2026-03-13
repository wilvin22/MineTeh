<?php
// Test file to verify rider page functionality
session_start();
require_once 'database/supabase.php';

echo "<h1>Rider System Test</h1>";
echo "<pre>";

// Test 1: Check if riders table exists
echo "\n=== TEST 1: Check Riders Table ===\n";
try {
    $riders = $supabase->customQuery('riders', '*', 'limit=1');
    echo "✓ Riders table exists\n";
    echo "Sample data: " . json_encode($riders, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 2: Check if is_rider column exists in accounts
echo "\n=== TEST 2: Check is_rider Column ===\n";
try {
    $accounts = $supabase->customQuery('accounts', 'account_id,username,is_rider', 'limit=5');
    echo "✓ is_rider column exists\n";
    echo "Sample accounts: " . json_encode($accounts, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Check accounts that are not riders yet
echo "\n=== TEST 3: Available Accounts for Rider Conversion ===\n";
try {
    $available = $supabase->customQuery('accounts', 'account_id,username,first_name,last_name,email,is_rider', 
        'is_rider=eq.false&order=username.asc');
    echo "✓ Found " . count($available) . " accounts that can be converted to riders\n";
    foreach ($available as $acc) {
        echo "  - {$acc['username']} ({$acc['first_name']} {$acc['last_name']}) - is_rider: " . 
             ($acc['is_rider'] ? 'true' : 'false') . "\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 4: Check existing riders
echo "\n=== TEST 4: Existing Riders ===\n";
try {
    $riders = $supabase->customQuery('riders', '*', 'order=created_at.desc');
    echo "✓ Found " . count($riders) . " riders\n";
    foreach ($riders as $rider) {
        echo "  - Rider #{$rider['rider_id']}: {$rider['full_name']} ({$rider['phone_number']}) - Status: {$rider['status']}\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 5: Check admin session
echo "\n=== TEST 5: Session Check ===\n";
if (isset($_SESSION['user_id'])) {
    echo "✓ User logged in: user_id = {$_SESSION['user_id']}\n";
    $user = $supabase->select('accounts', '*', ['account_id' => $_SESSION['user_id']], true);
    if ($user) {
        echo "  - Username: {$user['username']}\n";
        echo "  - Is Admin: " . ($user['is_admin'] ? 'Yes' : 'No') . "\n";
        echo "  - Is Rider: " . ($user['is_rider'] ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "✗ No user logged in\n";
    echo "  Please login at admin/login.php first\n";
}

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. Make sure you've run add_rider_system_tables.sql\n";
echo "2. Login as admin at admin/login.php\n";
echo "3. Go to admin/riders.php to manage riders\n";
echo "4. Click 'Add New Rider' to create a rider\n";
echo "5. Test rider login at login.php\n";

echo "</pre>";
?>
