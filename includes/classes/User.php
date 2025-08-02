<?php
class User extends BaseModel {
    protected $table = 'users';
    protected $fillable = [
        'roblox_id', 'username', 'display_name', 'email', 'avatar_url', 
        'bio', 'role', 'status', 'email_verified', 'two_factor_enabled',
        'preferences', 'last_login'
    ];
    protected $hidden = ['two_factor_secret'];
    protected $casts = [
        'preferences' => 'json',
        'email_verified' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'last_login' => 'datetime'
    ];

    public function findByRobloxId($robloxId) {
        return $this->findBy('roblox_id', $robloxId);
    }

    public function findByUsername($username) {
        return $this->findBy('username', $username);
    }

    public function findByEmail($email) {
        return $this->findBy('email', $email);
    }

    public function getActive($limit = null) {
        return $this->where(['status' => 'active'], 'created_at DESC', $limit);
    }

    public function getByRole($role) {
        return $this->where(['role' => $role], 'created_at DESC');
    }

    public function getAdmins() {
        $sql = "SELECT * FROM {$this->table} WHERE role IN ('admin', 'superadmin') AND status = 'active' ORDER BY role DESC, created_at ASC";
        $result = $this->raw($sql);
        return array_map([$this, 'transformRecord'], $result);
    }

    public function updateLastLogin($userId) {
        return $this->update($userId, ['last_login' => date('Y-m-d H:i:s')]);
    }

    public function hasRole($userId, $role) {
        $user = $this->find($userId);
        if (!$user) return false;
        
        $roles = ['user', 'moderator', 'admin', 'superadmin'];
        $userRoleIndex = array_search($user['role'], $roles);
        $requiredRoleIndex = array_search($role, $roles);
        
        return $userRoleIndex !== false && $userRoleIndex >= $requiredRoleIndex;
    }

    public function getProjects($userId, $limit = null) {
        $projectModel = new Project();
        return $projectModel->where(['created_by' => $userId], 'created_at DESC', $limit);
    }

    public function getFavoriteProjects($userId, $limit = null) {
        $sql = "SELECT p.* FROM projects p 
                INNER JOIN project_likes pl ON p.id = pl.project_id 
                WHERE pl.user_id = :user_id AND p.is_published = 1 
                ORDER BY pl.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        $result = $this->raw($sql, [':user_id' => $userId]);
        return array_map(function($record) {
            $projectModel = new Project();
            return $projectModel->transformRecord($record);
        }, $result);
    }

    public function getActivityStats($userId) {
        $stats = [];
        
        // Project stats
        $projectModel = new Project();
        $stats['projects_created'] = $projectModel->count(['created_by' => $userId]);
        $stats['featured_projects'] = $projectModel->count(['created_by' => $userId, 'is_featured' => 1]);
        
        // Like stats
        $sql = "SELECT COUNT(*) as count FROM project_likes WHERE user_id = :user_id";
        $result = $this->raw($sql, [':user_id' => $userId]);
        $stats['projects_liked'] = $result[0]['count'] ?? 0;
        
        // Comment stats
        $sql = "SELECT COUNT(*) as count FROM project_comments WHERE user_id = :user_id";
        $result = $this->raw($sql, [':user_id' => $userId]);
        $stats['comments_made'] = $result[0]['count'] ?? 0;
        
        // View stats (total views on user's projects)
        $sql = "SELECT SUM(p.view_count) as total_views FROM projects p WHERE p.created_by = :user_id";
        $result = $this->raw($sql, [':user_id' => $userId]);
        $stats['total_project_views'] = $result[0]['total_views'] ?? 0;
        
        return $stats;
    }

    public function updatePreferences($userId, $preferences) {
        $user = $this->find($userId);
        if (!$user) return false;
        
        $currentPreferences = $user['preferences'] ?? [];
        $newPreferences = array_merge($currentPreferences, $preferences);
        
        return $this->update($userId, ['preferences' => json_encode($newPreferences)]);
    }

