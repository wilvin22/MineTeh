<?php
session_start();
include 'database/supabase.php';

echo "<h1>Database Column Test</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } table { border-collapse: collapse; width: 100%; margin: 20px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background: #945a9b; color: white; } .error { color: red; } .success { color: green; }</style>";

// Test 1: Get one listing
echo "<h2>Test 1: Get One Listing (using 'id' column)</h2>";
$test1 = $supabase->customQuery('listings', '*', 'limit=1');
if ($test1 && is_array($test1) && !empty($test1)) {
    echo "<p class='success'>✓ Success! Found listing</p>";
    echo "<pre>" . print_r($test1[0], true) . "</pre>";
    echo "<p><strong>Available columns:</strong> " . implode(', ', array_keys($test1[0])) . "</p>";
} else {
    echo "<p class='error'>✗ Failed to get listing</p>";
    $error = $supabase->getLastError();
    if ($error) {
        echo "<pre>" . print_r($error, true) . "</pre>";
    }
}

// Test 2: Get one account
echo "<h2>Test 2: Get One Account</h2>";
$test2 = $supabase->customQuery('accounts', '*', 'limit=1');
if ($test2 && is_array($test2) && !empty($test2)) {
    echo "<p class='success'>✓ Success! Found account</p>";
    echo "<pre>" . print_r($test2[0], true) . "</pre>";
    echo "<p><strong>Available columns:</strong> " . implode(', ', array_keys($test2[0])) . "</p>";
} else {
    echo "<p class='error'>✗ Failed to get account</p>";
    $error = $supabase->getLastError();
    if ($error) {
        echo "<pre>" . print_r($error, true) . "</pre>";
    }
}

// Test 3: Check RLS policies
echo "<h2>Test 3: RLS Policy Check</h2>";
echo "<p>If queries are failing, you may need to disable RLS or add policies in Supabase dashboard:</p>";
echo "<ol>";
echo "<li>Go to your Supabase dashboard</li>";
echo "<li>Click on 'Authentication' → 'Policies'</li>";
echo "<li>For each table (listings, accounts, etc.), either:</li>";
echo "<ul><li>Disable RLS (for development), OR</li>";
echo "<li>Add policies to allow SELECT for anon/authenticated users</li></ul>";
echo "</ol>";

echo "<h2>Quick Fix SQL (Run in Supabase SQL Editor)</h2>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
echo "-- Disable RLS for all tables (DEVELOPMENT ONLY)\n";
echo "ALTER TABLE accounts DISABLE ROW LEVEL SECURITY;\n";
echo "ALTER TABLE listings DISABLE ROW LEVEL SECURITY;\n";
echo "ALTER TABLE bids DISABLE ROW LEVEL SECURITY;\n";
echo "ALTER TABLE favorites DISABLE ROW LEVEL SECURITY;\n";
echo "ALTER TABLE listing_images DISABLE ROW LEVEL SECURITY;\n";
echo "ALTER TABLE messages DISABLE ROW LEVEL SECURITY;\n";
echo "ALTER TABLE conversations DISABLE ROW LEVEL SECURITY;\n";
echo "</pre>";
?>
