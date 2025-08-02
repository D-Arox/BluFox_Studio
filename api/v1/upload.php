<?php
function uploadAvatar() {
    require_auth();
    
    if (!isset($_FILES['avatar'])) {
        return ApiResponse::error('No avatar file provided', 400);
    }
    
    try {
        // $uploader = new FileUploader(ALLOWED_IMAGE_TYPES, MAX_AVATAR_SIZE ?? 2097152); // 2MB default
        $uploader = new FileUploader(ALLOWED_IMAGE_TYPES, 2097152 ?? 2097152); // 2MB default
        
        $user = current_user();
        
        // Upload file
        $result = $uploader->upload($_FILES['avatar'], 'avatars');
        
        // Create thumbnail
        $thumbnailPath = UPLOAD_PATH . '/avatars/thumb_' . $result['filename'];
        $uploader->resizeImage($result['path'], $thumbnailPath, 150, 150, 85);
        
        // Update user avatar
        $userModel = new User();
        $userModel->update($user['id'], [
            'avatar_url' => $result['url']
        ]);
        
        // Delete old avatar if exists
        if ($user['avatar_url'] && strpos($user['avatar_url'], UPLOADS_URL) === 0) {
            $oldPath = str_replace(UPLOADS_URL, UPLOAD_PATH, $user['avatar_url']);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
            
            // Delete old thumbnail
            $oldThumbPath = dirname($oldPath) . '/thumb_' . basename($oldPath);
            if (file_exists($oldThumbPath)) {
                unlink($oldThumbPath);
            }
        }
        
        // Log activity
        auth()->logActivity('avatar_uploaded', ['filename' => $result['filename']]);
        
        return ApiResponse::success([
            'avatar_url' => $result['url'],
            'thumbnail_url' => UPLOADS_URL . '/avatars/thumb_' . $result['filename'],
            'filename' => $result['filename'],
            'size' => $result['size']
        ], 'Avatar uploaded successfully');
        
    } catch (Exception $e) {
        error_log("Upload avatar error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to upload avatar: ' . $e->getMessage());
    }
}

function uploadProjectImage($data) {
    require_auth();
    
    if (!isset($_FILES['image'])) {
        return ApiResponse::error('No image file provided', 400);
    }
    
    $validator = new ApiValidator($data);
    $validator->required(['project_id'])->integer('project_id');
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $projectModel = new Project();
        $project = $projectModel->find($data['project_id']);
        
        if (!$project) {
            return ApiResponse::notFound('Project not found');
        }
        
        // Check ownership or admin rights
        if ($project['created_by'] !== current_user()['id'] && !has_role('admin')) {
            return ApiResponse::forbidden('You can only upload images to your own projects');
        }
        
        // Check image limit
        $existingImages = $projectModel->getImages($data['project_id']);
        $maxImages = 10; // $maxImages = MAX_PROJECT_IMAGES ?? 10;
        
        if (count($existingImages) >= $maxImages) {
            return ApiResponse::error("Maximum of $maxImages images allowed per project", 400);
        }
        
        $uploader = new FileUploader(ALLOWED_IMAGE_TYPES, UPLOAD_MAX_SIZE);
        
        // Upload file
        $result = $uploader->upload($_FILES['image'], 'projects');
        
        // Create thumbnail
        $thumbnailPath = UPLOAD_PATH . '/projects/thumb_' . $result['filename'];
        $uploader->resizeImage($result['path'], $thumbnailPath, 300, 200, 85);
        
        // Add to database
        $db = db();
        $imageData = [
            'project_id' => $data['project_id'],
            'image_url' => $result['url'],
            'alt_text' => $data['alt_text'] ?? '',
            'caption' => $data['caption'] ?? '',
            'is_thumbnail' => isset($data['is_thumbnail']) ? (bool)$data['is_thumbnail'] : false,
            'is_banner' => isset($data['is_banner']) ? (bool)$data['is_banner'] : false,
            'sort_order' => $data['sort_order'] ?? count($existingImages)
        ];
        
        $imageId = $db->insert('project_images', $imageData);
        
        // If this is set as thumbnail, update project
        if ($imageData['is_thumbnail']) {
            $projectModel->update($data['project_id'], ['thumbnail_url' => $result['url']]);
            
            // Remove thumbnail flag from other images
            $db->raw("UPDATE project_images SET is_thumbnail = 0 WHERE project_id = :project_id AND id != :image_id", [
                ':project_id' => $data['project_id'],
                ':image_id' => $imageId
            ]);
        }
        
        // If this is set as banner, update project
        if ($imageData['is_banner']) {
            $projectModel->update($data['project_id'], ['banner_url' => $result['url']]);
            
            // Remove banner flag from other images
            $db->raw("UPDATE project_images SET is_banner = 0 WHERE project_id = :project_id AND id != :image_id", [
                ':project_id' => $data['project_id'],
                ':image_id' => $imageId
            ]);
        }
        
        // Log activity
        auth()->logActivity('project_image_uploaded', [
            'project_id' => $data['project_id'],
            'image_id' => $imageId,
            'filename' => $result['filename']
        ]);
        
        return ApiResponse::success([
            'id' => $imageId,
            'image_url' => $result['url'],
            'thumbnail_url' => UPLOADS_URL . '/projects/thumb_' . $result['filename'],
            'filename' => $result['filename'],
            'size' => $result['size'],
            'alt_text' => $imageData['alt_text'],
            'caption' => $imageData['caption'],
            'is_thumbnail' => $imageData['is_thumbnail'],
            'is_banner' => $imageData['is_banner']
        ], 'Project image uploaded successfully');
        
    } catch (Exception $e) {
        error_log("Upload project image error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to upload project image: ' . $e->getMessage());
    }
}

function uploadTempFile() {
    require_auth();
    
    if (!isset($_FILES['file'])) {
        return ApiResponse::error('No file provided', 400);
    }
    
    try {
        $allowedTypes = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_FILE_TYPES);
        $uploader = new FileUploader($allowedTypes, UPLOAD_MAX_SIZE);
        
        // Upload to temp folder
        $result = $uploader->upload($_FILES['file'], 'temp');
        
        // Store temp file info in session for later use
        if (!isset($_SESSION['temp_files'])) {
            $_SESSION['temp_files'] = [];
        }
        
        $tempFileId = generate_random_string(16);
        $_SESSION['temp_files'][$tempFileId] = [
            'filename' => $result['filename'],
            'original_name' => $_FILES['file']['name'],
            'path' => $result['path'],
            'url' => $result['url'],
            'size' => $result['size'],
            'type' => $result['type'],
            'uploaded_at' => time()
        ];
        
        // Clean up old temp files (older than 1 hour)
        foreach ($_SESSION['temp_files'] as $id => $file) {
            if ($file['uploaded_at'] < time() - 3600) {
                if (file_exists($file['path'])) {
                    unlink($file['path']);
                }
                unset($_SESSION['temp_files'][$id]);
            }
        }
        
        return ApiResponse::success([
            'temp_id' => $tempFileId,
            'filename' => $result['filename'],
            'original_name' => $_FILES['file']['name'],
            'url' => $result['url'],
            'size' => $result['size'],
            'type' => $result['type']
        ], 'Temporary file uploaded successfully');
        
    } catch (Exception $e) {
        error_log("Upload temp file error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to upload temporary file: ' . $e->getMessage());
    }
}

function uploadGeneral($data) {
    require_auth();
    
    if (!isset($_FILES['file'])) {
        return ApiResponse::error('No file provided', 400);
    }
    
    try {
        $folder = $data['folder'] ?? 'general';
        $allowedTypes = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_FILE_TYPES);
        
        // Validate folder name
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $folder)) {
            return ApiResponse::error('Invalid folder name', 400);
        }
        
        $uploader = new FileUploader($allowedTypes, UPLOAD_MAX_SIZE);
        $result = $uploader->upload($_FILES['file'], $folder);
        
        // Log activity
        auth()->logActivity('file_uploaded', [
            'filename' => $result['filename'],
            'folder' => $folder,
            'size' => $result['size']
        ]);
        
        return ApiResponse::success([
            'filename' => $result['filename'],
            'original_name' => $_FILES['file']['name'],
            'url' => $result['url'],
            'size' => $result['size'],
            'type' => $result['type'],
            'folder' => $folder
        ], 'File uploaded successfully');
        
    } catch (Exception $e) {
        error_log("Upload general file error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to upload file: ' . $e->getMessage());
    }
}

