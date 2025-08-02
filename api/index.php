<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    $basePath = '/api/v1';
    if (strpos($path, $basePath) === 0) {
        $path = substr($path, strlen($basePath));
    }

    $path = trim($path, '/');
    $segments = empty($path) ? [] : explode('/', $path);
    $requestData = [];

    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $jsonInput = file_get_contents('php://input');
        if (!empty($jsonInput)) {
            $requestData = json_decode($jsonInput, true) ?? [];
        }
        $requestData = array_merge($_POST, $requestData);
    } else {
        $requestData = $_GET;
    }

    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimiter = new RateLimiter("api_$clientIp", API_RATE_LIMIT, API_RATE_LIMIT_WINDOW);
    
    if (!$rateLimiter->attempt()) {
        ApiResponse::error('Rate limit exceeded', 429, [
            'retry_after' => $rateLimiter->retryAfter(),
            'limit' => API_RATE_LIMIT,
            'window' => API_RATE_LIMIT_WINDOW
        ])->send();
    }

    header('X-RateLimit-Limit: ' . API_RATE_LIMIT);
    header('X-RateLimit-Remaining: ' . $rateLimiter->remaining());
    header('X-RateLimit-Reset: ' . (time() + $rateLimiter->retryAfter()));
    
    $response = routeRequest($method, $segments, $requestData);

    if ($response instanceof ApiResponse) {
        $response->send();
    } else {
        ApiResponse::success($response)->send();
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    if (DEBUG_MODE) {
        ApiResponse::serverError($e->getMessage())->send();
    } else {
        ApiResponse::serverError('An unexpected error occurred')->send();
    }
}

function routeRequest($method, $segments, $data) {
    $endpoint = $segments[0] ?? '';
    
    switch ($endpoint) {
        case '':
            return handleApiInfo();
            
        case 'auth':
            require_once __DIR__ . '/auth.php';
            return handleAuthRoutes($method, array_slice($segments, 1), $data);
            
        case 'users':
            return handleUserRoutes($method, array_slice($segments, 1), $data);
            
        case 'projects':
            require_once __DIR__ . '/projects.php';
            return handleProjectRoutes($method, array_slice($segments, 1), $data);
            
        case 'services':
            require_once __DIR__ . '/services.php';
            return handleServiceRoutes($method, array_slice($segments, 1), $data);
            
        case 'contact':
            require_once __DIR__ . '/contact.php';
            return handleContactRoutes($method, array_slice($segments, 1), $data);
            
        case 'analytics':
            require_once __DIR__ . '/analytics.php';
            return handleAnalyticsRoutes($method, array_slice($segments, 1), $data);
            
        case 'roblox':
            require_once __DIR__ . '/roblox.php';
            return handleRobloxRoutes($method, array_slice($segments, 1), $data);
            
        case 'upload':
            require_once __DIR__ . '/upload.php';
            return handleUploadRoutes($method, array_slice($segments, 1), $data);
            
        case 'cookie-consent':
            require_once __DIR__ . '/cookie-consent.php';
            return handleCookieConsentRoutes($method, array_slice($segments, 1), $data);
            
        case 'admin':
            require_once __DIR__ . '/admin.php';
            return handleAdminRoutes($method, array_slice($segments, 1), $data);
            
        default:
            return ApiResponse::notFound('Endpoint not found');
    }
}

// Add missing route handlers
function handleAuthRoutes($method, $segments, $data) {
    $action = $segments[0] ?? '';
    
    switch ($action) {
        case 'login':
            if ($method !== 'POST') {
                return ApiResponse::error('Method not allowed', 405);
            }
            return handleLogin($data);
            
        case 'logout':
            if ($method !== 'POST') {
                return ApiResponse::error('Method not allowed', 405);
            }
            return handleLogout();
            
        case 'me':
            if ($method !== 'GET') {
                return ApiResponse::error('Method not allowed', 405);
            }
            return handleGetCurrentUser();
            
        case 'refresh':
            if ($method !== 'POST') {
                return ApiResponse::error('Method not allowed', 405);
            }
            return handleRefreshToken($data);
            
        case 'roblox':
            return handleRobloxAuth($method, array_slice($segments, 1), $data);
            
        default:
            return ApiResponse::notFound('Auth endpoint not found');
    }
}

