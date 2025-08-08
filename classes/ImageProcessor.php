<?php
/**
 * Image Processing and Optimization Class
 * Handles image uploads with automatic resizing, optimization, and thumbnail generation
 */

class ImageProcessor {
    
    // Image size configurations (similar to WordPress)
    private $imageSizes = [
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
        'medium' => ['width' => 300, 'height' => 300, 'crop' => false],
        'large' => ['width' => 800, 'height' => 600, 'crop' => false],
        'full' => ['width' => 1200, 'height' => 900, 'crop' => false]
    ];
    
    private $jpegQuality = 85;
    private $pngCompression = 6;
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private $maxFileSize = 10 * 1024 * 1024; // 10MB
    
    public function __construct() {
        // Ensure GD extension is available
        if (!extension_loaded('gd')) {
            throw new Exception('GD extension is required for image processing');
        }
    }
    
    /**
     * Process and upload an image with multiple sizes
     * 
     * @param array $file $_FILES array element
     * @param string $uploadDir Directory to save images
     * @param string $prefix Filename prefix
     * @return array Array of generated image paths and sizes
     */
    public function processUpload($file, $uploadDir, $prefix = 'item') {
        // Validate upload
        $this->validateUpload($file);
        
        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $originalExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $baseFilename = $prefix . '_' . time() . '_' . uniqid();
        
        // Load the original image
        $sourceImage = $this->loadImage($file['tmp_name'], $file['type']);
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);
        
        $generatedImages = [];
        
        // Generate different sizes
        foreach ($this->imageSizes as $sizeName => $sizeConfig) {
            $targetWidth = $sizeConfig['width'];
            $targetHeight = $sizeConfig['height'];
            $crop = $sizeConfig['crop'];
            
            // Skip if original is smaller than target (except for thumbnails)
            if ($sizeName !== 'thumbnail' && $originalWidth < $targetWidth && $originalHeight < $targetHeight) {
                continue;
            }
            
            // Calculate dimensions
            $dimensions = $this->calculateDimensions(
                $originalWidth, 
                $originalHeight, 
                $targetWidth, 
                $targetHeight, 
                $crop
            );
            
            // Create resized image
            $resizedImage = $this->resizeImage(
                $sourceImage, 
                $originalWidth, 
                $originalHeight,
                $dimensions['width'], 
                $dimensions['height'],
                $crop ? $dimensions : null
            );
            
            // Generate filename for this size
            $filename = $sizeName === 'full' ? 
                $baseFilename . '.' . $originalExtension :
                $baseFilename . '-' . $dimensions['width'] . 'x' . $dimensions['height'] . '.' . $originalExtension;
            
            $filepath = $uploadDir . $filename;
            
            // Save the image
            $this->saveImage($resizedImage, $filepath, $file['type']);
            imagedestroy($resizedImage);
            
            // Store relative path from web root
            $relativePath = str_replace('../', '', $uploadDir) . $filename;
            $relativePath = str_replace('\\', '/', $relativePath);
            
            $generatedImages[$sizeName] = [
                'path' => $relativePath,
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'filesize' => filesize($filepath)
            ];
        }
        
        imagedestroy($sourceImage);
        
