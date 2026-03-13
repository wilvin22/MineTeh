<?php
/**
 * Batch Update Script - Add Responsive CSS/JS to All Pages
 * Run this once to update all PHP pages with responsive includes
 */

// Pages to update (relative to this script)
$pages = [
    // Home pages
    'home/dashboard.php',
    'home/create-listing.php',
    'home/listing-details.php',
    'home/your-listings.php',
    'home/bids.php',
    'home/cart.php',
    'home/saved-items.php',
    'home/checkout.php',
    'home/order-confirmation.php',
    'home/your-orders.php',
    'home/messages.php',
    'home/notifications.php',
    'home/account-settings.php',
    'home/search.php',
    
    // Admin pages
    'admin/dashboard.php',
    'admin/login.php',
    'admin/users.php',
    'admin/listings.php',
    'admin/orders.php',
    'admin/categories.php',
    'admin/riders.php',
    'admin/delivery-monitor.php',
    
    // Rider pages
    'rider/dashboard.php',
    'rider/delivery-details.php',
    'rider/proof-of-delivery.php',
];

$updated = [];
$skipped = [];
$errors = [];

foreach ($pages as $page) {
    if (!file_exists($page)) {
        $skipped[] = "$page (file not found)";
        continue;
    }
    
    $content = file_get_contents($page);
    $original = $content;
    $modified = false;
    
    // Determine the correct path prefix based on directory
    $pathPrefix = '../';
    if (strpos($page, 'admin/') === 0 || strpos($page, 'rider/') === 0 || strpos($page, 'home/') === 0) {
        $pathPrefix = '../';
    } else {
        $pathPrefix = '';
    }
    
    // Check if responsive.css is already included
    if (strpos($content, 'responsive.css') === false) {
        // Find </style> tag and add responsive.css after it
        if (preg_match('/<\/style>/i', $content)) {
            $content = preg_replace(
                '/<\/style>\s*(<\/head>)/i',
                "</style>\n    \n    <!-- Responsive CSS -->\n    <link rel=\"stylesheet\" href=\"{$pathPrefix}css/responsive.css\">\n$1",
                $content,
                1
            );
            $modified = true;
        }
        // Or find </head> tag directly
        elseif (preg_match('/<\/head>/i', $content)) {
            $content = preg_replace(
                '/<\/head>/i',
                "    <!-- Responsive CSS -->\n    <link rel=\"stylesheet\" href=\"{$pathPrefix}css/responsive.css\">\n</head>",
                $content,
                1
            );
            $modified = true;
        }
    }
    
    // Check if responsive.js is already included
    if (strpos($content, 'responsive.js') === false) {
        // Find </body> tag and add responsive.js before it
        if (preg_match('/<\/body>/i', $content)) {
            $content = preg_replace(
                '/<\/body>/i',
                "    \n    <!-- Responsive JavaScript -->\n    <script src=\"{$pathPrefix}js/responsive.js\"></script>\n</body>",
                $content,
                1
            );
            $modified = true;
        }
    }
    
    // Check if viewport meta tag exists
    if (strpos($content, 'viewport') === false) {
        // Find <head> tag and add viewport after it
        if (preg_match('/<head>/i', $content)) {
            $content = preg_replace(
                '/<head>/i',
                "<head>\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, maximum-scale=5.0\">",
                $content,
                1
            );
            $modified = true;
        }
    }
    
    if ($modified && $content !== $original) {
        if (file_put_contents($page, $content)) {
            $updated[] = $page;
        } else {
            $errors[] = "$page (write failed)";
        }
    } else {
        $skipped[] = "$page (already updated or no changes needed)";
    }
}

// Output results
echo "<!DOCTYPE html>\n";
echo "<html>\n<head>\n";
echo "<title>Responsive Update Results</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }\n";
echo ".success { color: #4CAF50; }\n";
echo ".skipped { color: #FF9800; }\n";
echo ".error { color: #f44336; }\n";
echo "ul { background: white; padding: 20px; border-radius: 5px; }\n";
echo "h2 { margin-top: 30px; }\n";
echo "</style>\n";
echo "</head>\n<body>\n";

echo "<h1>Responsive Update Results</h1>\n";

echo "<h2 class='success'>✅ Updated (" . count($updated) . " files)</h2>\n";
if (!empty($updated)) {
    echo "<ul>\n";
    foreach ($updated as $file) {
        echo "<li>$file</li>\n";
    }
    echo "</ul>\n";
}

echo "<h2 class='skipped'>⚠️ Skipped (" . count($skipped) . " files)</h2>\n";
if (!empty($skipped)) {
    echo "<ul>\n";
    foreach ($skipped as $file) {
        echo "<li>$file</li>\n";
    }
    echo "</ul>\n";
}

if (!empty($errors)) {
    echo "<h2 class='error'>❌ Errors (" . count($errors) . " files)</h2>\n";
    echo "<ul>\n";
    foreach ($errors as $file) {
        echo "<li>$file</li>\n";
    }
    echo "</ul>\n";
}

echo "<h2>Next Steps:</h2>\n";
echo "<ol>\n";
echo "<li>Test your pages on mobile devices</li>\n";
echo "<li>Check browser console for any errors (F12)</li>\n";
echo "<li>Adjust styling in css/responsive.css if needed</li>\n";
echo "<li>Delete this script after use: <code>update_responsive_all_pages.php</code></li>\n";
echo "</ol>\n";

echo "<p><a href='login.php'>Go to Login Page</a> | <a href='home/homepage.php'>Go to Homepage</a></p>\n";

echo "</body>\n</html>";
?>
