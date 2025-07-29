<?php
/**
 * Simple FTP Connection Test
 */

// Read configuration
$config = json_decode(file_get_contents('config.json'), true);
$ftp = $config['ftp'];

echo "Testing FTP connection...\n";
echo "Host: {$ftp['host']}\n";
echo "User: {$ftp['username']}\n";
echo "Port: {$ftp['port']}\n\n";

// Test FTP connection with cURL
$testUrl = "ftp://{$ftp['host']}/";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_USERPWD, $ftp['username'] . ':' . $ftp['password']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FTPLISTONLY, true);

echo "Attempting to connect and list directory...\n";
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($result !== false && empty($error)) {
    echo "✓ Connection successful!\n";
    echo "Directory listing:\n";
    echo $result;
} else {
    echo "✗ Connection failed\n";
    echo "Error: $error\n";
    echo "Response code: $httpCode\n";
    
    echo "\nSuggestions:\n";
    echo "1. Verify FTP credentials in your Siteground cPanel\n";
    echo "2. Check if FTP is enabled for your account\n";
    echo "3. Try using SFTP (port 22) instead of FTP (port 21)\n";
    echo "4. Ensure your IP is not blocked by firewall\n";
}
?>
