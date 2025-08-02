<?php
function createInquiry($data) {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $contactLimiter = new RateLimiter("contact_$clientIp", 3, 3600);

    if (!$contactLimiter->attempt()) {
        return ApiResponse::error("Too many contact submissions. Please try again later.", 429);
    }

    $validator = new ApiValidator($data);
    $validator->required(['name', 'email', 'subject', 'message'])
            ->email('email')
            ->min('name', 2)
            ->max('name', 255)
            ->max('email', 255)
            ->min('subject', 5)
            ->max('subject', 500)
            ->min('message', 10)
            ->max('max', 5000);
    
    if (isset($data['type'])) {
        $validator->in('type', ['general', 'service_inquiry', 'support', 'partnership', 'quote_request']);
    }

    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }

    try {
        $db = db();

        $inquiryData = [
            'service_id' => $data['service_id'] ?? null,
            'package_id' => $data['package_id'] ?? null,
            'type' => $data['type'] ?? 'general',
            'name' => sanitize_input($data['name']),
            'email' => strtolower(trim($data['email'])),
            'roblox_username' => $data['roblox_username'] ?? null,
            'company' => $data['company'] ?? null,
            'subject' => sanitize_input($data['subject']),
            'message' => sanitize_input($data['message']),
            'budget_range' => $data['budget_range'] ?? null,
            'timeline' => $data['timeline'] ?? null,
            'additional_info' => isset($data['additional_info']) ? json_encode($data['additional_info']) : null,
            'status' => 'new',
            'priority' => $data['priority'] ?? 'medium',
            'ip_address' => $clientIp,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'source' => $data['source'] ?? 'website',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? null
        ];

        $inquiryId = $db->insert('contact_inquiries', $inquiryData);
        $inquiry = $db->select('contact_inquiries', ['id' => $inquiryId])[0];

        try {
            $mailer = email();
            $adminEmail = ADMIN_EMAIL;

            $emailData = [
                'inquiry' => $inquiry,
                'subject' => '[BluFox Studio] New Contact Inquiry: ' . $inquiry['subject']
            ];

            $mailer->sendTemplate('contact_notification', $adminEmail, $emailData);
        } catch (Exception $e) {
            error_log("Failed to send contact confirmation email: " . $e->getMessage());
        }

        $userModel = new User();
        $admins = $userModel->getAdmins();

        foreach ($admins as $admin) {
            $userModel->createNotification(
                $admin['id'],
                'contact_inquiry',
                'New Contact Inquiry',
                "New inquiry from {$inquiry['name']}: {$inquiry['subject']}",
                ['inquiry_id' => $inquiryId]
            );
        }

        if (is_logged_in()) {
            auth()->logActivity('contact_inquiry_created', ['inquiry_id' => $inquiryId]);
        }

        return ApiResponse::success([
            'inquiry_id' => $inquiryId,
            'message' => 'Tank you for your inquiry. We will get back to you soon!'
        ], 'Inquiry submitted successfully', 201);
    } catch (Exception $e) {
        error_log("Create inquiry error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to submit inquiry');
    }
}

