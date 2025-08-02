<?php
class FileUploader {
    private $allowedTypes;
    private $maxSize;
    private $uploadPath;
    
    public function __construct($allowedTypes = null, $maxSize = null, $uploadPath = null) {
        $this->allowedTypes = $allowedTypes ?? ALLOWED_IMAGE_TYPES;
        $this->maxSize = $maxSize ?? UPLOAD_MAX_SIZE;
        $this->uploadPath = $uploadPath ?? UPLOAD_PATH;
    }

    public function upload($file, $folder = '', $filename = null) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid file upload');
        }

        $this->validateFile($file);

        if (!$filename) {
            $extension = $this->getFileExtension($file['name']);
            $filename = generate_random_string(32) . '.' . $extension;
        }

        $targetDir = $this->uploadPath . '/' . trim($folder, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Failed to move uploaded file');
        }

        chmod($targetPath, 0644);

        return [
            'filename' => $filename,
            'path' => $targetPath,
            'url' => UPLOADS_URL . '/' . trim($folder, '/') . '/' . $filename,
            'size' => filesize($targetPath),
            'type' => $file['type']
        ];
    }

    public function uploadMultiple($files, $folder = '', $maxFiles = 10) {
        $uploaded = [];
        $count = 0;
        
        foreach ($files['name'] as $index => $name) {
            if ($count >= $maxFiles) {
                break;
            }
            
            if (empty($name)) {
                continue;
            }
            
            $file = [
                'name' => $files['name'][$index],
                'type' => $files['type'][$index],
                'tmp_name' => $files['tmp_name'][$index],
                'error' => $files['error'][$index],
                'size' => $files['size'][$index]
            ];
            
            try {
                $uploaded[] = $this->upload($file, $folder);
                $count++;
            } catch (Exception $e) {
                error_log("File upload failed: " . $e->getMessage());
            }
        }
        
        return $uploaded;
    }

    private function validateFile($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $this->getUploadErrorMessage($file['error']));
        }
        
        if ($file['size'] > $this->maxSize) {
            throw new Exception('File size exceeds maximum allowed size');
        }
        
        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, $this->allowedTypes)) {
            throw new Exception('File type not allowed');
        }
        
        if (strpos($mimeType, 'image/') === 0) {
            $imageInfo = getimagesize($file['tmp_name']);
            if (!$imageInfo) {
                throw new Exception('Invalid image file');
            }
        }
    }

    private function getFileExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    public function delete($filePath) {
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

     public function resizeImage($sourcePath, $targetPath, $maxWidth, $maxHeight, $quality = 85) {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new Exception('Invalid image file');
        }
        
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
        $newWidth = round($sourceWidth * $ratio);
        $newHeight = round($sourceHeight * $ratio);
        
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                throw new Exception('Unsupported image type');
        }
        
        $targetImage = imagecreatetruecolor($newWidth, $newHeight);
        
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
            imagefilledrectangle($targetImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
        
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($targetImage, $targetPath, $quality);
                break;
            case 'image/png':
                imagepng($targetImage, $targetPath);
                break;
            case 'image/gif':
                imagegif($targetImage, $targetPath);
                break;
        }
        
        imagedestroy($sourceImage);
        imagedestroy($targetImage);
        
        return true;
    }
}

class CacheManager {
    private $cacheDir;
    private $defaultTtl;
    
    public function __construct($cacheDir = null, $defaultTtl = 3600) {
        $this->cacheDir = $cacheDir ?? CACHE_PATH;
        $this->defaultTtl = $defaultTtl;
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    private function getCacheFile($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
    
    public function set($key, $value, $ttl = null) {
        if (!CACHE_ENABLED || CACHE_ENABLED !== 'true') {
            return false;
        }
        
        $ttl = $ttl ?? $this->defaultTtl;
        $expiry = time() + $ttl;
        
        $data = [
            'expiry' => $expiry,
            'value' => $value
        ];
        
        $cacheFile = $this->getCacheFile($key);
        return file_put_contents($cacheFile, serialize($data)) !== false;
    }
    
    public function get($key, $default = null) {
        if (!CACHE_ENABLED || CACHE_ENABLED !== 'true') {
            return $default;
        }
        
        $cacheFile = $this->getCacheFile($key);
        
        if (!file_exists($cacheFile)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($cacheFile));
        
        if (!$data || $data['expiry'] < time()) {
            $this->delete($key);
            return $default;
        }
        
        return $data['value'];
    }
    
    public function delete($key) {
        $cacheFile = $this->getCacheFile($key);
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }
    
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    public function clear() {
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value === null) {
            $value = $callback();
            $this->set($key, $value, $ttl);
        }
        
        return $value;
    }
    
    public function getStats() {
        $files = glob($this->cacheDir . '/*.cache');
        $totalSize = 0;
        $validCount = 0;
        $expiredCount = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $data = unserialize(file_get_contents($file));
            if ($data && $data['expiry'] >= time()) {
                $validCount++;
            } else {
                $expiredCount++;
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_files' => $validCount,
            'expired_files' => $expiredCount,
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2)
        ];
    }
}

function cache() {
    static $cache = null;
    if ($cache === null) {
        $cache = new CacheManager();
    }
    return $cache;
}

function upload() {
    static $uploader = null;
    if ($uploader === null) {
        $uploader = new FileUploader();
    }
    return $uploader;
}