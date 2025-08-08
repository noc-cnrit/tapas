<?php
/**
 * Simple API Endpoint Test
 * Test if API endpoints are accessible and working
 * 
 * This script will help identify API endpoint issues on production
 */

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>🔍 API Endpoint Tests</h1>\n";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>\n";

// Test URLs
$tests = [
    'Menu API (Food)' => '/api/get_menu_data.php?menu=food',
    'Menu API (All)' => '/api/get_menu_data.php?menu=all',
    'Item Details API' => '/get_item_details.php?id=1',
    'Test DB Connection' => '/test-db-connection.php',
    'Test API General' => '/test-api.php'
];

echo "<h2>📋 Test Results</h2>\n";
echo "<table border='1' style='border-collapse: collapse; margin: 20px 0; width: 100%;'>\n";
echo "<tr><th style='padding: 8px;'>Endpoint</th><th style='padding: 8px;'>Status</th><th style='padding: 8px;'>Response</th></tr>\n";

foreach ($tests as $name => $url) {
    $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
               '://' . $_SERVER['HTTP_HOST'] . $url;
    
    echo "<tr>";
    echo "<td style='padding: 8px;'><strong>$name</strong><br><small><a href='$fullUrl' target='_blank'>$url</a></small></td>";
    
    // Test the endpoint
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Endpoint Test Script');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "<td style='padding: 8px; background: #d4edda; color: #155724;'>✅ HTTP $httpCode</td>";
        
        // Check if it's JSON
        $jsonData = json_decode($response, true);
        if ($jsonData !== null) {
            if (isset($jsonData['success']) && $jsonData['success']) {
                echo "<td style='padding: 8px; background: #d4edda;'>✅ JSON Valid - Success</td>";
            } else {
                echo "<td style='padding: 8px; background: #f8d7da;'>❌ JSON Error: " . 
                     htmlspecialchars($jsonData['error'] ?? 'Unknown error') . "</td>";
            }
        } else {
            $preview = substr(strip_tags($response), 0, 100);
            echo "<td style='padding: 8px; background: #d1ecf1;'>📄 HTML Response: " . 
                 htmlspecialchars($preview) . "...</td>";
        }
    } else {
        echo "<td style='padding: 8px; background: #f8d7da; color: #721c24;'>❌ HTTP $httpCode</td>";
        
        if ($error) {
            echo "<td style='padding: 8px; background: #f8d7da;'>❌ cURL Error: " . htmlspecialchars($error) . "</td>";
        } else {
            $preview = substr(strip_tags($response), 0, 100);
            echo "<td style='padding: 8px; background: #f8d7da;'>❌ Error: " . htmlspecialchars($preview) . "</td>";
        }
    }
    
    echo "</tr>\n";
}

echo "</table>\n";

echo "<hr>\n";
echo "<h2>🔧 Manual Testing</h2>\n";
echo "<p>Click these links to manually test each endpoint:</p>\n";
echo "<ul>\n";
foreach ($tests as $name => $url) {
    $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
               '://' . $_SERVER['HTTP_HOST'] . $url;
    echo "<li><a href='$fullUrl' target='_blank'>$name: $url</a></li>\n";
}
echo "</ul>\n";

echo "<hr>\n";
echo "<p><strong>🔒 Delete this file after testing for security!</strong></p>\n";
?>
