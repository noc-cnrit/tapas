<?php
/**
 * Tapas Restaurant Website Deployment Script (cURL version)
 * Uploads files to Siteground production server via FTP using cURL
 */

// Read configuration
$config = json_decode(file_get_contents('config.json'), true);
if (!$config) {
    die("Error: Could not read config.json\n");
}

$ftp = $config['ftp'];
$filesToDeploy = $config['deployment']['files_to_deploy'];

echo "=== Tapas Restaurant Deployment Script (cURL) ===\n";
echo "Target: {$ftp['host']}\n";
echo "User: {$ftp['username']}\n";
echo "Root Path: {$ftp['root_path']}\n\n";

/**
 * Upload a file using cURL
 */
function uploadFileCurl($ftpConfig, $localFile, $remoteFile) {
    echo "Uploading: $localFile -> $remoteFile\n";
    
    if (!file_exists($localFile)) {
        echo "  ⚠ Warning: Local file does not exist: $localFile\n";
        return false;
    }
    
    $ftpUrl = "ftp://{$ftpConfig['host']}{$ftpConfig['root_path']}{$remoteFile}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ftpUrl);
    curl_setopt($ch, CURLOPT_USERPWD, $ftpConfig['username'] . ':' . $ftpConfig['password']);
    curl_setopt($ch, CURLOPT_UPLOAD, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FTP_CREATE_MISSING_DIRS, 1);
    
    $file = fopen($localFile, 'r');
    curl_setopt($ch, CURLOPT_INFILE, $file);
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localFile));
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    fclose($file);
    
    if ($result && empty($error)) {
        echo "  ✓ Success\n";
        return true;
    } else {
        echo "  ✗ Failed: $error\n";
        return false;
    }
}

/**
 * Upload directory recursively using cURL
 */
function uploadDirectoryCurl($ftpConfig, $localDir, $remoteDir = '') {
    if (!is_dir($localDir)) {
        echo "  ⚠ Warning: Local directory does not exist: $localDir\n";
        return 0;
    }
    
    $successCount = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($localDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $localPath = $file->getPathname();
            $relativePath = substr($localPath, strlen($localDir) + 1);
            $remotePath = $remoteDir ? $remoteDir . '/' . $relativePath : $relativePath;
            
            // Convert Windows paths to Unix paths
            $remotePath = str_replace('\\', '/', $remotePath);
            
            if (uploadFileCurl($ftpConfig, $localPath, $remotePath)) {
                $successCount++;
            }
        }
    }
    
    return $successCount;
}

// Deploy each file/directory
$successCount = 0;
$totalCount = 0;

foreach ($filesToDeploy as $item) {
    echo "Processing: $item\n";
    
    if (is_file($item)) {
        // Single file
        $totalCount++;
        if (uploadFileCurl($ftp, $item, basename($item))) {
            $successCount++;
        }
    } elseif (is_dir($item)) {
        // Directory - count files for progress
        $dirIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($item, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $fileCount = 0;
        foreach ($dirIterator as $file) {
            if ($file->isFile()) $fileCount++;
        }
        $totalCount += $fileCount;
        
        echo "  Found $fileCount files in directory\n";
        $uploaded = uploadDirectoryCurl($ftp, $item, basename($item));
        $successCount += $uploaded;
    } else {
        echo "  ⚠ Warning: Item not found: $item\n";
    }
    
    echo "\n";
}

echo "=== Deployment Complete ===\n";
echo "Files processed: $totalCount\n";
echo "Files uploaded successfully: $successCount\n";
echo "Status: " . ($successCount == $totalCount ? "All files uploaded successfully" : "Some files failed to upload") . "\n";
echo "\nYour website should now be updated at: http://platestpete.com\n";
?>
