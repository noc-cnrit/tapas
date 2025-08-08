<?php
/**
 * Admin URL Routing Test
 * Test if admin URL routing is working properly
 */

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>🔧 Admin URL Routing Test</h1>\n";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>\n";

// Test admin URLs  
$adminUrls = [
    'Admin Dashboard' => '/admin/',
    'Admin Sections' => '/admin/sections',
    'Admin Items' => '/admin/items', 
    'Admin Menus' => '/admin/menus',
    'Admin Login' => '/admin/login',
    'Admin QR Print' => '/admin/qr-print',
    'Admin Browse Images' => '/admin/browse-images',
    'Admin Change Password' => '/admin/change-password'
];

// Also test direct .php access
$directUrls = [
    'Sections.php Direct' => '/admin/sections.php',
    'Items.php Direct' => '/admin/items.php',
    'Menus.php Direct' => '/admin/menus.php'
];

echo "<h2>📋 Admin URL Test Results</h2>\n";
echo "<table border='1' style='border-collapse: collapse; margin: 20px 0; width: 100%;'>\n";
echo "<tr><th style='padding: 8px;'>URL</th><th style='padding: 8px;'>Status</th><th style='padding: 8px;'>Response</th></tr>\n";

$allUrls = array_merge($adminUrls, $directUrls);

foreach ($allUrls as $name => $url) {
    $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
               '://' . $_SERVER['HTTP_HOST'] . $url;
    
    echo "<tr>";
    echo "<td style='padding: 8px;'><strong>$name</strong><br><small><a href='$fullUrl' target='_blank'>$url</a></small></td>";
    
    // Test the URL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Admin URL Test Script');
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "<td style='padding: 8px; background: #d4edda; color: #155724;'>✅ HTTP $httpCode</td>";
        echo "<td style='padding: 8px; background: #d4edda;'>✅ Working</td>";
    } elseif ($httpCode === 302 || $httpCode === 301) {
        echo "<td style='padding: 8px; background: #fff3cd; color: #856404;'>🔀 HTTP $httpCode</td>";
        echo "<td style='padding: 8px; background: #fff3cd;'>🔀 Redirect" . ($redirectUrl ? " to: " . htmlspecialchars($redirectUrl) : "") . "</td>";
    } elseif ($httpCode === 404) {
        echo "<td style='padding: 8px; background: #f8d7da; color: #721c24;'>❌ HTTP 404</td>";
        echo "<td style='padding: 8px; background: #f8d7da;'>❌ Not Found - URL routing issue</td>";
    } else {
        echo "<td style='padding: 8px; background: #f8d7da; color: #721c24;'>❌ HTTP $httpCode</td>";
        
        if ($error) {
            echo "<td style='padding: 8px; background: #f8d7da;'>❌ cURL Error: " . htmlspecialchars($error) . "</td>";
        } else {
            echo "<td style='padding: 8px; background: #f8d7da;'>❌ Error Response</td>";
        }
    }
    
    echo "</tr>\n";
}

echo "</table>\n";

echo "<hr>\n";
echo "<h2>🔧 .htaccess Admin Routing Rules</h2>\n";
echo "<p>The .htaccess file should contain these rules for admin URLs to work:</p>\n";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "# Admin section clean URLs\n";
echo "RewriteRule ^admin/sections/?$ /admin/sections.php [L,QSA]\n";
echo "RewriteRule ^admin/items/?$ /admin/items.php [L,QSA]\n";
echo "RewriteRule ^admin/menus/?$ /admin/menus.php [L,QSA]\n";
echo "RewriteRule ^admin/login/?$ /admin/login.php [L,QSA]\n";
echo "RewriteRule ^admin/qr-print/?$ /admin/qr-print.php [L,QSA]\n";
echo "RewriteRule ^admin/browse-images/?$ /admin/browse_images.php [L,QSA]\n";
echo "RewriteRule ^admin/change-password/?$ /admin/change_password.php [L,QSA]";
echo "</pre>\n";

echo "<hr>\n";
echo "<h2>🔧 Manual Testing</h2>\n";
echo "<p>Click these links to manually test each admin URL:</p>\n";
echo "<ul>\n";
foreach ($allUrls as $name => $url) {
    $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
               '://' . $_SERVER['HTTP_HOST'] . $url;
    echo "<li><a href='$fullUrl' target='_blank'>$name: $url</a></li>\n";
}
echo "</ul>\n";

echo "<hr>\n";
echo "<p><strong>🔒 Delete this file after testing for security!</strong></p>\n";
?>