    public function getNotifications($userId, $unreadOnly = false, $limit = 20) {
        $conditions = ['user_id' => $userId];
        if ($unreadOnly) {
            $conditions['is_read'] = 0;
        }
        
        $sql = "SELECT * FROM user_notifications WHERE user_id = :user_id";
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC LIMIT $limit";
        
        return $this->raw($sql, [':user_id' => $userId]);
    }

    public function markNotificationsAsRead($userId, $notificationIds = null) {
        if ($notificationIds) {
            $placeholders = implode(',', array_fill(0, count($notificationIds), '?'));
            $sql = "UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND id IN ($placeholders)";
            $params = array_merge([$userId], $notificationIds);
        } else {
            $sql = "UPDATE user_notifications SET is_read = 1 WHERE user_id = ?";
            $params = [$userId];
        }
        
        $this->db->prepare($sql);
        return $this->db->execute($params);
    }

    public function createNotification($userId, $type, $title, $message, $data = null) {
        $notificationData = [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ? json_encode($data) : null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('user_notifications', $notificationData);
    }

    public function ban($userId, $reason = null) {
        $result = $this->update($userId, ['status' => 'banned']);
        
        if ($result) {
            auth()->logActivity('user_banned', [
                'banned_user_id' => $userId,
                'reason' => $reason
            ]);
            
            $this->createNotification($userId, 'account_status', 'Account Suspended', 
                'Your account has been suspended. Please contact support for more information.');
        }
        
        return $result;
    }

    public function unban($userId) {
        $result = $this->update($userId, ['status' => 'active']);
        
        if ($result) {
            auth()->logActivity('user_unbanned', ['unbanned_user_id' => $userId]);
            
            $this->createNotification($userId, 'account_status', 'Account Restored', 
                'Your account has been restored and is now active.');
        }
        
        return $result;
    }

    public function getApiKeys($userId) {
        return $this->raw("SELECT * FROM api_keys WHERE user_id = :user_id AND is_active = 1 ORDER BY created_at DESC", 
            [':user_id' => $userId]);
    }

    public function searchUsers($query, $page = 1, $perPage = 15) {
        return $this->search($query, ['username', 'display_name', 'email'], $page, $perPage);
    }

     public function getProfile($userId) {
        $user = $this->find($userId);
        if (!$user) return null;
        
        $profile = $this->raw("SELECT * FROM user_profiles WHERE user_id = :user_id", [':user_id' => $userId]);
        $user['profile'] = $profile[0] ?? null;
        $user['stats'] = $this->getActivityStats($userId);
        
        return $user;
    }

    public function updateProfile($userId, $profileData) {
        $existing = $this->raw("SELECT id FROM user_profiles WHERE user_id = :user_id", [':user_id' => $userId]);
        
        $profileData['updated_at'] = date('Y-m-d H:i:s');
        
        if (empty($existing)) {
            $profileData['user_id'] = $userId;
            $profileData['created_at'] = date('Y-m-d H:i:s');
            return $this->db->insert('user_profiles', $profileData);
        } else {
            return $this->db->update('user_profiles', $profileData, ['user_id' => $userId]);
        }
    }

    public function getRecentlyActive($limit = 10) {
        return $this->where(['status' => 'active'], 'last_login DESC', $limit);
    }

    public function getUserStats() {
        $stats = [];
        
        // Total users
        $stats['total_users'] = $this->count();
        
        // Active users
        $stats['active_users'] = $this->count(['status' => 'active']);
        
        // New users this month
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $result = $this->raw($sql);
        $stats['new_users_month'] = $result[0]['count'] ?? 0;
        
        // New users today
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE DATE(created_at) = CURDATE()";
        $result = $this->raw($sql);
        $stats['new_users_today'] = $result[0]['count'] ?? 0;
        
        // Users by role
        $sql = "SELECT role, COUNT(*) as count FROM {$this->table} WHERE status = 'active' GROUP BY role";
        $result = $this->raw($sql);
        $stats['users_by_role'] = [];
        foreach ($result as $row) {
            $stats['users_by_role'][$row['role']] = $row['count'];
        }
        
        return $stats;
    }
}