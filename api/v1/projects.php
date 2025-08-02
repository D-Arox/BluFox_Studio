<?php
function getProjects($data) {
    try {
        $projectModel = new Project();
        
        $page = max(1, (int)($data['page'] ?? 1));
        $perPage = min(50, max(1, (int)($data['per_page'] ?? 12)));
        $search = $data['search'] ?? '';
        
        $filters = [];
        if (!empty($data['category'])) {
            $filters['category'] = $data['category'];
        }
        if (!empty($data['difficulty'])) {
            $filters['difficulty'] = $data['difficulty'];
        }
        if (isset($data['featured']) && $data['featured'] !== '') {
            $filters['featured'] = (bool)$data['featured'];
        }
        if (isset($data['premium']) && $data['premium'] !== '') {
            $filters['premium'] = (bool)$data['premium'];
        }
        if (isset($data['free']) && $data['free'] !== '') {
            $filters['free'] = (bool)$data['free'];
        }
        if (!empty($data['tags'])) {
            $filters['tags'] = is_array($data['tags']) ? $data['tags'] : explode(',', $data['tags']);
        }
        if (!empty($data['sort'])) {
            $filters['sort'] = $data['sort'];
        }
        if (!empty($data['order'])) {
            $filters['order'] = strtoupper($data['order']) === 'ASC' ? 'ASC' : 'DESC';
        }
        
        if (!empty($search)) {
            $result = $projectModel->searchProjects($search, $filters, $page, $perPage);
        } else {
            $conditions = ['is_published' => 1];
            if (isset($filters['category'])) {
                $conditions['category'] = $filters['category'];
            }
            if (isset($filters['difficulty'])) {
                $conditions['difficulty'] = $filters['difficulty'];
            }
            if (isset($filters['featured'])) {
                $conditions['is_featured'] = $filters['featured'] ? 1 : 0;
            }
            if (isset($filters['premium'])) {
                $conditions['is_premium'] = $filters['premium'] ? 1 : 0;
            }
            
            $result = $projectModel->paginate($page, $perPage, $conditions, 'sort_order ASC, created_at DESC');
        }
        
        foreach ($result['data'] as &$project) {
            $project['tags'] = $projectModel->getTags($project['id']);
            $project['image_count'] = count($projectModel->getImages($project['id']));
            
            if (is_logged_in()) {
                $project['is_liked'] = $projectModel->isLikedByUser($project['id'], current_user()['id']);
            } else {
                $project['is_liked'] = false;
            }
        }
        
        return ApiResponse::paginated($result['data'], $result['pagination'], 'Projects retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get projects error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve projects');
    }
}

function getProject($projectId) {
    try {
        $projectModel = new Project();
        
        if (is_numeric($projectId)) {
            $project = $projectModel->find($projectId);
        } else {
            $project = $projectModel->findBySlug($projectId);
        }
        
        if (!$project) {
            return ApiResponse::notFound('Project not found');
        }
        
        if (!$project['is_published'] && !has_role('moderator')) {
            return ApiResponse::notFound('Project not found');
        }
        
        $project['images'] = $projectModel->getImages($project['id']);
        $project['tags'] = $projectModel->getTags($project['id']);
        $project['collaborators'] = $projectModel->getCollaborators($project['id']);
        $project['comments'] = $projectModel->getComments($project['id'], true, 10);
        $project['related'] = $projectModel->getRelated($project['id'], 4);
        
        if (is_logged_in()) {
            $project['is_liked'] = $projectModel->isLikedByUser($project['id'], current_user()['id']);
        } else {
            $project['is_liked'] = false;
        }
        
        return ApiResponse::success($project, 'Project retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get project error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve project');
    }
}

