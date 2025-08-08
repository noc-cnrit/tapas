<?php
/**
 * Browse Local Images
 * Returns JSON list of images for section photo selection
 * Updated to work with local image archive after WordPress elimination
 */

require_once '../classes/Auth.php';

// Require authentication
Auth::requireAuth();

header('Content-Type: application/json');

function getImagesFromDirectory($dir, $baseUrl = '') {
    $images = [];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!is_dir($dir)) {
        return $images;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
            if (in_array($extension, $allowedExtensions)) {
                $relativePath = str_replace('\\', '/', str_replace('../', '', $file->getPathname()));
                $relativePath = str_replace(str_replace('\\', '/', realpath('../')), '', str_replace('\\', '/', realpath($file->getPathname())));
                $relativePath = ltrim($relativePath, '/');
                
                $filename = $file->getFilename();
                
                // Get file info
                $fileInfo = [
                    'path' => $relativePath,
                    'filename' => $filename,
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                    'base_name' => $filename // For local images, use filename as base name
                ];
                
                $images[] = $fileInfo;
            }
        }
    }
    
    return $images;
}

try {
    // Local images directories - check multiple locations
    $imageDirectories = [
        '../images/archive',  // Main archive directory
        '../images/food',     // Food images directory
        '../images'           // Root images directory
    ];
    
    $allImages = [];
    
    // Gather images from all directories
    $imagesByBaseName = []; // Track images by their base name to avoid duplicates
    
    foreach ($imageDirectories as $dir) {
        if (is_dir($dir)) {
            $dirImages = getImagesFromDirectory($dir);
            
            foreach ($dirImages as $img) {
                // Create a normalized base name for deduplication
                $baseName = strtolower(pathinfo($img['filename'], PATHINFO_FILENAME));
                $baseName = preg_replace('/[-\s]+/', '-', $baseName); // Normalize spaces and dashes
                $baseName = preg_replace('/^(food-|plate-)?/', '', $baseName); // Remove common prefixes
                
                // Only keep the newest version of each image
                if (!isset($imagesByBaseName[$baseName]) || 
                    $img['modified'] > $imagesByBaseName[$baseName]['modified']) {
                    $imagesByBaseName[$baseName] = $img;
                }
            }
        }
    }
    
    // Convert back to indexed array
    $allImages = array_values($imagesByBaseName);
    
    // Sort by modification date (newest first)
    usort($allImages, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    // Get search term from query parameter
    $searchTerm = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
    
    if (!empty($searchTerm)) {
        // Server-side search: filter images by search term
        $matchingImages = [];
        $partialMatches = [];
        
        foreach ($allImages as $img) {
            $filename = strtolower($img['filename']);
            $baseName = strtolower($img['base_name']);
            
            // Exact matches get highest priority
            if (strpos($filename, $searchTerm) === 0 || strpos($baseName, $searchTerm) === 0) {
                array_unshift($matchingImages, $img);
            }
            // Contains matches get lower priority
            elseif (strpos($filename, $searchTerm) !== false || strpos($baseName, $searchTerm) !== false) {
                $partialMatches[] = $img;
            }
        }
        
        // Combine exact matches first, then partial matches
        $images = array_merge($matchingImages, $partialMatches);
        
        // Limit search results to 50 most relevant
        $images = array_slice($images, 0, 50);
        
    } else {
        // No search term: show recent files with some priority logic
        $priorityFiles = [];
        $regularFiles = [];
        
        foreach ($allImages as $img) {
            // Prioritize food-related images
            if (stripos($img['filename'], 'food') !== false || 
                stripos($img['filename'], 'dish') !== false ||
                stripos($img['filename'], 'chicken') !== false ||
                stripos($img['filename'], 'cheese') !== false) {
                $priorityFiles[] = $img;
            } else {
                $regularFiles[] = $img;
            }
        }
        
        // Put priority files first, then regular files
        $images = array_merge($priorityFiles, $regularFiles);
        
        // Limit to 100 for browsing
        $images = array_slice($images, 0, 100);
    }
    
    echo json_encode([
        'success' => true,
        'images' => $images,
        'count' => count($images),
        'total_found' => count($allImages),
        'directories_scanned' => $imageDirectories
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'images' => [],
        'count' => 0
    ]);
}
?>