function handleProjectRoutes($method, $segments, $data) {
    $projectId = $segments[0] ?? null;
    $action = $segments[1] ?? null;
    
    switch ($method) {
        case 'GET':
            if ($projectId && $action === 'comments') {
                return getProjectComments($projectId, $data);
            } elseif ($projectId && $action === 'images') {
                return getProjectImages($projectId);
            } elseif ($projectId && $action === 'related') {
                return getRelatedProjects($projectId, $data);
            } elseif ($projectId) {
                return getProject($projectId);
            } else {
                return getProjects($data);
            }
            
        case 'POST':
            if ($projectId && $action === 'like') {
                return toggleProjectLike($projectId);
            } elseif ($projectId && $action === 'comment') {
                return addProjectComment($projectId, $data);
            } elseif ($projectId && $action === 'view') {
                return recordProjectView($projectId);
            } elseif (!$projectId) {
                return createProject($data);
            }
            return ApiResponse::error('Invalid action', 400);
            
        case 'PUT':
            if ($projectId) {
                return updateProject($projectId, $data);
            }
            return ApiResponse::error('Project ID required', 400);
            
        case 'DELETE':
            if ($projectId) {
                return deleteProject($projectId);
            }
            return ApiResponse::error('Project ID required', 400);
            
        default:
            return ApiResponse::error('Method not allowed', 405);
    }
}

function handleServiceRoutes($method, $segments, $data) {
    $serviceId = $segments[0] ?? null;
    $action = $segments[1] ?? null;
    
    switch ($method) {
        case 'GET':
            if ($serviceId && $action === 'packages') {
                return getServicePackages($serviceId);
            } elseif ($serviceId && $action === 'testimonials') {
                return getServiceTestimonials($serviceId);
            } elseif ($serviceId && $action === 'faqs') {
                return getServiceFAQs($serviceId);
            } elseif ($serviceId) {
                return getService($serviceId);
            } else {
                return getServices($data);
            }
            
        case 'POST':
            if (!$serviceId) {
                return createService($data);
            }
            return ApiResponse::error('Method not allowed', 405);
            
        case 'PUT':
            if ($serviceId) {
                return updateService($serviceId, $data);
            }
            return ApiResponse::error('Service ID required', 400);
            
        case 'DELETE':
            if ($serviceId) {
                return deleteService($serviceId);
            }
            return ApiResponse::error('Service ID required', 400);
            
        default:
            return ApiResponse::error('Method not allowed', 405);
    }
}

function handleContactRoutes($method, $segments, $data) {
    $inquiryId = $segments[0] ?? null;
    $action = $segments[1] ?? null;
    
    switch ($method) {
        case 'GET':
            if ($inquiryId && $action === 'responses') {
                return getInquiryResponses($inquiryId);
            } elseif ($inquiryId) {
                return getInquiry($inquiryId);
            } else {
                return getInquiries($data);
            }
            
        case 'POST':
            if ($inquiryId && $action === 'respond') {
                return respondToInquiry($inquiryId, $data);
            } elseif (!$inquiryId) {
                return createInquiry($data);
            }
            return ApiResponse::error('Invalid action', 400);
            
        case 'PUT':
            if ($inquiryId) {
                return updateInquiry($inquiryId, $data);
            }
            return ApiResponse::error('Inquiry ID required', 400);
            
        case 'DELETE':
            if ($inquiryId) {
                return deleteInquiry($inquiryId);
            }
            return ApiResponse::error('Inquiry ID required', 400);
            
        default:
            return ApiResponse::error('Method not allowed', 405);
    }
}

function handleAnalyticsRoutes($method, $segments, $data) {
    require_permission('view_analytics');
    
    $type = $segments[0] ?? '';
    
    if ($method !== 'GET') {
        return ApiResponse::error('Method not allowed', 405);
    }
    
    switch ($type) {
        case 'dashboard':
            return getAnalyticsDashboard($data);
        case 'projects':
            return getProjectAnalytics($data);
        case 'users':
            return getUserAnalytics($data);
        case 'traffic':
            return getTrafficAnalytics($data);
        default:
            return getGeneralAnalytics($data);
    }
}

function handleRobloxRoutes($method, $segments, $data) {
    $action = $segments[0] ?? '';
    
    switch ($action) {
        case 'oauth':
            return handleRobloxOAuthRoutes($method, array_slice($segments, 1), $data);
        case 'user':
            return getRobloxUserInfo($data);
        case 'games':
            return getRobloxUserGames($data);
        case 'verify':
            return verifyRobloxOwnership($data);
        default:
            return ApiResponse::notFound('Roblox endpoint not found');
    }
}

