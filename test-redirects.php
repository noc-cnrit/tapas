<?php
/**
 * Simple redirect testing script
 * Tests that legacy URLs properly redirect to new format
 */

echo "<h1>🔄 URL Redirect Testing</h1>\n";
echo "<p>This script helps verify that old URLs properly redirect to the new format.</p>\n";

// Test URLs to check
$testUrls = [
    // Legacy menu paths
    '/menu' => '/index.php',
    '/menu/' => '/index.php',
    '/menu/food' => '/index.php?menu=food',
    '/menu/food/' => '/index.php?menu=food',
    '/menu/sushi' => '/index.php?menu=sushi', 
    '/menu/sushi/' => '/index.php?menu=sushi',
    '/menu/drinks' => '/index.php?menu=drinks',
    '/menu/drinks/' => '/index.php?menu=drinks',
    '/menu/special' => '/index.php?menu=food',
    '/menu/special/' => '/index.php?menu=food',
    '/menu/chefs_specials' => '/index.php?menu=chefs_specials',
    '/menu/chef_specials' => '/index.php?menu=chefs_specials',
    
    // Legacy HTML files
    '/index.html' => '/aboutus.html',
    '/menu.html' => '/index.php',
    '/special.html' => '/index.php?menu=chefs_specials',
    '/gallery.html' => '/index.php',
    '/drinks.html' => '/index.php?menu=drinks',
];

echo "<h2>📋 Expected Redirects:</h2>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr style='background-color: #f0f0f0;'><th>Old URL</th><th>Should Redirect To</th><th>Status</th></tr>\n";

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

foreach ($testUrls as $oldUrl => $expectedRedirect) {
    $fullOldUrl = $baseUrl . $oldUrl;
    $fullExpectedUrl = $baseUrl . $expectedRedirect;
    
    echo "<tr>";
    echo "<td><code>{$oldUrl}</code></td>";
    echo "<td><code>{$expectedRedirect}</code></td>";
    echo "<td><a href='{$fullOldUrl}' target='_blank' style='color: blue;'>🔗 Test</a></td>";
    echo "</tr>\n";
}

echo "</table>\n";

echo "<h2>💡 How to Test:</h2>\n";
echo "<ul>\n";
echo "<li>Click the 'Test' links above to verify redirects work</li>\n";
echo "<li>Each should redirect to the expected new URL with a 301 status</li>\n";
echo "<li>Old URLs should <strong>never</strong> show 404 errors</li>\n";
echo "<li>Use browser dev tools (Network tab) to verify 301 redirects</li>\n";
echo "</ul>\n";

echo "<h2>🧪 curl Testing Commands:</h2>\n";
echo "<p>Run these commands in terminal to test redirects:</p>\n";
echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 5px;'>\n";

foreach (array_slice($testUrls, 0, 5) as $oldUrl => $expectedRedirect) {
    $curlCmd = "curl -I \"{$baseUrl}{$oldUrl}\"";
    echo "{$curlCmd}\n";
}
echo "</pre>\n";

echo "<p><strong>Expected response:</strong> HTTP/1.1 301 Moved Permanently</p>\n";

echo "<div style='margin-top: 30px; padding: 15px; background-color: #e8f5e8; border-radius: 5px;'>\n";
echo "<h3>✅ What This Achieves:</h3>\n";
echo "<ul>\n";
echo "<li>Prevents 404 errors for existing bookmarks</li>\n";
echo "<li>Maintains SEO value through 301 redirects</li>\n"; 
echo "<li>Ensures QR codes continue working</li>\n";
echo "<li>Handles old HTML file references</li>\n";
echo "<li>Provides seamless user experience during transition</li>\n";
echo "</ul>\n";
echo "</div>\n";

// Clean up - remove this file after testing
echo "<p style='color: #666; font-size: 0.9em; margin-top: 30px;'>";
echo "Note: Delete this test file (test-redirects.php) after verifying redirects work properly.";
echo "</p>\n";
?>
