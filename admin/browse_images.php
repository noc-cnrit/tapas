<?php
/**
 * Browse Images from WordPress Uploads
 * Returns JSON list of images for section photo selection
 */

require_once '../classes/Auth.php';

// Require authentication
Auth::requireAuth();

header('Content-Type: application/json');

function getImagesFromDirectory($dir, $baseUrl = '') {
    $images = [];
    $imageGroups = []; // Group images by base name
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
                
                // Extract base name (remove WordPress size suffixes like -150x150, -300x300, etc.)
                $baseName = preg_replace('/-\d+x\d+\./', '.', $filename);
                
                // Get file info
                $fileInfo = [
                    'path' => $relativePath,
                    'filename' => $filename,
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                    'base_name' => $baseName
                ];
                
                // Group by base name
                if (!isset($imageGroups[$baseName])) {
                    $imageGroups[$baseName] = [];
                }
                $imageGroups[$baseName][] = $fileInfo;
            }
        }
    }
    
    // For each group, pick the best version to display
    foreach ($imageGroups as $baseName => $versions) {
        $bestVersion = null;
        $priority = 0;
        
        foreach ($versions as $version) {
            $currentPriority = 0;
            
            // Priority system: prefer medium sizes, then thumbnails, then originals
            if (strpos($version['filename'], '-300x') !== false) {
                $currentPriority = 100; // Highest priority for 300x versions
            } elseif (strpos($version['filename'], '-150x150.') !== false) {
                $currentPriority = 90; // High priority for 150x150 thumbnails
            } elseif (strpos($version['filename'], '-') === false) {
                $currentPriority = 50; // Medium priority for original files
            } else {
                $currentPriority = 10; // Lower priority for other sizes
            }
            
            if ($currentPriority > $priority) {
                $priority = $currentPriority;
                $bestVersion = $version;
            }
        }
        
        if ($bestVersion) {
            $images[] = $bestVersion;
        }
    }
    
    return $images;
}

try {
    // WordPress uploads directory
    $uploadsDir = '../wp/wp-content/uploads';
    $images = getImagesFromDirectory($uploadsDir);
    
    // Get search term from query parameter
    $searchTerm = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
    
    if (!empty($searchTerm)) {
        // Server-side search: filter images by search term
        $matchingImages = [];
        $partialMatches = [];
        
        foreach ($images as $img) {
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
        // No search term: show recent files with priority logic
        $cheeseFiles = [];
        $regularFiles = [];
        
        foreach ($images as $img) {
            if (stripos($img['filename'], 'cheesy') !== false || stripos($img['filename'], 'tator') !== false) {
                $cheeseFiles[] = $img;
            } else {
                $regularFiles[] = $img;
            }
        }
        
        // Put cheesy files first, then regular files
        $images = array_merge($cheeseFiles, $regularFiles);
        
        // Limit to 100 for browsing
        $images = array_slice($images, 0, 100);
    }
    
    echo json_encode([
        'success' => true,
        'images' => $images,
        'count' => count($images),
        'debug' => [
            'total_files_found' => count($images),
            'found_cheesy' => $foundCheesy,
            'cheesy_files' => $cheeseFiles
        ]
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
