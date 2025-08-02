<?php
class Project extends BaseModel {
    protected $table = 'projects';
    protected $fillable = [
        'title', 'slug', 'description', 'short_description', 'content', 'thumbnail_url', 
        'banner_url', 'category', 'difficulty', 'technologies', 'features', 'roblox_game_id',
        'roblox_game_url', 'github_url', 'demo_url', 'download_url', 'price', 'currency',
        'is_featured', 'is_published', 'is_premium', 'completion_percentage', 'estimated_hours',
        'client_name', 'project_date', 'meta_title', 'meta_description', 'meta_keywords',
        'sort_order', 'created_by', 'updated_by'
    ];
    protected $casts = [
        'technologies' => 'json',
        'features' => 'json',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'is_premium' => 'boolean',
        'price' => 'float',
        'project_date' => 'datetime'
    ];

    public function getPublished($limit = null, $featured = null) {
        $conditions = ['is_published' => 1];
        if ($featured !== null) {
            $conditions['is_featured'] = $featured ? 1 : 0;
        }
        return $this->where($conditions, 'sort_order ASC, created_at DESC', $limit);
    }

    public function getFeatured($limit = 6) {
        return $this->where(['is_published' => 1, 'is_featured' => 1], 'sort_order ASC, created_at DESC', $limit);
    }

    public function getByCategory($category, $limit = null) {
        return $this->where(['category' => $category, 'is_published' => 1], 'sort_order ASC, created_at DESC', $limit);
    }

    public function findBySlug($slug) {
        return $this->findBy('slug', $slug);
    }

    public function searchProjects($query, $filters = [], $page = 1, $perPage = 12) {
        $searchFields = ['title', 'description', 'short_description'];
        $sql = "SELECT p.* FROM projects p WHERE p.is_published = 1";
        $params = [];
        
        // Add search query
        if (!empty($query)) {
            $searchConditions = [];
            foreach ($searchFields as $field) {
                $searchConditions[] = "p.$field LIKE :search";
            }
            $sql .= " AND (" . implode(' OR ', $searchConditions) . ")";
            $params[':search'] = "%$query%";
        }
        
        // Add filters
        if (!empty($filters['category'])) {
            $sql .= " AND p.category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['difficulty'])) {
            $sql .= " AND p.difficulty = :difficulty";
            $params[':difficulty'] = $filters['difficulty'];
        }
        
        if (!empty($filters['featured'])) {
            $sql .= " AND p.is_featured = 1";
        }
        
        if (!empty($filters['premium'])) {
            $sql .= " AND p.is_premium = 1";
        }
        
        if (!empty($filters['free'])) {
            $sql .= " AND (p.price IS NULL OR p.price = 0)";
        }
        
        // Add tag filter
        if (!empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : [$filters['tags']];
            $tagPlaceholders = [];
            foreach ($tags as $index => $tag) {
                $tagPlaceholders[] = ":tag_$index";
                $params[":tag_$index"] = $tag;
            }
            $sql .= " AND p.id IN (
                SELECT ptr.project_id FROM project_tag_relations ptr 
                INNER JOIN project_tags pt ON ptr.tag_id = pt.id 
                WHERE pt.slug IN (" . implode(',', $tagPlaceholders) . ")
            )";
        }
        
        // Count total
        $countSql = str_replace('SELECT p.*', 'SELECT COUNT(*)', $sql);
        $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $totalResult = $this->db->fetch();
        $total = (int) $totalResult['COUNT(*)'];
        $totalPages = ceil($total / $perPage);
        
        // Add sorting and pagination
        $orderBy = $filters['sort'] ?? 'created_at';
        $orderDirection = $filters['order'] ?? 'DESC';
        
        switch ($orderBy) {
            case 'title':
                $sql .= " ORDER BY p.title $orderDirection";
                break;
            case 'views':
                $sql .= " ORDER BY p.view_count $orderDirection";
                break;
            case 'likes':
                $sql .= " ORDER BY p.like_count $orderDirection";
                break;
            case 'date':
                $sql .= " ORDER BY p.created_at $orderDirection";
                break;
            default:
                $sql .= " ORDER BY p.sort_order ASC, p.created_at DESC";
        }
        
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT $perPage OFFSET $offset";
        