function getInquiries($data) {
    require_permission('manage_inquiries');
    
    try {
        $db = db();
        
        $page = max(1, (int)($data['page'] ?? 1));
        $perPage = min(50, max(1, (int)($data['per_page'] ?? 20)));
        $status = $data['status'] ?? null;
        $type = $data['type'] ?? null;
        $priority = $data['priority'] ?? null;
        $search = $data['search'] ?? '';
        
        $sql = "SELECT ci.*, s.name as service_name, sp.name as package_name 
                FROM contact_inquiries ci 
                LEFT JOIN services s ON ci.service_id = s.id 
                LEFT JOIN service_packages sp ON ci.package_id = sp.id 
                WHERE 1=1";
        
        $params = [];
        
        if ($status) {
            $sql .= " AND ci.status = :status";
            $params[':status'] = $status;
        }
        
        if ($type) {
            $sql .= " AND ci.type = :type";
            $params[':type'] = $type;
        }
        
        if ($priority) {
            $sql .= " AND ci.priority = :priority";
            $params[':priority'] = $priority;
        }
        
        if ($search) {
            $sql .= " AND (ci.name LIKE :search OR ci.email LIKE :search OR ci.subject LIKE :search OR ci.message LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $countSql = str_replace('SELECT ci.*, s.name as service_name, sp.name as package_name', 'SELECT COUNT(*)', $sql);
        $db->prepare($countSql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $totalResult = $db->fetch();
        $total = (int) $totalResult['COUNT(*)'];
        $totalPages = ceil($total / $perPage);
        
        $sql .= " ORDER BY ci.created_at DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage);
        
        $db->prepare($sql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        
        $inquiries = $db->fetchAll();
        
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
        ], 'Inquiries retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get inquiries error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve inquiries');
    }
}

function getInquiry($inquiryId) {
    require_permission('manage_inquiries');
    
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
        
        $inquiry['additional_info'] = json_decode($inquiry['additional_info'], true) ?? [];
        
        $inquiry['responses'] = $db->select('inquiry_responses', 
            ['inquiry_id' => $inquiryId], 
            '*', 'created_at ASC'
        );
        
        return ApiResponse::success($inquiry, 'Inquiry retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get inquiry error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve inquiry');
    }
}

