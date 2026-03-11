<?php
session_start();
header('Content-Type: application/json');

require_once '../database/supabase.php';

// Test 1: Check if riders table exists
try {
    $test = $supabase->customQuery('riders', 'rider_id', 'limit=1');
    $table_exists = true;
} catch (Exception $e) {
    $table_exists = false;
    $table_error = $e->getMessage();
}

// Test 2: Check if accounts table has is_rider column
try {
    $test_account = $supabase->select('accounts', 'account_id,is_rider', ['account_id' => 1], true);
    $column_exists = true;
} catch (Exception $e) {
    $column_exists = false;
    $column_error = $e->getMessage();
}

// Test 3: Try to create a test account
$test_username = 'test_rider_' . time();
$account_data = [
    'username' => $test_username,
    'email' => $test_username . '@test.com',
    'password_hash' => password_hash('test123', PASSWORD_DEFAULT),
    'first_name' => 'Test',
    'last_name' => 'Rider',
    'is_admin' => false,
    'is_rider' => true,
    'created_at' => date('Y-m-d H:i:s')
];

$account_result = $supabase->insert('accounts', $account_data);
$account_created = $account_result !== false;

if ($account_created) {
    // Get the account_id
    $account = $supabase->select('accounts', 'account_id', ['username' => $test_username], true);
    $account_id = $account['account_id'] ?? null;
    
    // Test 4: Try to create rider profile
    if ($account_id) {
        $rider_data = [
            'account_id' => $account_id,
            'full_name' => 'Test Rider',
            'phone_number' => '09123456789',
            'vehicle_type' => 'motorcycle',
            'license_number' => 'TEST-123',
            'status' => 'active',
            'rating' => 5.00,
            'total_deliveries' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $rider_result = $supabase->insert('riders', $rider_data);
        $rider_created = $rider_result !== false;
    } else {
        $rider_created = false;
        $rider_error = 'Could not get account_id';
    }
} else {
    $rider_created = false;
    $rider_error = 'Account creation failed';
}

// Output results
echo json_encode([
    'tests' => [
        'riders_table_exists' => $table_exists,
        'riders_table_error' => $table_error ?? null,
        'is_rider_column_exists' => $column_exists,
        'is_rider_column_error' => $column_error ?? null,
        'account_created' => $account_created,
        'account_id' => $account_id ?? null,
        'rider_created' => $rider_created,
        'rider_error' => $rider_error ?? null
    ],
    'recommendation' => !$table_exists ? 
        'Run add_rider_system_tables.sql in Supabase SQL Editor' : 
        ($rider_created ? 'Everything works! Check admin/riders.php' : 'Check error details above')
], JSON_PRETTY_PRINT);