function deleteFile($data) {
    require_auth();
    
    $validator = new ApiValidator($data);
    $validator->required(['filename', 'folder']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        // Validate folder name
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['folder'])) {
            return ApiResponse::error('Invalid folder name', 400);
        }
        
        // Validate filename
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $data['filename'])) {
            return ApiResponse::error('Invalid filename', 400);
        }
        
        $filePath = UPLOAD_PATH . '/' . $data['folder'] . '/' . $data['filename'];
        
        // Check if file exists and is within upload directory
        if (!file_exists($filePath) || strpos(realpath($filePath), realpath(UPLOAD_PATH)) !== 0) {
            return ApiResponse::notFound('File not found');
        }
        
        // For project images, check ownership
        if ($data['folder'] === 'projects' && isset($data['project_id'])) {
            $projectModel = new Project();
            $project = $projectModel->find($data['project_id']);
            
            if (!$project) {
                return ApiResponse::notFound('Project not found');
            }
            
            if ($project['created_by'] !== current_user()['id'] && !has_role('admin')) {
                return ApiResponse::forbidden('You can only delete files from your own projects');
            }
            
            // Remove from database
            $db = db();
            $db->delete('project_images', [
                'project_id' => $data['project_id'],
                'image_url' => UPLOADS_URL . '/' . $data['folder'] . '/' . $data['filename']
            ]);
        }
        
        // For avatars, check ownership
        if ($data['folder'] === 'avatars') {
            $user = current_user();
            if (!$user['avatar_url'] || strpos($user['avatar_url'], $data['filename']) === false) {
                return ApiResponse::forbidden('You can only delete your own avatar');
            }
        }
        
        // Delete file
        $uploader = upload();
        $deleted = $uploader->delete($filePath);
        
        if (!$deleted) {
            return ApiResponse::serverError('Failed to delete file');
        }
        
        // Delete thumbnail if exists
        $thumbnailPath = dirname($filePath) . '/thumb_' . basename($filePath);
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
        
        // Log activity
        auth()->logActivity('file_deleted', [
            'filename' => $data['filename'],
            'folder' => $data['folder']
        ]);
        
        return ApiResponse::success(null, 'File deleted successfully');
        
    } catch (Exception $e) {
        error_log("Delete file error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to delete file');
    }
}

