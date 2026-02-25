<?php
/**
 * Script to find all PHP files that still use MySQL (database.php)
 * Run this to see which files need to be migrated to Supabase
 */

function scanDirectory($dir, &$results = []) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === '.git') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            scanDirectory($path, $results);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path);
            
            // Check for MySQL usage
            if (strpos($content, 'database.php') !== false || 
                strpos($content, 'mysqli_') !== false ||
                strpos($content, '$conn') !== false) {
                $results[] = $path;
            }
        }
    }
    
    return $results;
}

echo "=== Files Still Using MySQL ===\n\n";

$mysqlFiles = scanDirectory('.');

if (empty($mysqlFiles)) {
    echo "✅ No files found using MySQL!\n";
} else {
    echo "Found " . count($mysqlFiles) . " file(s) that need migration:\n\n";
    
    foreach ($mysqlFiles as $file) {
        echo "❌ " . $file . "\n";
    }
    
    echo "\n=== Migration Instructions ===\n\n";
    echo "Replace:\n";
    echo "  include 'database/database.php';\n";
    echo "With:\n";
    echo "  include 'database/supabase.php';\n\n";
    
    echo "Replace MySQL queries with Supabase methods:\n";
    echo "  mysqli_query() → \$supabase->select()\n";
    echo "  mysqli_fetch_assoc() → (already returns array)\n";
    echo "  mysqli_insert_id() → (returned in insert response)\n\n";
    
    echo "See SUPABASE_MIGRATION.md for detailed examples.\n";
}
?>
