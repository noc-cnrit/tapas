<?php
/**
 * Quick Thumbnail Generator
 * Generates and serves small thumbnails for image browsing
 */

require_once '../classes/Auth.php';

// Require authentication
Auth::requireAuth();

// Get image path from query parameter
$imagePath = isset($_GET['path']) ? $_GET['path'] : '';
$size = isset($_GET['size']) ? (int)$_GET['size'] : 150;

if (empty($imagePath)) {
    http_response_code(400);
    exit('No image path provided');
}

// Security: Prevent directory traversal
$imagePath = str_replace(['../', '..\\'], '', $imagePath);
$fullPath = '../' . $imagePath;

if (!file_exists($fullPath) || !is_file($fullPath)) {
    http_response_code(404);
    exit('Image not found');
}

// Check if it's a valid image
$imageInfo = getimagesize($fullPath);
if (!$imageInfo) {
    http_response_code(400);
    exit('Invalid image file');
}

$mimeType = $imageInfo['mime'];
$width = $imageInfo[0];
$height = $imageInfo[1];

// Create cache directory
$cacheDir = '../cache/thumbnails/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Generate cache filename
$cacheKey = md5($imagePath . $size . filemtime($fullPath));
$extension = pathinfo($fullPath, PATHINFO_EXTENSION);
$cacheFile = $cacheDir . $cacheKey . '.' . $extension;

// Serve cached thumbnail if it exists
if (file_exists($cacheFile)) {
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($cacheFile));
    header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
    readfile($cacheFile);
    exit;
}

// Generate thumbnail
try {
    // Load source image
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($fullPath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($fullPath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($fullPath);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($fullPath);
            break;
        default:
            http_response_code(400);
            exit('Unsupported image type');
    }
    
    if (!$sourceImage) {
        http_response_code(500);
        exit('Failed to load image');
    }
    
    // Calculate thumbnail dimensions (maintain aspect ratio)
    $ratio = min($size / $width, $size / $height);
    $newWidth = (int)round($width * $ratio);
    $newHeight = (int)round($height * $ratio);
    
    // Create thumbnail
    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save to cache
    switch ($mimeType) {
        case 'image/jpeg':
            imagejpeg($thumbnail, $cacheFile, 85);
            break;
        case 'image/png':
            imagepng($thumbnail, $cacheFile, 6);
            break;
        case 'image/gif':
            imagegif($thumbnail, $cacheFile);
            break;
        case 'image/webp':
            imagewebp($thumbnail, $cacheFile, 85);
            break;
    }
    
    // Output thumbnail
    header('Content-Type: ' . $mimeType);
    header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
    
    switch ($mimeType) {
        case 'image/jpeg':
            imagejpeg($thumbnail, null, 85);
            break;
        case 'image/png':
            imagepng($thumbnail, null, 6);
            break;
        case 'image/gif':
            imagegif($thumbnail);
            break;
        case 'image/webp':
            imagewebp($thumbnail, null, 85);
            break;
    }
    
    // Cleanup
    imagedestroy($sourceImage);
    imagedestroy($thumbnail);
    
} catch (Exception $e) {
    http_response_code(500);
    exit('Thumbnail generation failed: ' . $e->getMessage());
}
?>