function getTempFile($tempId) {
    require_auth();
    
    if (!isset($_SESSION['temp_files'][$tempId])) {
        return ApiResponse::notFound('Temporary file not found');
    }
    
    $tempFile = $_SESSION['temp_files'][$tempId];
    
    // Check if file still exists
    if (!file_exists($tempFile['path'])) {
        unset($_SESSION['temp_files'][$tempId]);
        return ApiResponse::notFound('Temporary file no longer exists');
    }
    
    return ApiResponse::success($tempFile, 'Temporary file retrieved');
}

function moveTempFile($data) {
    require_auth();
    
    $validator = new ApiValidator($data);
    $validator->required(['temp_id', 'destination_folder']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        if (!isset($_SESSION['temp_files'][$data['temp_id']])) {
            return ApiResponse::notFound('Temporary file not found');
        }
        
        $tempFile = $_SESSION['temp_files'][$data['temp_id']];
        
        // Validate destination folder
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['destination_folder'])) {
            return ApiResponse::error('Invalid destination folder name', 400);
        }
        
        // Create destination directory if it doesn't exist
        $destDir = UPLOAD_PATH . '/' . $data['destination_folder'];
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        
        // Move file
        $newFilename = $data['new_filename'] ?? $tempFile['filename'];
        $destPath = $destDir . '/' . $newFilename;
        
        if (!rename($tempFile['path'], $destPath)) {
            return ApiResponse::serverError('Failed to move temporary file');
        }
        
        // Update file permissions
        chmod($destPath, 0644);
        
        // Remove from temp files
        unset($_SESSION['temp_files'][$data['temp_id']]);
        
        $newUrl = UPLOADS_URL . '/' . $data['destination_folder'] . '/' . $newFilename;
        
        // Log activity
        auth()->logActivity('temp_file_moved', [
            'temp_id' => $data['temp_id'],
            'destination' => $data['destination_folder'],
            'filename' => $newFilename
        ]);
        
        return ApiResponse::success([
            'filename' => $newFilename,
            'url' => $newUrl,
            'path' => $destPath,
            'destination_folder' => $data['destination_folder']
        ], 'Temporary file moved successfully');
        
    } catch (Exception $e) {
        error_log("Move temp file error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to move temporary file');
    }
}

function getUploadStats() {
    require_permission('view_analytics');
    
    try {
        $stats = [];
        
        // Calculate directory sizes
        $directories = ['avatars', 'projects', 'temp', 'general'];
        
        foreach ($directories as $dir) {
            $dirPath = UPLOAD_PATH . '/' . $dir;
            $size = 0;
            $count = 0;
            
            if (is_dir($dirPath)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dirPath),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                foreach ($files as $file) {
                    if ($file->isFile()) {
                        $size += $file->getSize();
                        $count++;
                    }
                }
            }
            
            $stats[$dir] = [
                'size_bytes' => $size,
                'size_mb' => round($size / 1024 / 1024, 2),
                'file_count' => $count
            ];
        }
        
        // Total stats
        $totalSize = array_sum(array_column($stats, 'size_bytes'));
        $totalFiles = array_sum(array_column($stats, 'file_count'));
        
        $stats['total'] = [
            'size_bytes' => $totalSize,
            'size_mb' => round($totalSize / 1024 / 1024, 2),
            'file_count' => $totalFiles
        ];
        
        return ApiResponse::success($stats, 'Upload statistics retrieved');
        
    } catch (Exception $e) {
        error_log("Get upload stats error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve upload statistics');
    }
}