function handleUploadRoutes($method, $segments, $data) {
    require_auth();
    
    if ($method !== 'POST') {
        return ApiResponse::error('Method not allowed', 405);
    }
    
    $type = $segments[0] ?? '';
    
    switch ($type) {
        case 'avatar':
            return uploadAvatar();
        case 'project':
            return uploadProjectImage($data);
        case 'temp':
            return uploadTempFile();
        default:
            return uploadGeneral($data);
    }
}

function handleAdminRoutes($method, $segments, $data) {
    require_role('moderator');
    
    $resource = $segments[0] ?? '';
    
    switch ($resource) {
        case 'stats':
            return getAdminStats();
        case 'users':
            return handleAdminUsers($method, array_slice($segments, 1), $data);
        case 'projects':
            return handleAdminProjects($method, array_slice($segments, 1), $data);
        case 'inquiries':
            return handleAdminInquiries($method, array_slice($segments, 1), $data);
        case 'settings':
            return handleAdminSettings($method, array_slice($segments, 1), $data);
        default:
            return ApiResponse::notFound('Admin endpoint not found');
    }
}

function handleApiInfo() {
    return ApiResponse::success([
        'name' => 'BluFox Studio API',
        'version' => API_VERSION,
        'description' => 'RESTful API for BluFox Studio',
        'documentation' => SITE_URL . '/docs/api',
        'endpoints' => [
            'auth' => 'Authentication endpoints',
            'users' => 'User management',
            'projects' => 'Project management',
            'services' => 'Service information',
            'contact' => 'Contact form submissions',
            'analytics' => 'Analytics data',
            'roblox' => 'Roblox integration',
            'upload' => 'File upload endpoints',
            'cookie-consent' => 'Cookie consent management',
            'admin' => 'Administrative endpoints'
        ],
        'rate_limit' => [
            'requests_per_hour' => API_RATE_LIMIT,
            'window_seconds' => API_RATE_LIMIT_WINDOW
        ],
        'server_time' => date('c'),
        'environment' => ENVIRONMENT
    ], 'BluFox Studio API ' . API_VERSION);
}

function handleUserRoutes($method, $segments, $data) {
    $userId = $segments[0] ?? null;
    $action = $segments[1] ?? null;
    
    switch ($method) {
        case 'GET':
            if ($userId && $action === 'projects') {
                return getUserProjects($userId, $data);
            } elseif ($userId && $action === 'profile') {
                return getUserProfile($userId);
            } elseif ($userId && $action === 'activity') {
                return getUserActivity($userId);
            } elseif ($userId) {
                return getUser($userId);
            } else {
                return getUsers($data);
            }
            
        case 'POST':
            if (!$userId) {
                return createUser($data);
            }
            return ApiResponse::error('Method not allowed', 405);
            
        case 'PUT':
            if ($userId && $action === 'profile') {
                return updateUserProfile($userId, $data);
            } elseif ($userId && $action === 'preferences') {
                return updateUserPreferences($userId, $data);
            } elseif ($userId) {
                return updateUser($userId, $data);
            }
            return ApiResponse::error('User ID required', 400);
            
        case 'DELETE':
            if ($userId) {
                return deleteUser($userId);
            }
            return ApiResponse::error('User ID required', 400);
            
        default:
            return ApiResponse::error('Method not allowed', 405);
    }
}

// User-related functions
function getUsers($data) {
    require_permission('view_users');
    
    try {
        $userModel = new User();
        $page = max(1, (int)($data['page'] ?? 1));
        $perPage = min(50, max(1, (int)($data['per_page'] ?? 15)));
        $search = $data['search'] ?? '';
        
        if (!empty($search)) {
            $result = $userModel->searchUsers($search, $page, $perPage);
        } else {
            $result = $userModel->paginate($page, $perPage, ['status' => 'active'], 'created_at DESC');
        }
        
        return ApiResponse::paginated($result['data'], $result['pagination'], 'Users retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get users error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve users');
    }
}

