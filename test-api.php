<?php
/**
 * API Test Page
 * Test API endpoints to debug production issues
 */

// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 API Endpoint Testing</h1>\n";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>\n";

// Test menu data API
echo "<h2>📋 Testing Menu Data API</h2>\n";
$apiUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
          '://' . $_SERVER['HTTP_HOST'] . '/api/get_menu_data.php?menu=food';

echo "<p><strong>Testing URL:</strong> <a href='{$apiUrl}' target='_blank'>{$apiUrl}</a></p>\n";

// Make a local request to the API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>API Response:</h3>\n";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
echo "<tr><th>Property</th><th>Value</th></tr>\n";
echo "<tr><td>HTTP Code</td><td>{$httpCode}</td></tr>\n";
echo "<tr><td>cURL Error</td><td>" . ($error ? htmlspecialchars($error) : 'None') . "</td></tr>\n";
echo "<tr><td>Response Length</td><td>" . strlen($response) . " characters</td></tr>\n";
echo "</table>\n";

if ($response) {
    echo "<h4>Response Content:</h4>\n";
    if ($httpCode === 200) {
        $jsonData = json_decode($response, true);
        if ($jsonData) {
            echo "<p>✅ <strong>Valid JSON response!</strong></p>\n";
            echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
            echo htmlspecialchars(json_encode($jsonData, JSON_PRETTY_PRINT));
            echo "</pre>\n";
            
            if (isset($jsonData['success']) && $jsonData['success']) {
                echo "<p>✅ <strong>API Success:</strong> " . count($jsonData['menus']) . " menus returned</p>\n";
            } else {
                echo "<p>❌ <strong>API Error:</strong> " . ($jsonData['error'] ?? 'Unknown error') . "</p>\n";
            }
        } else {
            echo "<p>❌ <strong>Invalid JSON response</strong></p>\n";
            echo "<pre style='background: #ffeeee; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
            echo htmlspecialchars($response);
            echo "</pre>\n";
        }
    } else {
        echo "<p>❌ <strong>HTTP Error {$httpCode}</strong></p>\n";
        echo "<pre style='background: #ffeeee; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
        echo htmlspecialchars($response);
        echo "</pre>\n";
    }
} else {
    echo "<p>❌ <strong>No response received</strong></p>\n";
}

// Test database connection
echo "<hr>\n";
echo "<h2>🗄️ Database Connection Test</h2>\n";

try {
    require_once 'config/database.php';
    $pdo = getDBConnection();
    echo "<p>✅ <strong>Database connection successful!</strong></p>\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM menu_items LIMIT 1");
    $result = $stmt->fetch();
    echo "<p>✅ <strong>Database query successful:</strong> {$result['count']} menu items found</p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ <strong>Database connection failed:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test environment detection
echo "<hr>\n";
echo "<h2>🌍 Environment Information</h2>\n";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
echo "<tr><th>Variable</th><th>Value</th></tr>\n";
echo "<tr><td>HTTP_HOST</td><td>" . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'N/A') . "</td></tr>\n";
echo "<tr><td>SERVER_NAME</td><td>" . htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'N/A') . "</td></tr>\n";
echo "<tr><td>DOCUMENT_ROOT</td><td>" . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</td></tr>\n";
echo "<tr><td>REQUEST_URI</td><td>" . htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A') . "</td></tr>\n";
echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>\n";
echo "</table>\n";

echo "<hr>\n";
echo "<p><em>🔒 Delete this test file after debugging!</em></p>\n";
?>