        return $generatedImages;
    }
    
    /**
     * Validate uploaded file
     */
    private function validateUpload($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload failed with error code: ' . $file['error']);
        }
        
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File size exceeds maximum limit of ' . ($this->maxFileSize / 1024 / 1024) . 'MB');
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $this->allowedTypes));
        }
    }
    
    /**
     * Load image from file
     */
    private function loadImage($filepath, $mimeType) {
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($filepath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($filepath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($filepath);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($filepath);
                break;
            default:
                throw new Exception('Unsupported image type: ' . $mimeType);
        }
        
        if (!$image) {
            throw new Exception('Failed to load image');
        }
        
        return $image;
    }
    
    /**
     * Calculate target dimensions
     */
    private function calculateDimensions($originalWidth, $originalHeight, $targetWidth, $targetHeight, $crop = false) {
        if ($crop) {
            // For cropped images, we want exact dimensions
            return [
                'width' => $targetWidth,
                'height' => $targetHeight,
                'crop_x' => 0,
                'crop_y' => 0,
                'crop_width' => $originalWidth,
                'crop_height' => $originalHeight
            ];
        } else {
            // For non-cropped, maintain aspect ratio
            $ratio = min($targetWidth / $originalWidth, $targetHeight / $originalHeight);
            
            return [
                'width' => (int)round($originalWidth * $ratio),
                'height' => (int)round($originalHeight * $ratio)
            ];
        }
    }
    
    /**
     * Resize image
     */
    private function resizeImage($sourceImage, $sourceWidth, $sourceHeight, $targetWidth, $targetHeight, $cropData = null) {
        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
        
        // Preserve transparency for PNG and GIF
        $this->preserveTransparency($targetImage, $sourceImage);
        
        if ($cropData) {
            // Calculate crop dimensions to maintain aspect ratio
            $sourceRatio = $sourceWidth / $sourceHeight;
            $targetRatio = $targetWidth / $targetHeight;
            
            if ($sourceRatio > $targetRatio) {
                // Source is wider, crop width
                $cropHeight = $sourceHeight;
                $cropWidth = (int)($cropHeight * $targetRatio);
                $cropX = (int)(($sourceWidth - $cropWidth) / 2);
                $cropY = 0;
            } else {
                // Source is taller, crop height
                $cropWidth = $sourceWidth;
                $cropHeight = (int)($cropWidth / $targetRatio);
                $cropX = 0;
                $cropY = (int)(($sourceHeight - $cropHeight) / 2);
            }
            
            imagecopyresampled(
                $targetImage, $sourceImage,
                0, 0, $cropX, $cropY,
                $targetWidth, $targetHeight, $cropWidth, $cropHeight
            );
        } else {
            // Simple resize
            imagecopyresampled(
                $targetImage, $sourceImage,
                0, 0, 0, 0,
                $targetWidth, $targetHeight, $sourceWidth, $sourceHeight
            );
        }
        
        return $targetImage;
    }
    
    /**
     * Preserve transparency for PNG/GIF images
     */
    private function preserveTransparency($targetImage, $sourceImage) {
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        
        $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
        imagefill($targetImage, 0, 0, $transparent);
    }
    
    /**
     * Save image to file
     */
    private function saveImage($image, $filepath, $mimeType) {
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($image, $filepath, $this->jpegQuality);
                break;
            case 'image/png':
                imagepng($image, $filepath, $this->pngCompression);
                break;
            case 'image/gif':
                imagegif($image, $filepath);
                break;
            case 'image/webp':
                imagewebp($image, $filepath, $this->jpegQuality);
                break;
            default:
                throw new Exception('Unsupported save format: ' . $mimeType);
        }
    }
    
    /**
     * Delete generated images
     */
    public function deleteImages($baseImagePath) {
        $pathInfo = pathinfo($baseImagePath);
        $directory = $pathInfo['dirname'] . '/';
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        
        // Common size patterns to look for
        $patterns = [
            $filename . '.' . $extension, // original/full size
            $filename . '-*x*.' . $extension, // sized versions
        ];
        
        $deletedCount = 0;
        
        foreach ($patterns as $pattern) {
            $files = glob($directory . $pattern);
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                    $deletedCount++;
                }
            }
        }
        
        return $deletedCount;
    }
    
    /**
     * Get image size information
     */
    public function getImageInfo($imagePath) {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $info = getimagesize($imagePath);
        if (!$info) {
            return false;
        }
        
        return [
            'width' => $info[0],
            'height' => $info[1],
            'mime' => $info['mime'],
            'filesize' => filesize($imagePath)
        ];
    }
    
    /**
     * Get optimal image size for display context
     */
    public function getOptimalSize($context = 'medium') {
        $contexts = [
            'thumbnail' => 'thumbnail',   // Grid displays, icons
            'medium' => 'medium',         // Card displays
            'large' => 'large',           // Lightbox preview
            'full' => 'full'              // Full resolution
        ];
        
        return $contexts[$context] ?? 'medium';
    }
    
    /**
     * Rotate an existing image and regenerate all sizes
     * 
     * @param string $imagePath Path to the image file
     * @param int $degrees Rotation degrees (90, 180, 270)
     * @return bool Success status
     */
    public function rotateImage($imagePath, $degrees) {
        if (!file_exists($imagePath)) {
            throw new Exception('Image file not found: ' . $imagePath);
        }
        
        $validDegrees = [90, 180, 270, -90, -180, -270];
        if (!in_array($degrees, $validDegrees)) {
            throw new Exception('Invalid rotation degrees. Use 90, 180, or 270.');
        }
        
        // Get image info
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            throw new Exception('Unable to read image information.');
        }
        
        $mimeType = $imageInfo['mime'];
        
        // Load the image
        $sourceImage = $this->loadImage($imagePath, $mimeType);
        
        // Rotate the image
        $rotatedImage = imagerotate($sourceImage, -$degrees, 0); // Negative because imagerotate rotates counter-clockwise
        
        if (!$rotatedImage) {
            imagedestroy($sourceImage);
            throw new Exception('Failed to rotate image.');
        }
        
        // Save the rotated image back to the original path
        $this->saveImage($rotatedImage, $imagePath, $mimeType);
        
        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($rotatedImage);
        
        return true;
    }
    
    /**
     * Auto-fix image orientation based on EXIF data
     * 
     * @param string $imagePath Path to the image file
     * @return bool True if rotation was applied, false if no rotation needed
     */
    public function autoFixOrientation($imagePath) {
        if (!function_exists('exif_read_data') || !file_exists($imagePath)) {
            return false;
        }
        
        // Only works with JPEG images
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo || $imageInfo['mime'] !== 'image/jpeg') {
            return false;
        }
        
        try {
            $exif = exif_read_data($imagePath);
            if (!$exif || !isset($exif['Orientation'])) {
                return false;
            }
            
            $rotation = 0;
            switch ($exif['Orientation']) {
                case 3:
                    $rotation = 180;
                    break;
                case 6:
                    $rotation = 270;
                    break;
                case 8:
                    $rotation = 90;
                    break;
                default:
                    return false; // No rotation needed
            }
            
            // Apply the rotation
            $this->rotateImage($imagePath, $rotation);
            return true;
            
        } catch (Exception $e) {
            // EXIF data might be corrupted or missing
            return false;
        }
    }
?>