function createProject($data) {
    require_permission('manage_projects');
    
    $validator = new ApiValidator($data);
    $validator->required(['title', 'description'])
             ->min('title', 3)
             ->max('title', 255)
             ->min('description', 10)
             ->max('description', 5000);
    
    if (isset($data['category'])) {
        $validator->in('category', ['game', 'plugin', 'gui', 'script', 'model', 'other']);
    }
    
    if (isset($data['difficulty'])) {
        $validator->in('difficulty', ['easy', 'medium', 'hard', 'expert']);
    }
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $projectModel = new Project();
        
        $slug = $projectModel->generateSlug($data['title']);
        
        $projectData = [
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'],
            'short_description' => $data['short_description'] ?? substr($data['description'], 0, 200),
            'content' => $data['content'] ?? '',
            'category' => $data['category'] ?? 'other',
            'difficulty' => $data['difficulty'] ?? 'medium',
            'technologies' => isset($data['technologies']) ? json_encode($data['technologies']) : null,
            'features' => isset($data['features']) ? json_encode($data['features']) : null,
            'roblox_game_id' => $data['roblox_game_id'] ?? null,
            'roblox_game_url' => $data['roblox_game_url'] ?? null,
            'github_url' => $data['github_url'] ?? null,
            'demo_url' => $data['demo_url'] ?? null,
            'download_url' => $data['download_url'] ?? null,
            'price' => $data['price'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
            'is_featured' => isset($data['is_featured']) ? (bool)$data['is_featured'] : false,
            'is_published' => isset($data['is_published']) ? (bool)$data['is_published'] : false,
            'is_premium' => isset($data['is_premium']) ? (bool)$data['is_premium'] : false,
            'completion_percentage' => $data['completion_percentage'] ?? 0,
            'estimated_hours' => $data['estimated_hours'] ?? null,
            'client_name' => $data['client_name'] ?? null,
            'project_date' => $data['project_date'] ?? null,
            'meta_title' => $data['meta_title'] ?? $data['title'],
            'meta_description' => $data['meta_description'] ?? $data['short_description'],
            'sort_order' => $data['sort_order'] ?? 0,
            'created_by' => current_user()['id']
        ];
        
        $project = $projectModel->create($projectData);
        
        if (!empty($data['tags'])) {
            addProjectTags($project['id'], $data['tags']);
        }
        
        auth()->logActivity('project_created', ['project_id' => $project['id'], 'title' => $project['title']]);
        
        return ApiResponse::success($project, 'Project created successfully', 201);
        
    } catch (Exception $e) {
        error_log("Create project error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to create project');
    }
}

function updateProject($projectId, $data) {
    require_permission('manage_projects');
    
    try {
        $projectModel = new Project();
        $project = $projectModel->find($projectId);
        
        if (!$project) {
            return ApiResponse::notFound('Project not found');
        }
        
        if ($project['created_by'] !== current_user()['id'] && !has_role('admin')) {
            return ApiResponse::forbidden('You can only edit your own projects');
        }
        
        $validator = new ApiValidator($data);
        
        if (isset($data['title'])) {
            $validator->min('title', 3)->max('title', 255);
        }
        
        if (isset($data['description'])) {
            $validator->min('description', 10)->max('description', 5000);
        }
        
        if (isset($data['category'])) {
            $validator->in('category', ['game', 'plugin', 'gui', 'script', 'model', 'other']);
        }
        
        if (isset($data['difficulty'])) {
            $validator->in('difficulty', ['easy', 'medium', 'hard', 'expert']);
        }
        
        if ($validator->fails()) {
            return ApiResponse::validationError($validator->getErrors());
        }
        
        $updateData = array_intersect_key($data, array_flip([
            'title', 'description', 'short_description', 'content', 'category', 'difficulty',
            'roblox_game_id', 'roblox_game_url', 'github_url', 'demo_url', 'download_url',
            'price', 'currency', 'is_featured', 'is_published', 'is_premium',
            'completion_percentage', 'estimated_hours', 'client_name', 'project_date',
            'meta_title', 'meta_description', 'sort_order'
        ]));
        
        if (isset($data['technologies'])) {
            $updateData['technologies'] = json_encode($data['technologies']);
        }
        if (isset($data['features'])) {
            $updateData['features'] = json_encode($data['features']);
        }
        
        if (isset($data['title']) && $data['title'] !== $project['title']) {
            $updateData['slug'] = $projectModel->generateSlug($data['title'], $projectId);
        }
        
        $updateData['updated_by'] = current_user()['id'];
        
        $updatedProject = $projectModel->update($projectId, $updateData);
        
        if (isset($data['tags'])) {
            updateProjectTags($projectId, $data['tags']);
        }
        
        auth()->logActivity('project_updated', ['project_id' => $projectId, 'title' => $updatedProject['title']]);
        
        return ApiResponse::success($updatedProject, 'Project updated successfully');
        
    } catch (Exception $e) {
        error_log("Update project error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to update project');
    }
}

function deleteProject($projectId) {
    require_permission('manage_projects');
    
    try {
        $projectModel = new Project();
        $project = $projectModel->find($projectId);
        
        if (!$project) {
            return ApiResponse::notFound('Project not found');
        }
        
        if ($project['created_by'] !== current_user()['id'] && !has_role('admin')) {
            return ApiResponse::forbidden('You can only delete your own projects');
        }
        
        $projectModel->delete($projectId);
        
        auth()->logActivity('project_deleted', ['project_id' => $projectId, 'title' => $project['title']]);
        
        return ApiResponse::success(null, 'Project deleted successfully');
        
    } catch (Exception $e) {
        error_log("Delete project error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to delete project');
    }
}

function toggleProjectLike($projectId) {
    require_auth();
    
    try {
        $projectModel = new Project();
        $project = $projectModel->find($projectId);
        
        if (!$project || !$project['is_published']) {
            return ApiResponse::notFound('Project not found');
        }
        
        $userId = current_user()['id'];
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $liked = $projectModel->toggleLike($projectId, $userId, $clientIp);
        
        $updatedProject = $projectModel->find($projectId);
        
        return ApiResponse::success([
            'liked' => $liked,
            'like_count' => $updatedProject['like_count']
        ], $liked ? 'Project liked' : 'Project unliked');
        
    } catch (Exception $e) {
        error_log("Toggle project like error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to toggle like');
    }
}

function addProjectComment($projectId, $data) {
    require_auth();
    
    $validator = new ApiValidator($data);
    $validator->required(['content'])
             ->min('content', 5)
             ->max('content', 1000);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $projectModel = new Project();
        $project = $projectModel->find($projectId);
        
        if (!$project || !$project['is_published']) {
            return ApiResponse::notFound('Project not found');
        }
        
        $userId = current_user()['id'];
        $parentId = $data['parent_id'] ?? null;
        
        $commentId = $projectModel->addComment($projectId, $userId, $data['content'], $parentId);
        
        $stmt = db()->raw("SELECT pc.*, u.username, u.display_name, u.avatar_url 
                             FROM project_comments pc 
                             LEFT JOIN users u ON pc.user_id = u.id 
                             WHERE pc.id = :comment_id", 
                             [':comment_id' => $commentId]);

        $comment = $stmt ? $stmt[0] : null;
        
        return ApiResponse::success($comment, 'Comment added successfully', 201);
        
    } catch (Exception $e) {
        error_log("Add project comment error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to add comment');
    }
}

function getProjectComments($projectId, $data) {
    try {
        $projectModel = new Project();
        $project = $projectModel->find($projectId);
        
        if (!$project) {
            return ApiResponse::notFound('Project not found');
        }
        
        $page = max(1, (int)($data['page'] ?? 1));
        $perPage = min(50, max(1, (int)($data['per_page'] ?? 20)));
        $approved = !has_role('moderator'); // Moderators can see all comments
        
        $comments = $projectModel->getComments($projectId, $approved);
        
        $offset = ($page - 1) * $perPage;
        $totalComments = count($comments);
        $totalPages = ceil($totalComments / $perPage);
        $paginatedComments = array_slice($comments, $offset, $perPage);
        
        return ApiResponse::paginated($paginatedComments, [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $totalComments,
            'total_pages' => $totalPages,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages
        ], 'Comments retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get project comments error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve comments');
    }
}

function getProjectImages($projectId) {
    try {
        $projectModel = new Project();
        $project = $projectModel->find($projectId);
        
        if (!$project) {
            return ApiResponse::notFound('Project not found');
        }
        
        $images = $projectModel->getImages($projectId);
        
        return ApiResponse::success($images, 'Project images retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get project images error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve project images');
    }
}

function getRelatedProjects($projectId, $data) {
    try {
        $projectModel = new Project();
        $project = $projectModel->find($projectId);
        
        if (!$project) {
            return ApiResponse::notFound('Project not found');
        }
        
        $limit = min(20, max(1, (int)($data['limit'] ?? 4)));
        $related = $projectModel->getRelated($projectId, $limit);
        
        foreach ($related as &$relatedProject) {
            $relatedProject['tags'] = $projectModel->getTags($relatedProject['id']);
            if (is_logged_in()) {
                $relatedProject['is_liked'] = $projectModel->isLikedByUser($relatedProject['id'], current_user()['id']);
            } else {
                $relatedProject['is_liked'] = false;
            }
        }
        
        return ApiResponse::success($related, 'Related projects retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get related projects error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve related projects');
    }
}

function recordProjectView($projectId) {
    try {
        $projectModel = new Project();
        $project = $projectModel->find($projectId);
        
        if (!$project || !$project['is_published']) {
            return ApiResponse::notFound('Project not found');
        }
        
        $userId = is_logged_in() ? current_user()['id'] : null;
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $projectModel->incrementViews($projectId, $userId, $clientIp);
        
        $updatedProject = $projectModel->find($projectId);
        
        return ApiResponse::success([
            'view_count' => $updatedProject['view_count']
        ], 'View recorded');
        
    } catch (Exception $e) {
        error_log("Record project view error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to record view');
    }
}

function addProjectTags($projectId, $tags) {
    try {
        $db = db();
        
        foreach ($tags as $tagName) {
            $tag = $db->select('project_tags', ['name' => $tagName]);
            
            if (empty($tag)) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tagName)));
                $tagId = $db->insert('project_tags', [
                    'name' => $tagName,
                    'slug' => $slug,
                    'usage_count' => 1
                ]);
            } else {
                $tagId = $tag[0]['id'];
                $db->raw("UPDATE project_tags SET usage_count = usage_count + 1 WHERE id = :id", [':id' => $tagId]);
            }
            
            $db->insert('project_tag_relations', [
                'project_id' => $projectId,
                'tag_id' => $tagId
            ]);
        }
    } catch (Exception $e) {
        error_log("Add project tags error: " . $e->getMessage());
    }
}

function updateProjectTags($projectId, $tags) {
    try {
        $db = db();
        
        $db->delete('project_tag_relations', ['project_id' => $projectId]);
        
        if (!empty($tags)) {
            addProjectTags($projectId, $tags);
        }
    } catch (Exception $e) {
        error_log("Update project tags error: " . $e->getMessage());
    }
}