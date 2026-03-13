<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Service Load Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;}</style>";

echo "<h2>Step 1: Database Connection</h2>";
try {
    require_once 'database/supabase.php';
    echo "<p class='success'>✅ Database loaded</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>Step 2: Service File Check</h2>";
$service_file = 'services/AutoDeliveryAssignment.php';
if (file_exists($service_file)) {
    echo "<p class='success'>✅ Service file exists</p>";
    
    echo "<h2>Step 3: Include Service</h2>";
    try {
        require_once $service_file;
        echo "<p class='success'>✅ Service included</p>";
        
        echo "<h2>Step 4: Create Instance</h2>";
        try {
            $service = new AutoDeliveryAssignment($supabase);
            echo "<p class='success'>✅ Service instantiated</p>";
            
            echo "<h2>Step 5: Test Method</h2>";
            try {
                $stats = $service->getAssignmentStats();
                echo "<p class='success'>✅ Method called successfully</p>";
                echo "<pre>" . print_r($stats, true) . "</pre>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ Method error: " . $e->getMessage() . "</p>";
                echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Instantiation error: " . $e->getMessage() . "</p>";
            echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Include error: " . $e->getMessage() . "</p>";
        echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<p class='error'>❌ Service file not found</p>";
}
?>