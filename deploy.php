<?php
/**
 * Tapas Restaurant Website Deployment Script
 * Uploads files to Siteground production server via FTP
 */

// Read configuration
$config = json_decode(file_get_contents('config.json'), true);
if (!$config) {
    die("Error: Could not read config.json\n");
}

$ftp = $config['ftp'];
$filesToDeploy = $config['deployment']['files_to_deploy'];

echo "=== Tapas Restaurant Deployment Script ===\n";
echo "Target: {$ftp['host']}\n";
echo "User: {$ftp['username']}\n";
echo "Root Path: {$ftp['root_path']}\n\n";

// Connect to FTP server
$ftpConnection = ftp_connect($ftp['host'], $ftp['port']);
if (!$ftpConnection) {
    die("Error: Could not connect to FTP server {$ftp['host']}\n");
}

// Login to FTP server
$login = ftp_login($ftpConnection, $ftp['username'], $ftp['password']);
if (!$login) {
    die("Error: Could not login to FTP server\n");
}

// Enable passive mode
ftp_pasv($ftpConnection, true);

echo "✓ Connected to FTP server successfully\n\n";

// Change to root directory
if (!ftp_chdir($ftpConnection, $ftp['root_path'])) {
    die("Error: Could not change to root directory {$ftp['root_path']}\n");
}

/**
 * Upload a file to FTP server
 */
function uploadFile($ftp, $localFile, $remoteFile) {
    echo "Uploading: $localFile -> $remoteFile\n";
    
    if (!file_exists($localFile)) {
        echo "  ⚠ Warning: Local file does not exist: $localFile\n";
        return false;
    }
    
    // Create remote directory if needed
    $remoteDir = dirname($remoteFile);
    if ($remoteDir != '.' && $remoteDir != '') {
        createRemoteDirectory($ftp, $remoteDir);
    }
    
    if (ftp_put($ftp, $remoteFile, $localFile, FTP_BINARY)) {
        echo "  ✓ Success\n";
        return true;
    } else {
        echo "  ✗ Failed\n";
        return false;
    }
}

/**
 * Create remote directory recursively
 */
function createRemoteDirectory($ftp, $dir) {
    $parts = explode('/', $dir);
    $path = '';
    
    foreach ($parts as $part) {
        if ($part == '') continue;
        
        $path .= $part . '/';
        
        // Try to create directory (ignore if it already exists)
        @ftp_mkdir($ftp, $path);
    }
}

/**
 * Upload directory recursively
 */
function uploadDirectory($ftp, $localDir, $remoteDir = '') {
    if (!is_dir($localDir)) {
        echo "  ⚠ Warning: Local directory does not exist: $localDir\n";
        return;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($localDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        $localPath = $file->getPathname();
        $relativePath = substr($localPath, strlen($localDir) + 1);
        $remotePath = $remoteDir ? $remoteDir . '/' . $relativePath : $relativePath;
        
        // Convert Windows paths to Unix paths
        $remotePath = str_replace('\\', '/', $remotePath);
        
        if ($file->isFile()) {
            uploadFile($ftp, $localPath, $remotePath);
        }
    }
}

// Deploy each file/directory
$successCount = 0;
$totalCount = 0;

foreach ($filesToDeploy as $item) {
    echo "Processing: $item\n";
    
    if (is_file($item)) {
        // Single file
        $totalCount++;
        if (uploadFile($ftpConnection, $item, basename($item))) {
            $successCount++;
        }
    } elseif (is_dir($item)) {
        // Directory - count files for progress
        $dirIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($item, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $fileCount = iterator_count($dirIterator);
        $totalCount += $fileCount;
        
        echo "  Found $fileCount files in directory\n";
        uploadDirectory($ftpConnection, $item, basename($item));
        $successCount += $fileCount; // Assume success for now
    } else {
        echo "  ⚠ Warning: Item not found: $item\n";
    }
    
    echo "\n";
}

// Close FTP connection
ftp_close($ftpConnection);

echo "=== Deployment Complete ===\n";
echo "Files processed: $totalCount\n";
echo "Status: Deployment finished\n";
echo "\nYour website should now be updated at: http://platestpete.com\n";
?>