        // Execute query
        $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $records = $this->db->fetchAll();
        $transformedRecords = array_map([$this, 'transformRecord'], $records);
        
        return [
            'data' => $transformedRecords,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
                'query' => $query,
                'filters' => $filters
            ]
        ];
    }

    public function getRelated($projectId, $limit = 4) {
        $project = $this->find($projectId);
        if (!$project) return [];
        
        $sql = "SELECT p.* FROM projects p 
                WHERE p.id != :project_id 
                AND p.is_published = 1 
                AND (p.category = :category OR p.difficulty = :difficulty)
                ORDER BY 
                    CASE WHEN p.category = :category THEN 1 ELSE 2 END,
                    p.view_count DESC, 
                    p.created_at DESC 
                LIMIT $limit";
        
        $result = $this->raw($sql, [
            ':project_id' => $projectId,
            ':category' => $project['category'],
            ':difficulty' => $project['difficulty']
        ]);
        
        return array_map([$this, 'transformRecord'], $result);
    }

    public function getImages($projectId) {
        return $this->raw("SELECT * FROM project_images WHERE project_id = :project_id ORDER BY sort_order ASC", 
            [':project_id' => $projectId]);
    }

    public function getTags($projectId) {
        $sql = "SELECT pt.* FROM project_tags pt 
                INNER JOIN project_tag_relations ptr ON pt.id = ptr.tag_id 
                WHERE ptr.project_id = :project_id 
                ORDER BY pt.name ASC";
        
        return $this->raw($sql, [':project_id' => $projectId]);
    }

    public function getCollaborators($projectId) {
        $sql = "SELECT pc.*, u.username, u.display_name, u.avatar_url 
                FROM project_collaborators pc 
                LEFT JOIN users u ON pc.user_id = u.id 
                WHERE pc.project_id = :project_id 
                ORDER BY pc.is_lead DESC, pc.sort_order ASC";
        
        return $this->raw($sql, [':project_id' => $projectId]);
    }

    public function getComments($projectId, $approved = true, $limit = null) {
        $sql = "SELECT pc.*, u.username, u.display_name, u.avatar_url 
                FROM project_comments pc 
                LEFT JOIN users u ON pc.user_id = u.id 
                WHERE pc.project_id = :project_id";
        
        if ($approved) {
            $sql .= " AND pc.is_approved = 1";
        }
        
        $sql .= " ORDER BY pc.is_pinned DESC, pc.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        return $this->raw($sql, [':project_id' => $projectId]);
    }

     public function incrementViews($projectId, $userId = null, $ipAddress = null) {
        // Check if this user/IP has viewed recently (within 24 hours)
        $viewExists = false;
        
        if ($userId) {
            $sql = "SELECT id FROM project_views WHERE project_id = :project_id AND user_id = :user_id AND created_at > NOW() - INTERVAL 24 HOUR";
            $result = $this->raw($sql, [':project_id' => $projectId, ':user_id' => $userId]);
            $viewExists = !empty($result);
        } elseif ($ipAddress) {
            $sql = "SELECT id FROM project_views WHERE project_id = :project_id AND ip_address = :ip_address AND created_at > NOW() - INTERVAL 24 HOUR";
            $result = $this->raw($sql, [':project_id' => $projectId, ':ip_address' => $ipAddress]);
            $viewExists = !empty($result);
        }
        
        if (!$viewExists) {
            $this->db->insert('project_views', [
                'project_id' => $projectId,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'referrer' => $_SERVER['HTTP_REFERER'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $sql = "UPDATE projects SET view_count = view_count + 1 WHERE id = :project_id";
            $this->db->prepare($sql);
            $this->db->bind(':project_id', $projectId);
            $this->db->execute();
        }
    }

    public function toggleLike($projectId, $userId, $ipAddress = null) {
        $existingLike = $this->raw("SELECT id FROM project_likes WHERE project_id = :project_id AND user_id = :user_id", 
            [':project_id' => $projectId, ':user_id' => $userId]);
        
        if ($existingLike) {
            $this->db->delete('project_likes', ['project_id' => $projectId, 'user_id' => $userId]);
            $sql = "UPDATE projects SET like_count = like_count - 1 WHERE id = :project_id";
            $liked = false;
        } else {
            $this->db->insert('project_likes', [
                'project_id' => $projectId,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $sql = "UPDATE projects SET like_count = like_count + 1 WHERE id = :project_id";
            $liked = true;
        }
        
        $this->db->prepare($sql);
        $this->db->bind(':project_id', $projectId);
        $this->db->execute();
        
        return $liked;
    }
    
    public function isLikedByUser($projectId, $userId) {
        $result = $this->raw("SELECT id FROM project_likes WHERE project_id = :project_id AND user_id = :user_id", 
            [':project_id' => $projectId, ':user_id' => $userId]);
        return !empty($result);
    }

    public function addComment($projectId, $userId, $content, $parentId = null) {
        return $this->db->insert('project_comments', [
            'project_id' => $projectId,
            'user_id' => $userId,
            'parent_id' => $parentId,
            'content' => $content,
            'is_approved' => has_role('moderator') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function generateSlug($title, $projectId = null) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $conditions = ['slug' => $slug];
            if ($projectId) {
                $sql = "SELECT id FROM projects WHERE slug = :slug AND id != :project_id";
                $result = $this->raw($sql, [':slug' => $slug, ':project_id' => $projectId]);
            } else {
                $result = $this->where($conditions);
            }
            
            if (empty($result)) {
                break;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

     public function getProjectStats() {
        $stats = [];
        
        $stats['total_projects'] = $this->count();
        $stats['published_projects'] = $this->count(['is_published' => 1]);
        $stats['featured_projects'] = $this->count(['is_featured' => 1]);
        $stats['premium_projects'] = $this->count(['is_premium' => 1]);
        
        $sql = "SELECT category, COUNT(*) as count FROM projects WHERE is_published = 1 GROUP BY category";
        $result = $this->raw($sql);
        $stats['projects_by_category'] = [];
        foreach ($result as $row) {
            $stats['projects_by_category'][$row['category']] = $row['count'];
        }
        
        $sql = "SELECT SUM(view_count) as total_views FROM projects WHERE is_published = 1";
        $result = $this->raw($sql);
        $stats['total_views'] = $result[0]['total_views'] ?? 0;
        
        $sql = "SELECT SUM(like_count) as total_likes FROM projects WHERE is_published = 1";
        $result = $this->raw($sql);
        $stats['total_likes'] = $result[0]['total_likes'] ?? 0;
        $stats['most_viewed'] = $this->where(['is_published' => 1], 'view_count DESC', 5);
        $stats['most_liked'] = $this->where(['is_published' => 1], 'like_count DESC', 5);
        
        return $stats;
    }

    public function getTrending($days = 7, $limit = 6) {
        $sql = "SELECT p.*, 
                COUNT(DISTINCT pv.id) as recent_views,
                COUNT(DISTINCT pl.id) as recent_likes,
                (COUNT(DISTINCT pv.id) * 1 + COUNT(DISTINCT pl.id) * 3) as trend_score
                FROM projects p
                LEFT JOIN project_views pv ON p.id = pv.project_id AND pv.created_at > NOW() - INTERVAL $days DAY
                LEFT JOIN project_likes pl ON p.id = pl.project_id AND pl.created_at > NOW() - INTERVAL $days DAY
                WHERE p.is_published = 1
                GROUP BY p.id
                ORDER BY trend_score DESC, p.created_at DESC
                LIMIT $limit";
        
        $result = $this->raw($sql);
        return array_map([$this, 'transformRecord'], $result);
    }

    public function updateMetadata($projectId, $metadata) {
        $allowedFields = ['meta_title', 'meta_description', 'meta_keywords'];
        $updateData = array_intersect_key($metadata, array_flip($allowedFields));
        
        if (empty($updateData)) {
            return false;
        }
        
        return $this->update($projectId, $updateData);
    }

    public function duplicate($projectId, $newTitle = null) {
        $original = $this->find($projectId);
        if (!$original) return false;
        
        unset($original['id'], $original['created_at'], $original['updated_at']);
        
        if ($newTitle) {
            $original['title'] = $newTitle;
        } else {
            $original['title'] = $original['title'] . ' (Copy)';
        }
        $original['slug'] = $this->generateSlug($original['title']);
        
        $original['view_count'] = 0;
        $original['like_count'] = 0;
        $original['download_count'] = 0;
        $original['is_published'] = 0;
        $original['is_featured'] = 0;
        
        return $this->create($original);
    }
}