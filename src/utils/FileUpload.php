<?php
/**
 * VelocityPhp File Upload Handler
 * Secure file upload with validation, sanitization, and storage
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Utils;

class FileUpload
{
    /**
     * Default allowed MIME types by category
     */
    protected static $allowedMimeTypes = [
        'image' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml'
        ],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv'
        ],
        'video' => [
            'video/mp4',
            'video/webm',
            'video/ogg',
            'video/quicktime'
        ],
        'audio' => [
            'audio/mpeg',
            'audio/wav',
            'audio/ogg',
            'audio/webm'
        ],
        'archive' => [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/gzip'
        ]
    ];
    
    /**
     * File extension to MIME type mapping
     */
    protected static $extensionToMime = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogg' => 'audio/ogg',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        '7z' => 'application/x-7z-compressed',
        'gz' => 'application/gzip'
    ];
    
    /**
     * Default configuration
     */
    protected $config = [
        'upload_path' => null,
        'max_size' => 10485760, // 10MB
        'allowed_types' => ['image'],
        'allowed_extensions' => [],
        'generate_unique_name' => true,
        'preserve_filename' => false,
        'create_thumbnail' => false,
        'thumbnail_width' => 150,
        'thumbnail_height' => 150,
        'overwrite' => false
    ];
    
    /**
     * Validation errors
     */
    protected $errors = [];
    
    /**
     * Upload result
     */
    protected $result = [];
    
    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        
        // Set default upload path
        if (!$this->config['upload_path']) {
            $this->config['upload_path'] = defined('ROOT_PATH') 
                ? ROOT_PATH . '/public/uploads' 
                : __DIR__ . '/../../public/uploads';
        }
    }
    
    /**
     * Configure the upload handler
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }
    
    /**
     * Set upload path
     */
    public function setUploadPath(string $path): self
    {
        $this->config['upload_path'] = $path;
        return $this;
    }
    
    /**
     * Set maximum file size (in bytes)
     */
    public function setMaxSize(int $bytes): self
    {
        $this->config['max_size'] = $bytes;
        return $this;
    }
    
    /**
     * Set allowed file types (image, document, video, audio, archive)
     */
    public function setAllowedTypes(array $types): self
    {
        $this->config['allowed_types'] = $types;
        return $this;
    }
    
    /**
     * Set allowed extensions directly
     */
    public function setAllowedExtensions(array $extensions): self
    {
        $this->config['allowed_extensions'] = array_map('strtolower', $extensions);
        return $this;
    }
    
    /**
     * Upload a single file
     * 
     * @param array $file $_FILES['fieldname'] array
     * @param string|null $customName Optional custom filename (without extension)
     * @return array|false Upload result or false on failure
     */
    public function upload(array $file, ?string $customName = null)
    {
        $this->errors = [];
        $this->result = [];
        
        // Validate file array structure
        if (!$this->validateFileArray($file)) {
            return false;
        }
        
        // Check for upload errors
        if (!$this->validateUploadError($file['error'])) {
            return false;
        }
        
        // Validate file size
        if (!$this->validateFileSize($file['size'])) {
            return false;
        }
        
        // Get and validate extension
        $extension = $this->getExtension($file['name']);
        if (!$this->validateExtension($extension)) {
            return false;
        }
        
        // Validate MIME type
        $mimeType = $this->getMimeType($file['tmp_name']);
        if (!$this->validateMimeType($mimeType, $extension)) {
            return false;
        }
        
        // Security check: Validate it's a real uploaded file
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->errors[] = 'Invalid upload attempt';
            return false;
        }
        
        // Additional security for images
        if ($this->isImage($mimeType)) {
            if (!$this->validateImage($file['tmp_name'])) {
                return false;
            }
        }
        
        // Prepare upload directory
        if (!$this->prepareUploadDirectory()) {
            return false;
        }
        
        // Generate filename
        $filename = $this->generateFilename($file['name'], $customName, $extension);
        $destination = $this->config['upload_path'] . '/' . $filename;
        
        // Check if file exists
        if (!$this->config['overwrite'] && file_exists($destination)) {
            // Add random suffix
            $filename = pathinfo($filename, PATHINFO_FILENAME) . '_' . uniqid() . '.' . $extension;
            $destination = $this->config['upload_path'] . '/' . $filename;
        }
        
        // Move the uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->errors[] = 'Failed to move uploaded file';
            return false;
        }
        
        // Set proper permissions
        chmod($destination, 0644);
        
        // Build result
        $this->result = [
            'success' => true,
            'filename' => $filename,
            'original_name' => $file['name'],
            'path' => $destination,
            'relative_path' => str_replace(ROOT_PATH . '/public', '', $destination),
            'size' => $file['size'],
            'size_human' => $this->formatFileSize($file['size']),
            'mime_type' => $mimeType,
            'extension' => $extension,
            'is_image' => $this->isImage($mimeType)
        ];
        
        // Create thumbnail if enabled and is image
        if ($this->config['create_thumbnail'] && $this->isImage($mimeType)) {
            $thumbnail = $this->createThumbnail($destination, $extension);
            if ($thumbnail) {
                $this->result['thumbnail'] = $thumbnail;
            }
        }
        
        return $this->result;
    }
    
    /**
     * Upload multiple files
     * 
     * @param array $files $_FILES['fieldname'] array (multiple)
     * @return array Array of upload results
     */
    public function uploadMultiple(array $files): array
    {
        $results = [];
        
        // Normalize files array structure
        if (isset($files['name']) && is_array($files['name'])) {
            // Multiple files uploaded with same field name
            $count = count($files['name']);
            
            for ($i = 0; $i < $count; $i++) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                $result = $this->upload($file);
                $results[] = $result ?: [
                    'success' => false,
                    'original_name' => $files['name'][$i],
                    'errors' => $this->errors
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Validate $_FILES array structure
     */
    protected function validateFileArray(array $file): bool
    {
        $required = ['name', 'type', 'tmp_name', 'error', 'size'];
        
        foreach ($required as $key) {
            if (!isset($file[$key])) {
                $this->errors[] = "Invalid file upload: missing '{$key}'";
                return false;
            }
        }
        
        if (empty($file['name']) || empty($file['tmp_name'])) {
            $this->errors[] = 'No file was uploaded';
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate upload error code
     */
    protected function validateUploadError(int $error): bool
    {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension'
        ];
        
        if ($error !== UPLOAD_ERR_OK) {
            $this->errors[] = $errorMessages[$error] ?? 'Unknown upload error';
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate file size
     */
    protected function validateFileSize(int $size): bool
    {
        if ($size > $this->config['max_size']) {
            $this->errors[] = sprintf(
                'File size (%s) exceeds maximum allowed (%s)',
                $this->formatFileSize($size),
                $this->formatFileSize($this->config['max_size'])
            );
            return false;
        }
        
        if ($size === 0) {
            $this->errors[] = 'File is empty';
            return false;
        }
        
        return true;
    }
    
    /**
     * Get file extension
     */
    protected function getExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Validate file extension
     */
    protected function validateExtension(string $extension): bool
    {
        // If specific extensions are set, use those
        if (!empty($this->config['allowed_extensions'])) {
            if (!in_array($extension, $this->config['allowed_extensions'])) {
                $this->errors[] = sprintf(
                    "File extension '%s' is not allowed. Allowed: %s",
                    $extension,
                    implode(', ', $this->config['allowed_extensions'])
                );
                return false;
            }
            return true;
        }
        
        // Otherwise, build allowed extensions from type categories
        $allowedExtensions = [];
        foreach ($this->config['allowed_types'] as $type) {
            if (isset(self::$allowedMimeTypes[$type])) {
                foreach (self::$allowedMimeTypes[$type] as $mime) {
                    $ext = array_search($mime, self::$extensionToMime);
                    if ($ext) {
                        $allowedExtensions[] = $ext;
                    }
                }
            }
        }
        
        // Add jpeg for jpg
        if (in_array('jpg', $allowedExtensions)) {
            $allowedExtensions[] = 'jpeg';
        }
        
        if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions)) {
            $this->errors[] = sprintf(
                "File extension '%s' is not allowed for type: %s",
                $extension,
                implode(', ', $this->config['allowed_types'])
            );
            return false;
        }
        
        return true;
    }
    
    /**
     * Get actual MIME type using finfo
     */
    protected function getMimeType(string $filePath): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($filePath);
    }
    
    /**
     * Validate MIME type
     */
    protected function validateMimeType(string $mimeType, string $extension): bool
    {
        // Build allowed MIME types from categories
        $allowedMimes = [];
        foreach ($this->config['allowed_types'] as $type) {
            if (isset(self::$allowedMimeTypes[$type])) {
                $allowedMimes = array_merge($allowedMimes, self::$allowedMimeTypes[$type]);
            }
        }
        
        // If allowed extensions set, get their mimes
        if (!empty($this->config['allowed_extensions'])) {
            $allowedMimes = [];
            foreach ($this->config['allowed_extensions'] as $ext) {
                if (isset(self::$extensionToMime[$ext])) {
                    $allowedMimes[] = self::$extensionToMime[$ext];
                }
            }
        }
        
        if (!empty($allowedMimes) && !in_array($mimeType, $allowedMimes)) {
            $this->errors[] = sprintf(
                "File type '%s' is not allowed",
                $mimeType
            );
            return false;
        }
        
        // Verify extension matches MIME type (prevent extension spoofing)
        if (isset(self::$extensionToMime[$extension])) {
            $expectedMime = self::$extensionToMime[$extension];
            // Handle jpeg/jpg case
            if ($extension === 'jpeg') {
                $expectedMime = 'image/jpeg';
            }
            
            if ($mimeType !== $expectedMime) {
                // Allow some flexibility for text files
                if (!($extension === 'csv' && $mimeType === 'text/plain')) {
                    $this->errors[] = 'File extension does not match file type';
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Check if MIME type is an image
     */
    protected function isImage(string $mimeType): bool
    {
        return strpos($mimeType, 'image/') === 0;
    }
    
    /**
     * Validate image file
     */
    protected function validateImage(string $filePath): bool
    {
        // Try to get image info
        $imageInfo = @getimagesize($filePath);
        
        if ($imageInfo === false) {
            $this->errors[] = 'File is not a valid image';
            return false;
        }
        
        // Check for minimum dimensions
        if ($imageInfo[0] < 1 || $imageInfo[1] < 1) {
            $this->errors[] = 'Invalid image dimensions';
            return false;
        }
        
        return true;
    }
    
    /**
     * Prepare upload directory
     */
    protected function prepareUploadDirectory(): bool
    {
        $path = (string)$this->config['upload_path'];
        
        if (!is_dir($path)) {
            if (!@mkdir($path, 0755, true)) {
                $this->errors[] = 'Failed to create upload directory';
                return false;
            }
        }
        
        if (!is_writable($path)) {
            $this->errors[] = 'Upload directory is not writable';
            return false;
        }
        
        // Create .htaccess to prevent script execution
        $htaccess = $path . '/.htaccess';
        if (!file_exists($htaccess)) {
            $content = "# Prevent script execution\n";
            $content .= "Options -ExecCGI\n";
            $content .= "RemoveHandler .php .phtml .php3 .php4 .php5 .phps\n";
            $content .= "AddType text/plain .php .phtml .php3 .php4 .php5 .phps\n";
            @file_put_contents($htaccess, $content);
        }
        
        return true;
    }
    
    /**
     * Generate safe filename
     */
    protected function generateFilename(string $originalName, ?string $customName, string $extension): string
    {
        if ($customName) {
            // Sanitize custom name
            $name = $this->sanitizeFilename($customName);
        } elseif ($this->config['preserve_filename']) {
            // Keep original name but sanitize
            $name = $this->sanitizeFilename(pathinfo($originalName, PATHINFO_FILENAME));
        } else {
            // Generate unique name
            $name = $this->generateUniqueName();
        }
        
        return $name . '.' . $extension;
    }
    
    /**
     * Sanitize filename
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove any directory traversal attempts
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
        
        // Remove multiple underscores
        $filename = preg_replace('/_+/', '_', $filename);
        
        // Trim underscores
        $filename = trim($filename, '_');
        
        // Ensure filename is not empty
        if (empty($filename)) {
            $filename = 'file';
        }
        
        // Limit length
        if (strlen($filename) > 100) {
            $filename = substr($filename, 0, 100);
        }
        
        return $filename;
    }
    
    /**
     * Generate unique filename
     */
    protected function generateUniqueName(): string
    {
        return date('Ymd_His') . '_' . bin2hex(random_bytes(8));
    }
    
    /**
     * Create thumbnail for image
     */
    protected function createThumbnail(string $sourcePath, string $extension): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }
        
        $width = $this->config['thumbnail_width'];
        $height = $this->config['thumbnail_height'];
        
        // Get source image dimensions
        list($srcWidth, $srcHeight) = getimagesize($sourcePath);
        
        // Calculate thumbnail dimensions maintaining aspect ratio
        $ratio = min($width / $srcWidth, $height / $srcHeight);
        $newWidth = (int)($srcWidth * $ratio);
        $newHeight = (int)($srcHeight * $ratio);
        
        // Create source image
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $srcImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'png':
                $srcImage = imagecreatefrompng($sourcePath);
                break;
            case 'gif':
                $srcImage = imagecreatefromgif($sourcePath);
                break;
            case 'webp':
                if (function_exists('imagecreatefromwebp')) {
                    $srcImage = imagecreatefromwebp($sourcePath);
                } else {
                    return null;
                }
                break;
            default:
                return null;
        }
        
        if (!$srcImage) {
            return null;
        }
        
        // Create thumbnail canvas
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if (in_array($extension, ['png', 'gif'])) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize
        imagecopyresampled(
            $thumbnail, $srcImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $srcWidth, $srcHeight
        );
        
        // Generate thumbnail path
        $pathInfo = pathinfo($sourcePath);
        $thumbPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
        
        // Save thumbnail
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($thumbnail, $thumbPath, 85);
                break;
            case 'png':
                imagepng($thumbnail, $thumbPath, 8);
                break;
            case 'gif':
                imagegif($thumbnail, $thumbPath);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    imagewebp($thumbnail, $thumbPath, 85);
                }
                break;
        }
        
        // Cleanup
        imagedestroy($srcImage);
        imagedestroy($thumbnail);
        
        if (file_exists($thumbPath)) {
            chmod($thumbPath, 0644);
            return 'thumb_' . $pathInfo['basename'];
        }
        
        return null;
    }
    
    /**
     * Format file size to human readable
     */
    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Delete uploaded file
     */
    public function delete(string $filename): bool
    {
        $path = $this->config['upload_path'] . '/' . basename($filename);
        
        if (file_exists($path)) {
            // Also delete thumbnail if exists
            $thumbPath = $this->config['upload_path'] . '/thumb_' . basename($filename);
            if (file_exists($thumbPath)) {
                @unlink($thumbPath);
            }
            
            return @unlink($path);
        }
        
        return false;
    }
    
    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get first error
     */
    public function getError(): ?string
    {
        return $this->errors[0] ?? null;
    }
    
    /**
     * Get upload result
     */
    public function getResult(): array
    {
        return $this->result;
    }
    
    /**
     * Static helper: Quick image upload
     */
    public static function image(array $file, string $path = null): array
    {
        $uploader = new self([
            'upload_path' => $path,
            'allowed_types' => ['image'],
            'max_size' => 5 * 1024 * 1024, // 5MB
            'create_thumbnail' => true
        ]);
        
        $result = $uploader->upload($file);
        
        return $result ?: [
            'success' => false,
            'errors' => $uploader->getErrors()
        ];
    }
    
    /**
     * Static helper: Quick document upload
     */
    public static function document(array $file, string $path = null): array
    {
        $uploader = new self([
            'upload_path' => $path,
            'allowed_types' => ['document'],
            'max_size' => 20 * 1024 * 1024 // 20MB
        ]);
        
        $result = $uploader->upload($file);
        
        return $result ?: [
            'success' => false,
            'errors' => $uploader->getErrors()
        ];
    }
}
