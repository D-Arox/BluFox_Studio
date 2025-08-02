<?php
// api/v1/analytics.php - Analytics API Endpoints

function getAnalyticsDashboard($data) {
    require_permission('view_analytics');
    
    try {
        $db = db();
        $timeframe = $data['timeframe'] ?? '30d';
        
        // Calculate date range
        switch ($timeframe) {
            case '7d':
                $startDate = date('Y-m-d', strtotime('-7 days'));
                break;
            case '30d':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                break;
            case '90d':
                $startDate = date('Y-m-d', strtotime('-90 days'));
                break;
            case '1y':
                $startDate = date('Y-m-d', strtotime('-1 year'));
                break;
            default:
                $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        
        $dashboard = [];
        
        // Website overview
        $dashboard['overview'] = [
            'total_projects' => $db->count('projects', ['is_published' => 1]),
            'total_users' => $db->count('users', ['status' => 'active']),
            'total_services' => $db->count('services', ['is_active' => 1]),
            'total_inquiries' => $db->count('contact_inquiries')
        ];
        
        // Project statistics
        $projectStats = $db->rawSingle("
            SELECT 
                SUM(view_count) as total_views,
                SUM(like_count) as total_likes,
                SUM(download_count) as total_downloads
            FROM projects 
            WHERE is_published = 1
        ");
        
        $dashboard['projects'] = [
            'total_views' => (int)($projectStats['total_views'] ?? 0),
            'total_likes' => (int)($projectStats['total_likes'] ?? 0),
            'total_downloads' => (int)($projectStats['total_downloads'] ?? 0),
            'featured_count' => $db->count('projects', ['is_published' => 1, 'is_featured' => 1])
        ];
        
        // Recent activity
        $dashboard['recent_activity'] = [
            'new_users' => $db->count('users', ["created_at >= '$startDate'"]),
            'new_projects' => $db->count('projects', ["created_at >= '$startDate'"]),
            'new_inquiries' => $db->count('contact_inquiries', ["created_at >= '$startDate'"])
        ];
        
        // Top projects by views
        $dashboard['top_projects'] = $db->raw("
            SELECT id, title, view_count, like_count, created_at 
            FROM projects 
            WHERE is_published = 1 
            ORDER BY view_count DESC 
            LIMIT 10
        ");
        
        // Daily page views for the timeframe
        $dashboard['daily_views'] = $db->raw("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as views
            FROM page_views 
            WHERE created_at >= :start_date
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [':start_date' => $startDate]);
        
        // User registrations over time
        $dashboard['user_registrations'] = $db->raw("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as registrations
            FROM users 
            WHERE created_at >= :start_date
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [':start_date' => $startDate]);
        
        // Contact inquiries by type
        $dashboard['inquiries_by_type'] = $db->raw("
            SELECT type, COUNT(*) as count 
            FROM contact_inquiries 
            WHERE created_at >= :start_date
            GROUP BY type
        ", [':start_date' => $startDate]);
        
        // Most popular services
        $dashboard['popular_services'] = $db->raw("
            SELECT s.name, s.view_count, s.inquiry_count
            FROM services s 
            WHERE s.is_active = 1 
            ORDER BY s.view_count DESC 
            LIMIT 5
        ");
        
        return ApiResponse::success($dashboard, 'Analytics dashboard retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get analytics dashboard error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve analytics dashboard');
    }
}

function getProjectAnalytics($data) {
    require_permission('view_analytics');
    
    try {
        $db = db();
        $projectId = $data['project_id'] ?? null;
        $timeframe = $data['timeframe'] ?? '30d';
        
        // Calculate date range
        switch ($timeframe) {
            case '7d':
                $startDate = date('Y-m-d', strtotime('-7 days'));
                break;
            case '30d':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                break;
            case '90d':
                $startDate = date('Y-m-d', strtotime('-90 days'));
                break;
            default:
                $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        
        $analytics = [];
        
        if ($projectId) {
            // Single project analytics
            $project = $db->select('projects', ['id' => $projectId]);
            if (empty($project)) {
                return ApiResponse::notFound('Project not found');
            }
            
            $project = $project[0];
            
            // Basic stats
            $analytics['project'] = [
                'id' => $project['id'],
                'title' => $project['title'],
                'total_views' => (int)$project['view_count'],
                'total_likes' => (int)$project['like_count'],
                'total_downloads' => (int)$project['download_count']
            ];
            
            // Daily views for the project
            $analytics['daily_views'] = $db->raw("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as views
                FROM project_views 
                WHERE project_id = :project_id AND created_at >= :start_date
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ", [':project_id' => $projectId, ':start_date' => $startDate]);
            
            // Views by country/region (if tracking IP)
            $analytics['views_by_region'] = $db->raw("
                SELECT 
                    SUBSTRING_INDEX(ip_address, '.', 2) as region,
                    COUNT(*) as views
                FROM project_views 
                WHERE project_id = :project_id AND created_at >= :start_date
                GROUP BY region
                ORDER BY views DESC
                LIMIT 10
            ", [':project_id' => $projectId, ':start_date' => $startDate]);
            
            // Referrer analysis
            $analytics['top_referrers'] = $db->raw("
                SELECT 
                    referrer,
                    COUNT(*) as views
                FROM project_views 
                WHERE project_id = :project_id 
                AND referrer IS NOT NULL 
                AND created_at >= :start_date
                GROUP BY referrer
                ORDER BY views DESC
                LIMIT 10
            ", [':project_id' => $projectId, ':start_date' => $startDate]);
            
        } else {
            // All projects analytics
            
            // Projects by category performance
            $analytics['category_performance'] = $db->raw("
                SELECT 
                    category,
                    COUNT(*) as project_count,
                    SUM(view_count) as total_views,
                    SUM(like_count) as total_likes,
                    AVG(view_count) as avg_views
                FROM projects 
                WHERE is_published = 1
                GROUP BY category
                ORDER BY total_views DESC
            ");
            
            // Top performing projects
            $analytics['top_projects'] = $db->raw("
                SELECT 
                    id, title, category, view_count, like_count, download_count,
                    created_at
                FROM projects 
                WHERE is_published = 1 
                ORDER BY view_count DESC 
                LIMIT 20
            ");
            
            // Project views over time
            $analytics['project_views_timeline'] = $db->raw("
                SELECT 
                    DATE(pv.created_at) as date,
                    COUNT(*) as views,
                    COUNT(DISTINCT pv.project_id) as unique_projects
                FROM project_views pv
                WHERE pv.created_at >= :start_date
                GROUP BY DATE(pv.created_at)
                ORDER BY date ASC
            ", [':start_date' => $startDate]);
            
            // Most liked projects
            $analytics['most_liked'] = $db->raw("
                SELECT 
                    id, title, like_count, view_count
                FROM projects 
                WHERE is_published = 1 AND like_count > 0
                ORDER BY like_count DESC 
                LIMIT 10
            ");
        }
        
        return ApiResponse::success($analytics, 'Project analytics retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get project analytics error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve project analytics');
    }
}

function getUserAnalytics($data) {
    require_permission('view_analytics');
    
    try {
        $db = db();
        $timeframe = $data['timeframe'] ?? '30d';
        
        // Calculate date range
        switch ($timeframe) {
            case '7d':
                $startDate = date('Y-m-d', strtotime('-7 days'));
                break;
            case '30d':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                break;
            case '90d':
                $startDate = date('Y-m-d', strtotime('-90 days'));
                break;
            default:
                $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        
        $analytics = [];
        
        // User growth
        $analytics['user_growth'] = $db->raw("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as new_users
            FROM users 
            WHERE created_at >= :start_date
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [':start_date' => $startDate]);
        
        // User activity
        $analytics['user_activity'] = $db->raw("
            SELECT 
                DATE(last_login) as date,
                COUNT(*) as active_users
            FROM users 
            WHERE last_login >= :start_date AND status = 'active'
            GROUP BY DATE(last_login)
            ORDER BY date ASC
        ", [':start_date' => $startDate]);
        
        // Users by role
        $analytics['users_by_role'] = $db->raw("
            SELECT role, COUNT(*) as count 
            FROM users 
            WHERE status = 'active'
            GROUP BY role
        ");
        
        // Most active users (by project creation)
        $analytics['most_active_creators'] = $db->raw("
            SELECT 
                u.id, u.username, u.display_name,
                COUNT(p.id) as project_count,
                SUM(p.view_count) as total_views
            FROM users u
            LEFT JOIN projects p ON u.id = p.created_by
            WHERE u.status = 'active'
            GROUP BY u.id
            HAVING project_count > 0
            ORDER BY project_count DESC, total_views DESC
            LIMIT 10
        ");
        
        // User engagement metrics
        $analytics['engagement'] = [
            'total_comments' => $db->count('project_comments', ["created_at >= '$startDate'"]),
            'total_likes' => $db->count('project_likes', ["created_at >= '$startDate'"]),
            'total_project_views' => $db->count('project_views', ["created_at >= '$startDate'"])
        ];
        
        // Top contributors
        $analytics['top_contributors'] = $db->raw("
            SELECT 
                u.id, u.username, u.display_name, u.avatar_url,
                COUNT(DISTINCT p.id) as projects,
                COUNT(DISTINCT pc.id) as comments,
                COUNT(DISTINCT pl.id) as likes_given
            FROM users u
            LEFT JOIN projects p ON u.id = p.created_by
            LEFT JOIN project_comments pc ON u.id = pc.user_id
            LEFT JOIN project_likes pl ON u.id = pl.user_id
            WHERE u.status = 'active'
            GROUP BY u.id
            HAVING (projects > 0 OR comments > 0 OR likes_given > 0)
            ORDER BY (projects * 3 + comments + likes_given) DESC
            LIMIT 10
        ");
        
        return ApiResponse::success($analytics, 'User analytics retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get user analytics error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve user analytics');
    }
}

function getTrafficAnalytics($data) {
    require_permission('view_analytics');
    
    try {
        $db = db();
        $timeframe = $data['timeframe'] ?? '30d';
        
        // Calculate date range
        switch ($timeframe) {
            case '7d':
                $startDate = date('Y-m-d', strtotime('-7 days'));
                break;
            case '30d':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                break;
            case '90d':
                $startDate = date('Y-m-d', strtotime('-90 days'));
                break;
            default:
                $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        
        $analytics = [];
        
        // Page views over time
        $analytics['page_views'] = $db->raw("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as views,
                COUNT(DISTINCT session_id) as unique_sessions,
                COUNT(DISTINCT user_id) as unique_users
            FROM page_views 
            WHERE created_at >= :start_date
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [':start_date' => $startDate]);
        
        // Most visited pages
        $analytics['popular_pages'] = $db->raw("
            SELECT 
                page_type,
                page_id,
                COUNT(*) as views,
                COUNT(DISTINCT session_id) as unique_sessions
            FROM page_views 
            WHERE created_at >= :start_date
            GROUP BY page_type, page_id
            ORDER BY views DESC
            LIMIT 20
        ", [':start_date' => $startDate]);
        
        // Top referrers
        $analytics['top_referrers'] = $db->raw("
            SELECT 
                COALESCE(referrer, 'Direct') as referrer,
                COUNT(*) as visits
            FROM page_views 
            WHERE created_at >= :start_date
            GROUP BY referrer
            ORDER BY visits DESC
            LIMIT 15
        ", [':start_date' => $startDate]);
        
        // Traffic sources
        $analytics['traffic_sources'] = $db->raw("
            SELECT 
                CASE 
                    WHEN referrer IS NULL OR referrer = '' THEN 'Direct'
                    WHEN referrer LIKE '%google%' THEN 'Google'
                    WHEN referrer LIKE '%roblox%' THEN 'Roblox'
                    WHEN referrer LIKE '%discord%' THEN 'Discord'
                    WHEN referrer LIKE '%github%' THEN 'GitHub'
                    WHEN referrer LIKE '%youtube%' THEN 'YouTube'
                    ELSE 'Other'
                END as source,
                COUNT(*) as visits
            FROM page_views 
            WHERE created_at >= :start_date
            GROUP BY source
            ORDER BY visits DESC
        ", [':start_date' => $startDate]);
        
        // Bounce rate (single page visits)
        $bounceData = $db->rawSingle("
            SELECT 
                COUNT(single_page_sessions.session_id) as bounce_sessions,
                COUNT(DISTINCT pv.session_id) as total_sessions
            FROM page_views pv
            LEFT JOIN (
                SELECT session_id
                FROM page_views
                WHERE created_at >= :start_date
                GROUP BY session_id
                HAVING COUNT(*) = 1
            ) single_page_sessions ON pv.session_id = single_page_sessions.session_id
            WHERE pv.created_at >= :start_date
        ", [':start_date' => $startDate]);
        
        $totalSessions = (int)($bounceData['total_sessions'] ?? 1);
        $bounceSessions = (int)($bounceData['bounce_sessions'] ?? 0);
        $analytics['bounce_rate'] = $totalSessions > 0 ? round(($bounceSessions / $totalSessions) * 100, 2) : 0;
        
        // Average session duration (if we track it)
        $analytics['avg_session_duration'] = $db->rawSingle("
            SELECT AVG(session_duration) as avg_duration
            FROM (
                SELECT 
                    session_id,
                    TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at)) as session_duration
                FROM page_views 
                WHERE created_at >= :start_date
                GROUP BY session_id
                HAVING COUNT(*) > 1
            ) session_durations
        ", [':start_date' => $startDate]);
        
        $analytics['avg_session_duration'] = round($analytics['avg_session_duration']['avg_duration'] ?? 0, 2);
        
        return ApiResponse::success($analytics, 'Traffic analytics retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get traffic analytics error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve traffic analytics');
    }
}

function getGeneralAnalytics($data) {
    require_permission('view_analytics');
    
    try {
        $db = db();
        
        $analytics = [
            'overview' => [
                'total_users' => $db->count('users'),
                'active_users' => $db->count('users', ['status' => 'active']),
                'total_projects' => $db->count('projects'),
                'published_projects' => $db->count('projects', ['is_published' => 1]),
                'total_services' => $db->count('services'),
                'active_services' => $db->count('services', ['is_active' => 1]),
                'total_inquiries' => $db->count('contact_inquiries'),
                'pending_inquiries' => $db->count('contact_inquiries', ['status' => 'new'])
            ]
        ];
        
        // Recent activity summary
        $analytics['recent_activity'] = [
            'users_today' => $db->count('users', ['DATE(created_at)' => date('Y-m-d')]),
            'projects_today' => $db->count('projects', ['DATE(created_at)' => date('Y-m-d')]),
            'inquiries_today' => $db->count('contact_inquiries', ['DATE(created_at)' => date('Y-m-d')]),
            'users_this_week' => $db->count('users', ['created_at >=' => date('Y-m-d', strtotime('-7 days'))]),
            'projects_this_week' => $db->count('projects', ['created_at >=' => date('Y-m-d', strtotime('-7 days'))]),
            'inquiries_this_week' => $db->count('contact_inquiries', ['created_at >=' => date('Y-m-d', strtotime('-7 days'))])
        ];
        
        // Performance metrics
        $performanceStats = $db->rawSingle("
            SELECT 
                SUM(p.view_count) as total_project_views,
                SUM(p.like_count) as total_project_likes,
                SUM(p.download_count) as total_project_downloads,
                AVG(p.view_count) as avg_project_views
            FROM projects p 
            WHERE p.is_published = 1
        ");
        
        $analytics['performance'] = [
            'total_project_views' => (int)($performanceStats['total_project_views'] ?? 0),
            'total_project_likes' => (int)($performanceStats['total_project_likes'] ?? 0),
            'total_project_downloads' => (int)($performanceStats['total_project_downloads'] ?? 0),
            'avg_project_views' => round($performanceStats['avg_project_views'] ?? 0, 2)
        ];
        
        // System health
        $analytics['system'] = [
            'database_size_mb' => $db->getStats()['total_size_mb'],
            'cache_enabled' => CACHE_ENABLED === 'true',
            'debug_mode' => DEBUG_MODE,
            'environment' => ENVIRONMENT
        ];
        
        return ApiResponse::success($analytics, 'General analytics retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get general analytics error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve general analytics');
    }
}

// Track page view
function trackPageView($pageType, $pageId = null, $userId = null) {
    try {
        $db = db();
        
        $viewData = [
            'page_type' => $pageType,
            'page_id' => $pageId,
            'user_id' => $userId,
            'session_id' => session_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'referrer' => $_SERVER['HTTP_REFERER'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $db->insert('page_views', $viewData);
        
    } catch (Exception $e) {
        error_log("Track page view error: " . $e->getMessage());
        return false;
    }
}