function getUser($userId) {
    try {
        $userModel = new User();
        $user = $userModel->find($userId);
        
        if (!$user) {
            return ApiResponse::notFound('User not found');
        }
        
        // Check if user can view this profile
        if (!is_logged_in() || (current_user()['id'] !== $user['id'] && !has_role('moderator'))) {
            // Return limited public info
            $publicUser = [
                'id' => $user['id'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'avatar_url' => $user['avatar_url'],
                'bio' => $user['bio'],
                'created_at' => $user['created_at']
            ];
            return ApiResponse::success($publicUser, 'Public user profile retrieved');
        }
        
        // Return full profile for own profile or admins
        $profile = $userModel->getProfile($userId);
        return ApiResponse::success($profile, 'User profile retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get user error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve user');
    }
}

function getUserProjects($userId, $data) {
    try {
        $userModel = new User();
        $user = $userModel->find($userId);
        
        if (!$user) {
            return ApiResponse::notFound('User not found');
        }
        
        $limit = min(50, max(1, (int)($data['limit'] ?? 10)));
        $projects = $userModel->getProjects($userId, $limit);
        
        return ApiResponse::success($projects, 'User projects retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get user projects error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve user projects');
    }
}

function getUserProfile($userId) {
    try {
        $userModel = new User();
        $profile = $userModel->getProfile($userId);
        
        if (!$profile) {
            return ApiResponse::notFound('User profile not found');
        }
        
        return ApiResponse::success($profile, 'User profile retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get user profile error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve user profile');
    }
}

function updateUserProfile($userId, $data) {
    require_auth();
    
    // Check if user can update this profile
    if (current_user()['id'] !== (int)$userId && !has_role('admin')) {
        return ApiResponse::forbidden('You can only update your own profile');
    }
    
    try {
        $userModel = new User();
        $result = $userModel->updateProfile($userId, $data);
        
        if ($result) {
            return ApiResponse::success(['profile_updated' => true], 'Profile updated successfully');
        } else {
            return ApiResponse::serverError('Failed to update profile');
        }
        
    } catch (Exception $e) {
        error_log("Update user profile error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to update profile');
    }
}

function updateUserPreferences($userId, $data) {
    require_auth();
    
    // Check if user can update preferences
    if (current_user()['id'] !== (int)$userId) {
        return ApiResponse::forbidden('You can only update your own preferences');
    }
    
    try {
        $userModel = new User();
        $result = $userModel->updatePreferences($userId, $data);
        
        if ($result) {
            return ApiResponse::success(['preferences_updated' => true], 'Preferences updated successfully');
        } else {
            return ApiResponse::serverError('Failed to update preferences');
        }
        
    } catch (Exception $e) {
        error_log("Update user preferences error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to update preferences');
    }
}

function createUser($data) {
    // This would typically be handled by the registration process
    return ApiResponse::error('User registration should be done through the auth endpoints', 400);
}

function updateUser($userId, $data) {
    require_auth();
    
    // Only allow users to update their own basic info, or admins to update anyone
    if (current_user()['id'] !== (int)$userId && !has_role('admin')) {
        return ApiResponse::forbidden('You can only update your own information');
    }
    
    try {
        $userModel = new User();
        $allowedFields = ['display_name', 'bio'];
        
        // Admins can update more fields
        if (has_role('admin')) {
            $allowedFields = array_merge($allowedFields, ['email', 'status']);
        }
        
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($updateData)) {
            return ApiResponse::error('No valid fields to update', 400);
        }
        
        $result = $userModel->update($userId, $updateData);
        
        if ($result) {
            return ApiResponse::success($result, 'User updated successfully');
        } else {
            return ApiResponse::serverError('Failed to update user');
        }
        
    } catch (Exception $e) {
        error_log("Update user error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to update user');
    }
}

function deleteUser($userId) {
    require_auth();
    
    // Only allow users to delete their own account, or admins to delete anyone
    if (current_user()['id'] !== (int)$userId && !has_role('admin')) {
        return ApiResponse::forbidden('You can only delete your own account');
    }
    
    try {
        $userModel = new User();
        $user = $userModel->find($userId);
        
        if (!$user) {
            return ApiResponse::notFound('User not found');
        }
        
        // Prevent deletion of superadmin users
        if ($user['role'] === 'superadmin' && !has_role('superadmin')) {
            return ApiResponse::forbidden('Cannot delete superadmin users');
        }
        
        $result = $userModel->delete($userId);
        
        if ($result) {
            // If user deleted their own account, logout
            if (current_user()['id'] === (int)$userId) {
                auth()->logout();
            }
            
            return ApiResponse::success(null, 'User deleted successfully');
        } else {
            return ApiResponse::serverError('Failed to delete user');
        }
        
    } catch (Exception $e) {
        error_log("Delete user error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to delete user');
    }
}