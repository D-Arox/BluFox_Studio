<?php
function getAdminStats() {
    require_role('moderator');
    
    try {
        $db = db();
        $stats = [];
        
        // Overview statistics
        $stats['overview'] = [
            'total_users' => $db->count('users'),
            'active_users' => $db->count('users', ['status' => 'active']),
            'total_projects' => $db->count('projects'),
            'published_projects' => $db->count('projects', ['is_published' => 1]),
            'total_services' => $db->count('services'),
            'active_services' => $db->count('services', ['is_active' => 1]),
            'total_inquiries' => $db->count('contact_inquiries'),
            'pending_inquiries' => $db->count('contact_inquiries', ['status' => 'new'])
        ];
        
        // Recent activity (last 24 hours)
        $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $stats['recent_activity'] = [
            'new_users' => $db->count('users', ["created_at >= '$yesterday'"]),
            'new_projects' => $db->count('projects', ["created_at >= '$yesterday'"]),
            'new_inquiries' => $db->count('contact_inquiries', ["created_at >= '$yesterday'"]),
            'new_comments' => $db->count('project_comments', ["created_at >= '$yesterday'"])
        ];
        
        // User statistics by role
        $usersByRole = $db->raw("SELECT role, COUNT(*) as count FROM users WHERE status = 'active' GROUP BY role");
        $stats['users_by_role'] = [];
        foreach ($usersByRole as $row) {
            $stats['users_by_role'][$row['role']] = (int)$row['count'];
        }
        
        // Project statistics by category
        $projectsByCategory = $db->raw("SELECT category, COUNT(*) as count FROM projects WHERE is_published = 1 GROUP BY category");
        $stats['projects_by_category'] = [];
        foreach ($projectsByCategory as $row) {
            $stats['projects_by_category'][$row['category']] = (int)$row['count'];
        }
        
        // Inquiry statistics
        $inquiriesByStatus = $db->raw("SELECT status, COUNT(*) as count FROM contact_inquiries GROUP BY status");
        $stats['inquiries_by_status'] = [];
        foreach ($inquiriesByStatus as $row) {
            $stats['inquiries_by_status'][$row['status']] = (int)$row['count'];
        }
        
        // System statistics
        $stats['system'] = [
            'database_size_mb' => $db->getStats()['total_size_mb'],
            'cache_enabled' => CACHE_ENABLED === 'true',
            'debug_mode' => DEBUG_MODE,
            'environment' => ENVIRONMENT,
            'php_version' => PHP_VERSION,
            'server_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get()
        ];
        
        // Performance metrics
        $performanceStats = $db->rawSingle("
            SELECT 
                SUM(view_count) as total_views,
                SUM(like_count) as total_likes,
                AVG(view_count) as avg_views_per_project
            FROM projects 
            WHERE is_published = 1
        ");
        
        $stats['performance'] = [
            'total_project_views' => (int)($performanceStats['total_views'] ?? 0),
            'total_project_likes' => (int)($performanceStats['total_likes'] ?? 0),
            'avg_views_per_project' => round($performanceStats['avg_views_per_project'] ?? 0, 2)
        ];
        
        return ApiResponse::success($stats, 'Admin statistics retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get admin stats error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve admin statistics');
    }
}

function handleAdminUsers($method, $segments, $data) {
    require_role('moderator');
    
    $userId = $segments[0] ?? null;
    $action = $segments[1] ?? null;
    
    switch ($method) {
        case 'GET':
            if ($userId && $action === 'activity') {
                return getUserActivity($userId);
            } elseif ($userId) {
                return getAdminUser($userId);
            } else {
                return getAdminUsers($data);
            }
            
        case 'PUT':
            if ($userId && $action === 'ban') {
                return banUser($userId, $data);
            } elseif ($userId && $action === 'unban') {
                return unbanUser($userId);
            } elseif ($userId && $action === 'role') {
                return updateUserRole($userId, $data);
            } elseif ($userId) {
                return updateAdminUser($userId, $data);
            }
            return ApiResponse::error('Invalid action', 400);
            
        case 'DELETE':
            if ($userId) {
                return deleteAdminUser($userId);
            }
            return ApiResponse::error('User ID required', 400);
            
        default:
            return ApiResponse::error('Method not allowed', 405);
    }
}

function getAdminUsers($data) {
    require_role('moderator');
    
    try {
        $db = db();
        $page = max(1, (int)($data['page'] ?? 1));
        $perPage = min(100, max(1, (int)($data['per_page'] ?? 25)));
        $search = $data['search'] ?? '';
        $role = $data['role'] ?? null;
        $status = $data['status'] ?? null;
        
        // Build base query
        $sql = "SELECT u.*, 
                       (SELECT COUNT(*) FROM projects p WHERE p.created_by = u.id) as project_count,
                       (SELECT COUNT(*) FROM project_comments pc WHERE pc.user_id = u.id) as comment_count
                FROM users u
                WHERE 1=1";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (u.username LIKE :search OR u.display_name LIKE :search OR u.email LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if ($role) {
            $sql .= " AND u.role = :role";
            $params[':role'] = $role;
        }
        
        if ($status) {
            $sql .= " AND u.status = :status";
            $params[':status'] = $status;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM users u WHERE 1=1";
        if ($search) {
            $countSql .= " AND (u.username LIKE :search OR u.display_name LIKE :search OR u.email LIKE :search)";
        }
        if ($role) {
            $countSql .= " AND u.role = :role";
        }
        if ($status) {
            $countSql .= " AND u.status = :status";
        }
        
        $db->prepare($countSql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $totalResult = $db->fetch();
        $total = (int)$totalResult['total'];
        $totalPages = ceil($total / $perPage);
        
        // Add sorting and pagination
        $sql .= " ORDER BY u.created_at DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage);
        
        $db->prepare($sql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        
        $users = $db->fetchAll();
        
        // Clean sensitive data
        foreach ($users as &$user) {
            unset($user['two_factor_secret']);
            $user['preferences'] = json_decode($user['preferences'], true) ?? [];
        }
        
        return ApiResponse::paginated($users, [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages
        ], 'Admin users retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get admin users error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve users');
    }
}

function getAdminUser($userId) {
    require_role('moderator');
    
    try {
        $userModel = new User();
        $user = $userModel->find($userId);
        
        if (!$user) {
            return ApiResponse::notFound('User not found');
        }
        
        $db = db();
        
        $user['recent_activity'] = $db->raw("
            SELECT action, details, ip_address, created_at
            FROM audit_logs 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT 20
        ", [':user_id' => $userId]);
        
        $user['active_sessions'] = $userModel->getUserSessions($userId);
        $user['api_keys'] = $userModel->getApiKeys($userId);
        $user['stats'] = $userModel->getActivityStats($userId);
        
        unset($user['two_factor_secret']);
        $user['preferences'] = json_decode($user['preferences'], true) ?? [];
        
        return ApiResponse::success($user, 'Admin user retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get admin user error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve user');
    }
}

function updateAdminUser($userId, $data) {
    require_role('admin');
    
    try {
        $userModel = new User();
        $user = $userModel->find($userId);
        
        if (!$user) {
            return ApiResponse::notFound('User not found');
        }
        
        // Validate data
        $validator = new ApiValidator($data);
        
        if (isset($data['email'])) {
            $validator->email('email');
        }
        
        if (isset($data['status'])) {
            $validator->in('status', ['active', 'suspended', 'banned']);
        }
        
        if ($validator->fails()) {
            return ApiResponse::validationError($validator->getErrors());
        }
        
        // Filter allowed fields
        $allowedFields = ['display_name', 'email', 'status', 'bio'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($updateData)) {
            return ApiResponse::error('No valid fields to update', 400);
        }
        
        $result = $userModel->update($userId, $updateData);
        
        if ($result) {
            // Log activity
            auth()->logActivity('admin_user_updated', [
                'target_user_id' => $userId,
                'updated_fields' => array_keys($updateData)
            ]);
            
            return ApiResponse::success($result, 'User updated successfully');
        } else {
            return ApiResponse::serverError('Failed to update user');
        }
        
    } catch (Exception $e) {
        error_log("Update admin user error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to update user');
    }
}

function banUser($userId, $data) {
    require_role('admin');
    
    try {
        $userModel = new User();
        $user = $userModel->find($userId);
        
        if (!$user) {
            return ApiResponse::notFound('User not found');
        }
        
        if ($user['role'] === 'superadmin') {
            return ApiResponse::forbidden('Cannot ban superadmin users');
        }
        
        if ($user['id'] === current_user()['id']) {
            return ApiResponse::forbidden('Cannot ban yourself');
        }
        
        $reason = $data['reason'] ?? 'No reason provided';
        $result = $userModel->ban($userId, $reason);
        
        if ($result) {
            return ApiResponse::success(['user_id' => $userId], 'User banned successfully');
        } else {
            return ApiResponse::serverError('Failed to ban user');
        }
        
    } catch (Exception $e) {
        error_log("Ban user error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to ban user');
    }
}

function unbanUser($userId) {
    require_role('admin');
    
    try {
        $userModel = new User();
        $user = $userModel->find($userId);
        
        if (!$user) {
            return ApiResponse::notFound('User not found');
        }
        
        $result = $userModel->unban($userId);
        
        if ($result) {
            return ApiResponse::success(['user_id' => $userId], 'User unbanned successfully');
        } else {
            return ApiResponse::serverError('Failed to unban user');
        }
        
    } catch (Exception $e) {
        error_log("Unban user error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to unban user');
    }
}

function updateUserRole($userId, $data) {
    require_role('admin');
    
    $validator = new ApiValidator($data);
    $validator->required(['role'])
             ->in('role', ['user', 'moderator', 'admin', 'superadmin']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $userModel = new User();
        $user = $userModel->find($userId);
        
        if (!$user) {
            return ApiResponse::notFound('User not found');
        }
        
        // Only superadmins can create other superadmins
        if ($data['role'] === 'superadmin' && current_user()['role'] !== 'superadmin') {
            return ApiResponse::forbidden('Only superadmins can grant superadmin role');
        }
        
        // Cannot demote yourself from superadmin
        if ($user['id'] === current_user()['id'] && 
            current_user()['role'] === 'superadmin' && 
            $data['role'] !== 'superadmin') {
            return ApiResponse::forbidden('Cannot demote yourself from superadmin');
        }
        
        $result = $userModel->update($userId, ['role' => $data['role']]);
        
        if ($result) {
            // Log activity
            auth()->logActivity('user_role_updated', [
                'target_user_id' => $userId,
                'old_role' => $user['role'],
                'new_role' => $data['role']
            ]);
            
            return ApiResponse::success(['user_id' => $userId, 'role' => $data['role']], 'User role updated successfully');
        } else {
            return ApiResponse::serverError('Failed to update user role');
        }
        
    } catch (Exception $e) {
        error_log("Update user role error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to update user role');
    }
}

function deleteAdminUser($userId) {
    require_role('admin');
    
    try {
        $userModel = new User();
        $user = $userModel->find($userId);
        
        if (!$user) {
            return ApiResponse::notFound('User not found');
        }
        
        if ($user['role'] === 'superadmin') {
            return ApiResponse::forbidden('Cannot delete superadmin users');
        }
        
        if ($user['id'] === current_user()['id']) {
            return ApiResponse::forbidden('Cannot delete yourself');
        }
        
        $result = $userModel->delete($userId);
        
        if ($result) {
            // Log activity
            auth()->logActivity('user_deleted', [
                'target_user_id' => $userId,
                'username' => $user['username']
            ]);
            
            return ApiResponse::success(null, 'User deleted successfully');
        } else {
            return ApiResponse::serverError('Failed to delete user');
        }
        
    } catch (Exception $e) {
        error_log("Delete admin user error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to delete user');
    }
}

function getUserActivity($userId) {
    require_role('moderator');
    
    try {
        $db = db();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 50)));
        
        // Check if user exists
        $user = $db->select('users', ['id' => $userId]);
        if (empty($user)) {
            return ApiResponse::notFound('User not found');
        }
        
        // Get total count
        $total = $db->count('audit_logs', ['user_id' => $userId]);
        $totalPages = ceil($total / $perPage);
        
        // Get activity logs
        $offset = ($page - 1) * $perPage;
        $activity = $db->raw("
            SELECT action, details, ip_address, user_agent, created_at
            FROM audit_logs 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT $perPage OFFSET $offset
        ", [':user_id' => $userId]);
        
        // Parse details JSON
        foreach ($activity as &$log) {
            $log['details'] = json_decode($log['details'], true) ?? [];
        }
        
        return ApiResponse::paginated($activity, [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages
        ], 'User activity retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get user activity error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve user activity');
    }
}

function handleAdminProjects($method, $segments, $data) {
    require_role('moderator');
    
    $projectId = $segments[0] ?? null;
    $action = $segments[1] ?? null;
    
    switch ($method) {
        case 'GET':
            if ($projectId) {
                return getAdminProject($projectId);
            } else {
                return getAdminProjects($data);
            }
            
        case 'PUT':
            if ($projectId && $action === 'approve') {
                return approveProject($projectId);
            } elseif ($projectId && $action === 'feature') {
                return toggleProjectFeature($projectId, $data);
            } elseif ($projectId && $action === 'status') {
                return updateProjectStatus($projectId, $data);
            }
            return ApiResponse::error('Invalid action', 400);
            
        case 'DELETE':
            if ($projectId) {
                return deleteAdminProject($projectId);
            }
            return ApiResponse::error('Project ID required', 400);
            
        default:
            return ApiResponse::error('Method not allowed', 405);
    }
}

function getAdminProjects($data) {
    require_role('moderator');
    
    try {
        $db = db();
        $page = max(1, (int)($data['page'] ?? 1));
        $perPage = min(100, max(1, (int)($data['per_page'] ?? 25)));
        $search = $data['search'] ?? '';
        $category = $data['category'] ?? null;
        $status = $data['status'] ?? null;
        $featured = isset($data['featured']) ? (bool)$data['featured'] : null;
        
        // Build base query
        $sql = "SELECT p.*, u.username as creator_username, u.display_name as creator_name
                FROM projects p
                LEFT JOIN users u ON p.created_by = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (p.title LIKE :search OR p.description LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if ($category) {
            $sql .= " AND p.category = :category";
            $params[':category'] = $category;
        }
        
        if ($status === 'published') {
            $sql .= " AND p.is_published = 1";
        } elseif ($status === 'draft') {
            $sql .= " AND p.is_published = 0";
        }
        
        if ($featured !== null) {
            $sql .= " AND p.is_featured = :featured";
            $params[':featured'] = $featured ? 1 : 0;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM projects p LEFT JOIN users u ON p.created_by = u.id WHERE 1=1";
        if ($search) {
            $countSql .= " AND (p.title LIKE :search OR p.description LIKE :search)";
        }
        if ($category) {
            $countSql .= " AND p.category = :category";
        }
        if ($status === 'published') {
            $countSql .= " AND p.is_published = 1";
        } elseif ($status === 'draft') {
            $countSql .= " AND p.is_published = 0";
        }
        if ($featured !== null) {
            $countSql .= " AND p.is_featured = " . ($featured ? 1 : 0);
        }
        
        $db->prepare($countSql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $totalResult = $db->fetch();
        $total = (int)$totalResult['total'];
        $totalPages = ceil($total / $perPage);
        
        // Add sorting and pagination
        $sql .= " ORDER BY p.created_at DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage);
        
        $db->prepare($sql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        
        $projects = $db->fetchAll();
        
        // Transform data
        foreach ($projects as &$project) {
            $project['technologies'] = json_decode($project['technologies'], true) ?? [];
            $project['features'] = json_decode($project['features'], true) ?? [];
        }
        
        return ApiResponse::paginated($projects, [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages
        ], 'Admin projects retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get admin projects error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve projects');
    }
}

function getAdminProject($projectId) {
    require_role('moderator');
    
    try {
        $projectModel = new Project();
        $project = $projectModel->find($projectId);
        
        if (!$project) {
            return ApiResponse::notFound('Project not found');
        }
        
        // Get additional admin data
        $project['images'] = $projectModel->getImages($projectId);
        $project['tags'] = $projectModel->getTags($projectId);
        $project['collaborators'] = $projectModel->getCollaborators($projectId);
        $project['comments'] = $projectModel->getComments($projectId, false); // Include unapproved
        
        return ApiResponse::success($project, 'Admin project retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get admin project error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve project');
    }
}

function approveProject($projectId) {
    require_role('moderator');
    
    try {
        $projectModel = new Project();
        $project = $projectModel->find($projectId);
        
        if (!$project) {
            return ApiResponse::notFound('Project not found');
        }
        
        $result = $projectModel->update($projectId, ['is_published' => 1]);
        
        if ($result) {
            // Notify project creator
            $userModel = new User();
            $userModel->createNotification(
                $project['created_by'],
                'project_approved',
                'Project Approved',
                "Your project '{$project['title']}' has been approved and is now published!",
                ['project_id' => $projectId]
            );
            
            // Log activity
            auth()->logActivity('project_approved', ['project_id' => $projectId]);
            
            return ApiResponse::success(['project_id' => $projectId], 'Project approved successfully');
        } else {
            return ApiResponse::serverError('Failed to approve project');
        }
        
    } catch (Exception $e) {
        error_log("Approve project error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to approve project');
    }
}

function toggleProjectFeature($projectId, $data) {
    require_role('admin');
    
    $validator = new ApiValidator($data);
    $validator->required(['featured']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $projectModel = new Project();
        $project = $projectModel->find($projectId);
        
        if (!$project) {
            return ApiResponse::notFound('Project not found');
        }
        
        $featured = (bool)$data['featured'];
        $result = $projectModel->update($projectId, ['is_featured' => $featured]);
        
        if ($result) {
            // Log activity
            auth()->logActivity('project_feature_toggled', [
                'project_id' => $projectId,
                'featured' => $featured
            ]);
            
            return ApiResponse::success([
                'project_id' => $projectId,
                'featured' => $featured
            ], $featured ? 'Project featured' : 'Project unfeatured');
        } else {
            return ApiResponse::serverError('Failed to update project feature status');
        }
        
    } catch (Exception $e) {
        error_log("Toggle project feature error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to update project feature status');
    }
}

function updateProjectStatus($projectId, $data) {
    require_role('moderator');
    
    $validator = new ApiValidator($data);
    $validator->required(['status'])
             ->in('status', ['draft', 'published', 'archived']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $projectModel = new Project();
        $project = $projectModel->find($projectId);
        
        if (!$project) {
            return ApiResponse::notFound('Project not found');
        }
        
        $isPublished = $data['status'] === 'published' ? 1 : 0;
        $result = $projectModel->update($projectId, ['is_published' => $isPublished]);
        
        if ($result) {
            // Log activity
            auth()->logActivity('project_status_updated', [
                'project_id' => $projectId,
                'old_status' => $project['is_published'] ? 'published' : 'draft',
                'new_status' => $data['status']
            ]);
            
            return ApiResponse::success([
                'project_id' => $projectId,
                'status' => $data['status']
            ], 'Project status updated successfully');
        } else {
            return ApiResponse::serverError('Failed to update project status');
        }
        
    } catch (Exception $e) {
        error_log("Update project status error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to update project status');
    }
}

function deleteAdminProject($projectId) {
    require_role('admin');
    
    try {
        $projectModel = new Project();
        $project = $projectModel->find($projectId);
        
        if (!$project) {
            return ApiResponse::notFound('Project not found');
        }
        
        $result = $projectModel->delete($projectId);
        
        if ($result) {
            // Log activity
            auth()->logActivity('admin_project_deleted', [
                'project_id' => $projectId,
                'title' => $project['title']
            ]);
            
            return ApiResponse::success(null, 'Project deleted successfully');
        } else {
            return ApiResponse::serverError('Failed to delete project');
        }
        
    } catch (Exception $e) {
        error_log("Delete admin project error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to delete project');
    }
}

function handleAdminInquiries($method, $segments, $data) {
    require_role('moderator');
    
    $inquiryId = $segments[0] ?? null;
    $action = $segments[1] ?? null;
    
    switch ($method) {
        case 'GET':
            if ($inquiryId) {
                return getAdminInquiry($inquiryId);
            } else {
                return getAdminInquiries($data);
            }
            
        case 'PUT':
            if ($inquiryId && $action === 'assign') {
                return assignInquiry($inquiryId, $data);
            } elseif ($inquiryId && $action === 'status') {
                return updateInquiryStatus($inquiryId, $data);
            }
            return ApiResponse::error('Invalid action', 400);
            
        default:
            return ApiResponse::error('Method not allowed', 405);
    }
}

function getAdminInquiries($data) {
    require_role('moderator');
    
    try {
        $db = db();
        $page = max(1, (int)($data['page'] ?? 1));
        $perPage = min(100, max(1, (int)($data['per_page'] ?? 25)));
        $status = $data['status'] ?? null;
        $priority = $data['priority'] ?? null;
        $assignedTo = $data['assigned_to'] ?? null;
        
        // Build base query
        $sql = "SELECT ci.*, s.name as service_name, u.username as assigned_username
                FROM contact_inquiries ci
                LEFT JOIN services s ON ci.service_id = s.id
                LEFT JOIN users u ON ci.assigned_to = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($status) {
            $sql .= " AND ci.status = :status";
            $params[':status'] = $status;
        }
        
        if ($priority) {
            $sql .= " AND ci.priority = :priority";
            $params[':priority'] = $priority;
        }
        
        if ($assignedTo) {
            $sql .= " AND ci.assigned_to = :assigned_to";
            $params[':assigned_to'] = $assignedTo;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM contact_inquiries ci WHERE 1=1";
        if ($status) {
            $countSql .= " AND ci.status = :status";
        }
        if ($priority) {
            $countSql .= " AND ci.priority = :priority";
        }
        if ($assignedTo) {
            $countSql .= " AND ci.assigned_to = :assigned_to";
        }
        
        $db->prepare($countSql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $totalResult = $db->fetch();
        $total = (int)$totalResult['total'];
        $totalPages = ceil($total / $perPage);
        
        // Add sorting and pagination
        $sql .= " ORDER BY ci.priority DESC, ci.created_at DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage);
        
        $db->prepare($sql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        
        $inquiries = $db->fetchAll();
        
        // Transform data
        foreach ($inquiries as &$inquiry) {
            $inquiry['additional_info'] = json_decode($inquiry['additional_info'], true) ?? [];
        }
        
        return ApiResponse::paginated($inquiries, [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages
        ], 'Admin inquiries retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get admin inquiries error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve inquiries');
    }
}

function getAdminInquiry($inquiryId) {
    require_role('moderator');
    
    try {
        $db = db();
        
        $sql = "SELECT ci.*, s.name as service_name, sp.name as package_name,
                       u.username as assigned_to_username, u.display_name as assigned_to_name
                FROM contact_inquiries ci 
                LEFT JOIN services s ON ci.service_id = s.id 
                LEFT JOIN service_packages sp ON ci.package_id = sp.id 
                LEFT JOIN users u ON ci.assigned_to = u.id
                WHERE ci.id = :id";
        
        $db->prepare($sql);
        $db->bind(':id', $inquiryId);
        $inquiry = $db->fetch();
        
        if (!$inquiry) {
            return ApiResponse::notFound('Inquiry not found');
        }
        
        // Cast JSON fields
        $inquiry['additional_info'] = json_decode($inquiry['additional_info'], true) ?? [];
        
        // Get responses
        $inquiry['responses'] = $db->raw("
            SELECT ir.*, u.username, u.display_name, u.avatar_url 
            FROM inquiry_responses ir 
            LEFT JOIN users u ON ir.user_id = u.id 
            WHERE ir.inquiry_id = :inquiry_id 
            ORDER BY ir.created_at ASC
        ", [':inquiry_id' => $inquiryId]);
        
        // Transform response data
        foreach ($inquiry['responses'] as &$response) {
            $response['attachments'] = json_decode($response['attachments'], true) ?? [];
        }
        
        return ApiResponse::success($inquiry, 'Admin inquiry retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get admin inquiry error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve inquiry');
    }
}

function assignInquiry($inquiryId, $data) {
    require_role('moderator');
    
    $validator = new ApiValidator($data);
    $validator->required(['user_id'])->integer('user_id');
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $db = db();
        
        // Check if inquiry exists
        $inquiry = $db->select('contact_inquiries', ['id' => $inquiryId]);
        if (empty($inquiry)) {
            return ApiResponse::notFound('Inquiry not found');
        }
        
        // Check if user exists and has appropriate role
        $user = $db->select('users', ['id' => $data['user_id']]);
        if (empty($user)) {
            return ApiResponse::notFound('User not found');
        }
        
        $user = $user[0];
        if (!in_array($user['role'], ['moderator', 'admin', 'superadmin'])) {
            return ApiResponse::error('User must have moderator role or higher', 400);
        }
        
        // Update inquiry
        $result = $db->update('contact_inquiries', [
            'assigned_to' => $data['user_id'],
            'status' => 'in_progress'
        ], ['id' => $inquiryId]);
        
        if ($result) {
            // Notify assigned user
            $userModel = new User();
            $userModel->createNotification(
                $data['user_id'],
                'inquiry_assigned',
                'Inquiry Assigned',
                "You have been assigned to handle inquiry #{$inquiryId}",
                ['inquiry_id' => $inquiryId]
            );
            
            // Log activity
            auth()->logActivity('inquiry_assigned', [
                'inquiry_id' => $inquiryId,
                'assigned_to' => $data['user_id']
            ]);
            
            return ApiResponse::success([
                'inquiry_id' => $inquiryId,
                'assigned_to' => $data['user_id']
            ], 'Inquiry assigned successfully');
        } else {
            return ApiResponse::serverError('Failed to assign inquiry');
        }
        
    } catch (Exception $e) {
        error_log("Assign inquiry error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to assign inquiry');
    }
}

function updateInquiryStatus($inquiryId, $data) {
    require_role('moderator');
    
    $validator = new ApiValidator($data);
    $validator->required(['status'])
             ->in('status', ['new', 'in_progress', 'responded', 'resolved', 'closed']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $db = db();
        
        $inquiry = $db->select('contact_inquiries', ['id' => $inquiryId]);
        if (empty($inquiry)) {
            return ApiResponse::notFound('Inquiry not found');
        }
        
        $result = $db->update('contact_inquiries', [
            'status' => $data['status']
        ], ['id' => $inquiryId]);
        
        if ($result) {
            // Log activity
            auth()->logActivity('inquiry_status_updated', [
                'inquiry_id' => $inquiryId,
                'old_status' => $inquiry[0]['status'],
                'new_status' => $data['status']
            ]);
            
            return ApiResponse::success([
                'inquiry_id' => $inquiryId,
                'status' => $data['status']
            ], 'Inquiry status updated successfully');
        } else {
            return ApiResponse::serverError('Failed to update inquiry status');
        }
        
    } catch (Exception $e) {
        error_log("Update inquiry status error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to update inquiry status');
    }
}

function handleAdminSettings($method, $segments, $data) {
    require_role('admin');
    
    $settingKey = $segments[0] ?? null;
    
    switch ($method) {
        case 'GET':
            if ($settingKey) {
                return getAdminSetting($settingKey);
            } else {
                return getAdminSettings($data);
            }
            
        case 'PUT':
            if ($settingKey) {
                return updateAdminSetting($settingKey, $data);
            }
            return ApiResponse::error('Setting key required', 400);
            
        case 'POST':
            return createAdminSetting($data);
            
        case 'DELETE':
            if ($settingKey) {
                return deleteAdminSetting($settingKey);
            }
            return ApiResponse::error('Setting key required', 400);
            
        default:
            return ApiResponse::error('Method not allowed', 405);
    }
}

function getAdminSettings($data) {
    require_role('admin');
    
    try {
        $db = db();
        
        $category = $data['category'] ?? null;
        $isPublic = isset($data['public']) ? (bool)$data['public'] : null;
        
        $conditions = [];
        if ($isPublic !== null) {
            $conditions['is_public'] = $isPublic ? 1 : 0;
        }
        
        $settings = $db->select('settings', $conditions, '*', '`key` ASC');
        
        // Transform settings
        $result = [];
        foreach ($settings as $setting) {
            $value = $setting['value'];
            
            // Cast value based on type
            switch ($setting['type']) {
                case 'boolean':
                    $value = $value === 'true' || $value === '1';
                    break;
                case 'number':
                    $value = is_numeric($value) ? (float)$value : 0;
                    break;
                case 'json':
                    $value = json_decode($value, true) ?? [];
                    break;
            }
            
            $result[] = [
                'key' => $setting['key'],
                'value' => $value,
                'type' => $setting['type'],
                'description' => $setting['description'],
                'is_public' => (bool)$setting['is_public']
            ];
        }
        
        return ApiResponse::success($result, 'Admin settings retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get admin settings error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve settings');
    }
}

function getAdminSetting($settingKey) {
    require_role('admin');
    
    try {
        $db = db();
        
        $setting = $db->select('settings', ['key' => $settingKey]);
        if (empty($setting)) {
            return ApiResponse::notFound('Setting not found');
        }
        
        $setting = $setting[0];
        
        // Cast value based on type
        $value = $setting['value'];
        switch ($setting['type']) {
            case 'boolean':
                $value = $value === 'true' || $value === '1';
                break;
            case 'number':
                $value = is_numeric($value) ? (float)$value : 0;
                break;
            case 'json':
                $value = json_decode($value, true) ?? [];
                break;
        }
        
        $result = [
            'key' => $setting['key'],
            'value' => $value,
            'type' => $setting['type'],
            'description' => $setting['description'],
            'is_public' => (bool)$setting['is_public']
        ];
        
        return ApiResponse::success($result, 'Admin setting retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get admin setting error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve setting');
    }
}

function updateAdminSetting($settingKey, $data) {
    require_role('admin');
    
    $validator = new ApiValidator($data);
    $validator->required(['value']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $db = db();
        
        // Check if setting exists
        $setting = $db->select('settings', ['key' => $settingKey]);
        if (empty($setting)) {
            return ApiResponse::notFound('Setting not found');
        }
        
        $setting = $setting[0];
        
        // Convert value based on type
        $value = $data['value'];
        switch ($setting['type']) {
            case 'boolean':
                $value = $value ? 'true' : 'false';
                break;
            case 'number':
                $value = (string)$value;
                break;
            case 'json':
                $value = json_encode($value);
                break;
            default:
                $value = (string)$value;
        }
        
        // Update setting
        $result = $db->update('settings', ['value' => $value], ['key' => $settingKey]);
        
        if ($result) {
            // Log activity
            auth()->logActivity('setting_updated', [
                'setting_key' => $settingKey,
                'old_value' => $setting['value'],
                'new_value' => $value
            ]);
            
            return ApiResponse::success([
                'key' => $settingKey,
                'value' => $data['value']
            ], 'Setting updated successfully');
        } else {
            return ApiResponse::serverError('Failed to update setting');
        }
        
    } catch (Exception $e) {
        error_log("Update admin setting error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to update setting');
    }
}

function createAdminSetting($data) {
    require_role('admin');
    
    $validator = new ApiValidator($data);
    $validator->required(['key', 'value', 'type'])
             ->in('type', ['string', 'number', 'boolean', 'json']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $db = db();
        
        // Check if setting already exists
        $existing = $db->select('settings', ['key' => $data['key']]);
        if (!empty($existing)) {
            return ApiResponse::error('Setting already exists', 400);
        }
        
        // Convert value based on type
        $value = $data['value'];
        switch ($data['type']) {
            case 'boolean':
                $value = $value ? 'true' : 'false';
                break;
            case 'number':
                $value = (string)$value;
                break;
            case 'json':
                $value = json_encode($value);
                break;
            default:
                $value = (string)$value;
        }
        
        $settingData = [
            'key' => $data['key'],
            'value' => $value,
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'is_public' => isset($data['is_public']) ? (bool)$data['is_public'] : false
        ];
        
        $result = $db->insert('settings', $settingData);
        
        if ($result) {
            // Log activity
            auth()->logActivity('setting_created', [
                'setting_key' => $data['key'],
                'type' => $data['type']
            ]);
            
            return ApiResponse::success([
                'key' => $data['key'],
                'value' => $data['value'],
                'type' => $data['type']
            ], 'Setting created successfully', 201);
        } else {
            return ApiResponse::serverError('Failed to create setting');
        }
        
    } catch (Exception $e) {
        error_log("Create admin setting error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to create setting');
    }
}

function deleteAdminSetting($settingKey) {
    require_role('admin');
    
    try {
        $db = db();
        
        $setting = $db->select('settings', ['key' => $settingKey]);
        if (empty($setting)) {
            return ApiResponse::notFound('Setting not found');
        }
        
        $result = $db->delete('settings', ['key' => $settingKey]);
        
        if ($result) {
            // Log activity
            auth()->logActivity('setting_deleted', [
                'setting_key' => $settingKey
            ]);
            
            return ApiResponse::success(null, 'Setting deleted successfully');
        } else {
            return ApiResponse::serverError('Failed to delete setting');
        }
        
    } catch (Exception $e) {
        error_log("Delete admin setting error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to delete setting');
    }
}