function updateInquiry($inquiryId, $data) {
    require_permission('manage_inquiries');
    
    try {
        $db = db();
        $inquiry = $db->select('contact_inquiries', ['id' => $inquiryId]);
        
        if (empty($inquiry)) {
            return ApiResponse::notFound('Inquiry not found');
        }
        
        $inquiry = $inquiry[0];
        
        $validator = new ApiValidator($data);
        
        if (isset($data['status'])) {
            $validator->in('status', ['new', 'in_progress', 'responded', 'resolved', 'closed']);
        }
        
        if (isset($data['priority'])) {
            $validator->in('priority', ['low', 'medium', 'high', 'urgent']);
        }
        
        if ($validator->fails()) {
            return ApiResponse::validationError($validator->getErrors());
        }
        
        $updateData = array_intersect_key($data, array_flip([
            'status', 'priority', 'assigned_to'
        ]));
        
        if (!empty($updateData)) {
            $db->update('contact_inquiries', $updateData, ['id' => $inquiryId]);
        }
        
        $updatedInquiry = $db->select('contact_inquiries', ['id' => $inquiryId])[0];
        
        // Log activity
        auth()->logActivity('inquiry_updated', [
            'inquiry_id' => $inquiryId,
            'changes' => $updateData
        ]);
        
        return ApiResponse::success($updatedInquiry, 'Inquiry updated successfully');
        
    } catch (Exception $e) {
        error_log("Update inquiry error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to update inquiry');
    }
}

function respondToInquiry($inquiryId, $data) {
    require_permission('manage_inquiries');
    
    $validator = new ApiValidator($data);
    $validator->required(['message'])
             ->min('message', 10)
             ->max('message', 5000);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $db = db();
        $inquiry = $db->select('contact_inquiries', ['id' => $inquiryId]);
        
        if (empty($inquiry)) {
            return ApiResponse::notFound('Inquiry not found');
        }
        
        $inquiry = $inquiry[0];
        $responseData = [
            'inquiry_id' => $inquiryId,
            'user_id' => current_user()['id'],
            'message' => sanitize_input($data['message']),
            'is_internal' => isset($data['is_internal']) ? (bool)$data['is_internal'] : false,
            'is_email_sent' => false,
            'attachments' => isset($data['attachments']) ? json_encode($data['attachments']) : null
        ];
        
        $responseId = $db->insert('inquiry_responses', $responseData);
        
        $updateData = [
            'status' => 'responded',
            'response_count' => $inquiry['response_count'] + 1,
            'last_response_at' => date('Y-m-d H:i:s')
        ];
        
        $db->update('contact_inquiries', $updateData, ['id' => $inquiryId]);
        
        if (!$responseData['is_internal']) {
            try {
                $mailer = email();
                
                $emailData = [
                    'inquiry' => $inquiry,
                    'response' => $responseData,
                    'user' => current_user(),
                    'subject' => 'Re: ' . $inquiry['subject']
                ];
                
                $mailer->sendTemplate('inquiry_response', $inquiry['email'], $emailData);
                
                $db->update('inquiry_responses', ['is_email_sent' => 1], ['id' => $responseId]);
                
            } catch (Exception $e) {
                error_log("Failed to send inquiry response email: " . $e->getMessage());
            }
        }
        
        $response = $db->select('inquiry_responses', ['id' => $responseId])[0];
        
        auth()->logActivity('inquiry_responded', [
            'inquiry_id' => $inquiryId,
            'response_id' => $responseId,
            'is_internal' => $responseData['is_internal']
        ]);
        
        return ApiResponse::success($response, 'Response added successfully', 201);
        
    } catch (Exception $e) {
        error_log("Respond to inquiry error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to add response');
    }
}

function getInquiryResponses($inquiryId) {
    require_permission('manage_inquiries');
    
    try {
        $db = db();
        
        $inquiry = $db->select('contact_inquiries', ['id' => $inquiryId]);
        if (empty($inquiry)) {
            return ApiResponse::notFound('Inquiry not found');
        }
        
        $sql = "SELECT ir.*, u.username, u.display_name, u.avatar_url 
                FROM inquiry_responses ir 
                LEFT JOIN users u ON ir.user_id = u.id 
                WHERE ir.inquiry_id = :inquiry_id 
                ORDER BY ir.created_at ASC";
        
        $responses = $db->raw($sql, [':inquiry_id' => $inquiryId]);
        
        foreach ($responses as &$response) {
            $response['attachments'] = json_decode($response['attachments'], true) ?? [];
        }
        
        return ApiResponse::success($responses, 'Inquiry responses retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get inquiry responses error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve inquiry responses');
    }
}

function deleteInquiry($inquiryId) {
    require_permission('manage_inquiries');
    
    try {
        $db = db();
        $inquiry = $db->select('contact_inquiries', ['id' => $inquiryId]);
        
        if (empty($inquiry)) {
            return ApiResponse::notFound('Inquiry not found');
        }
        
        $inquiry = $inquiry[0];
        
        $db->delete('contact_inquiries', ['id' => $inquiryId]);
        
        auth()->logActivity('inquiry_deleted', [
            'inquiry_id' => $inquiryId,
            'subject' => $inquiry['subject']
        ]);
        
        return ApiResponse::success(null, 'Inquiry deleted successfully');
        
    } catch (Exception $e) {
        error_log("Delete inquiry error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to delete inquiry');
    }
}

function getInquiryStats() {
    require_permission('manage_inquiries');
    
    try {
        $db = db();
        
        $stats = [];
        
        $stats['total'] = $db->count('contact_inquiries');
        
        $statusCounts = $db->raw("SELECT status, COUNT(*) as count FROM contact_inquiries GROUP BY status");
        $stats['by_status'] = [];
        foreach ($statusCounts as $row) {
            $stats['by_status'][$row['status']] = (int)$row['count'];
        }
        
        $typeCounts = $db->raw("SELECT type, COUNT(*) as count FROM contact_inquiries GROUP BY type");
        $stats['by_type'] = [];
        foreach ($typeCounts as $row) {
            $stats['by_type'][$row['type']] = (int)$row['count'];
        }
        
        $priorityCounts = $db->raw("SELECT priority, COUNT(*) as count FROM contact_inquiries GROUP BY priority");
        $stats['by_priority'] = [];
        foreach ($priorityCounts as $row) {
            $stats['by_priority'][$row['priority']] = (int)$row['count'];
        }
        
        $stats['recent'] = $db->count('contact_inquiries', ['created_at >=' => date('Y-m-d', strtotime('-30 days'))]);
        $stats['today'] = $db->count('contact_inquiries', ['DATE(created_at)' => date('Y-m-d')]);
        
        $responseTimeResult = $db->rawSingle("
            SELECT AVG(TIMESTAMPDIFF(HOUR, ci.created_at, ci.last_response_at)) as avg_response_time 
            FROM contact_inquiries ci 
            WHERE ci.last_response_at IS NOT NULL
        ");
        $stats['avg_response_time_hours'] = round($responseTimeResult['avg_response_time'] ?? 0, 2);
        
        return ApiResponse::success($stats, 'Inquiry statistics retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get inquiry stats error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve inquiry statistics');
    }
}