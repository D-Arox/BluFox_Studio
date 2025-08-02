<?php
function getServices($data) {
    try {
        $db = db();
        
        $page = max(1, (int)($data['page'] ?? 1));
        $perPage = min(50, max(1, (int)($data['per_page'] ?? 10)));
        $category = $data['category'] ?? null;
        $featured = isset($data['featured']) ? (bool)$data['featured'] : null;
        
        $conditions = ['is_active' => 1];
        
        if ($category) {
            $conditions['category'] = $category;
        }
        
        if ($featured !== null) {
            $conditions['is_featured'] = $featured ? 1 : 0;
        }
        
        $total = $db->count('services', $conditions);
        $totalPages = ceil($total / $perPage);
        
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT s.* FROM services s WHERE " . implode(' AND ', array_map(fn($k) => "$k = :$k", array_keys($conditions))) . 
               " ORDER BY s.sort_order ASC, s.created_at DESC LIMIT $perPage OFFSET $offset";
        
        $db->prepare($sql);
        foreach ($conditions as $key => $value) {
            $db->bind(":$key", $value);
        }
        
        $services = $db->fetchAll();
        
        foreach ($services as &$service) {
            $service['features'] = json_decode($service['features'], true) ?? [];
            $service['included_features'] = json_decode($service['included_features'], true) ?? [];
            $service['add_on_features'] = json_decode($service['add_on_features'], true) ?? [];
            $service['technologies'] = json_decode($service['technologies'], true) ?? [];
            $service['requirements'] = json_decode($service['requirements'], true) ?? [];
            $service['process_steps'] = json_decode($service['process_steps'], true) ?? [];
            $service['portfolio_examples'] = json_decode($service['portfolio_examples'], true) ?? [];
            
            $service['packages_count'] = $db->count('service_packages', ['service_id' => $service['id'], 'is_active' => 1]);
            $service['testimonials_count'] = $db->count('service_testimonials', ['service_id' => $service['id'], 'is_approved' => 1]);
        }
        
        return ApiResponse::paginated($services, [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages
        ], 'Services retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get services error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve services');
    }
}

function getService($serviceId) {
    try {
        $db = db();

        if (is_numeric($serviceId)) {
            $service = $db->select('services', ['id' => $serviceId, 'is_active' => 1]);
        } else {
            $service = $db->select('services', ['slug' => $serviceId, 'is_active' => 1]);
        }
        
        if (empty($service)) {
            return ApiResponse::notFound('Service not found');
        }

        $service = $service[0];

        $service['features'] = json_decode($service['features'], true) ?? [];
        $service['included_features'] = json_decode($service['included_features'], true) ?? [];
        $service['add_on_features'] = json_decode($service['add_on_features'], true) ?? [];
        $service['technologies'] = json_decode($service['technologies'], true) ?? [];
        $service['requirements'] = json_decode($service['requirements'], true) ?? [];
        $service['process_steps'] = json_decode($service['process_steps'], true) ?? [];
        $service['portfolio_examples'] = json_decode($service['portfolio_examples'], true) ?? [];
    
        $service['packages'] = $db->select('service_packages', 
            ['service_id' => $service['id'], 'is_active' => 1], 
            '*', 'sort_order ASC'
        );

        foreach ($service['packages'] as &$package) {
            $package['features'] = json_decode($package['features'], true) ?? [];
            $package['limitations'] = json_decode($package['limitations'], true) ?? [];
        }
        
        $service['testimonials'] = $db->select('service_testimonials', 
            ['service_id' => $service['id'], 'is_approved' => 1], 
            '*', 'sort_order ASC, created_at DESC', '5'
        );

        $service['faqs'] = $db->select('service_faqs', 
            ['service_id' => $service['id']], 
            '*', 'sort_order ASC'
        );

        $db->raw("UPDATE services SET view_count = view_count + 1 WHERE id = :id", [':id' => $service['id']]);
        
        return ApiResponse::success($service, 'Service retrieved successfully');
    } catch (Exception $e) {
        error_log("Get service error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve service');
    }
}

function getServicePackages($serviceId) {
    try {
        $db = db();
        
        $service = $db->select('services', ['id' => $serviceId, 'is_active' => 1]);
        if (empty($service)) {
            return ApiResponse::notFound('Service not found');
        }
        
        $packages = $db->select('service_packages', 
            ['service_id' => $serviceId, 'is_active' => 1], 
            '*', 'sort_order ASC'
        );
        
        foreach ($packages as &$package) {
            $package['features'] = json_decode($package['features'], true) ?? [];
            $package['limitations'] = json_decode($package['limitations'], true) ?? [];
        }
        
        return ApiResponse::success($packages, 'Service packages retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get service packages error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve service packages');
    }
}

function getServiceTestimonials($serviceId) {
    try {
        $db = db();
        
        $service = $db->select('services', ['id' => $serviceId, 'is_active' => 1]);
        if (empty($service)) {
            return ApiResponse::notFound('Service not found');
        }
        
        $testimonials = $db->select('service_testimonials', 
            ['service_id' => $serviceId, 'is_approved' => 1], 
            '*', 'sort_order ASC, created_at DESC'
        );
        
        return ApiResponse::success($testimonials, 'Service testimonials retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get service testimonials error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve service testimonials');
    }
}

function getServiceFAQs($serviceId) {
    try {
        $db = db();
        
        $service = $db->select('services', ['id' => $serviceId, 'is_active' => 1]);
        if (empty($service)) {
            return ApiResponse::notFound('Service not found');
        }
        
        $faqs = $db->select('service_faqs', 
            ['service_id' => $serviceId], 
            '*', 'sort_order ASC'
        );
        
        return ApiResponse::success($faqs, 'Service FAQs retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get service FAQs error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve service FAQs');
    }
}

function createService($data) {
    require_permission('manage_services');
    
    $validator = new ApiValidator($data);
    $validator->required(['name', 'description'])
             ->min('name', 3)
             ->max('name', 255)
             ->min('description', 10);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $db = db();
        
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name'])));
        $originalSlug = $slug;
        $counter = 1;
        
        while ($db->count('services', ['slug' => $slug]) > 0) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        $serviceData = [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'],
            'short_description' => $data['short_description'] ?? substr($data['description'], 0, 200),
            'content' => $data['content'] ?? '',
            'icon' => $data['icon'] ?? null,
            'price_range' => $data['price_range'] ?? null,
            'starting_price' => $data['starting_price'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
            'delivery_time' => $data['delivery_time'] ?? null,
            'features' => isset($data['features']) ? json_encode($data['features']) : null,
            'included_features' => isset($data['included_features']) ? json_encode($data['included_features']) : null,
            'add_on_features' => isset($data['add_on_features']) ? json_encode($data['add_on_features']) : null,
            'technologies' => isset($data['technologies']) ? json_encode($data['technologies']) : null,
            'requirements' => isset($data['requirements']) ? json_encode($data['requirements']) : null,
            'process_steps' => isset($data['process_steps']) ? json_encode($data['process_steps']) : null,
            'portfolio_examples' => isset($data['portfolio_examples']) ? json_encode($data['portfolio_examples']) : null,
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
            'is_featured' => isset($data['is_featured']) ? (bool)$data['is_featured'] : false,
            'is_custom_pricing' => isset($data['is_custom_pricing']) ? (bool)$data['is_custom_pricing'] : false,
            'meta_title' => $data['meta_title'] ?? $data['name'],
            'meta_description' => $data['meta_description'] ?? $data['short_description'],
            'sort_order' => $data['sort_order'] ?? 0,
            'created_by' => current_user()['id']
        ];
        
        $serviceId = $db->insert('services', $serviceData);
        $service = $db->select('services', ['id' => $serviceId])[0];
        
        auth()->logActivity('service_created', ['service_id' => $serviceId, 'name' => $service['name']]);
        
        return ApiResponse::success($service, 'Service created successfully', 201);
        
    } catch (Exception $e) {
        error_log("Create service error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to create service');
    }
}

function updateService($serviceId, $data) {
    require_permission('manage_services');
    
    try {
        $db = db();
        $service = $db->select('services', ['id' => $serviceId]);
        
        if (empty($service)) {
            return ApiResponse::notFound('Service not found');
        }
        
        $service = $service[0];
        
        $validator = new ApiValidator($data);
        
        if (isset($data['name'])) {
            $validator->min('name', 3)->max('name', 255);
        }
        
        if (isset($data['description'])) {
            $validator->min('description', 10);
        }
        
        if ($validator->fails()) {
            return ApiResponse::validationError($validator->getErrors());
        }
        
        $updateData = array_intersect_key($data, array_flip([
            'name', 'description', 'short_description', 'content', 'icon',
            'price_range', 'starting_price', 'currency', 'delivery_time',
            'is_active', 'is_featured', 'is_custom_pricing',
            'meta_title', 'meta_description', 'sort_order'
        ]));
        
        foreach (['features', 'included_features', 'add_on_features', 'technologies', 'requirements', 'process_steps', 'portfolio_examples'] as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = json_encode($data[$field]);
            }
        }
        
        if (isset($data['name']) && $data['name'] !== $service['name']) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name'])));
            $originalSlug = $slug;
            $counter = 1;
            
            while ($db->count('services', ['slug' => $slug, 'id !=' => $serviceId]) > 0) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $updateData['slug'] = $slug;
        }
        
        $updateData['updated_by'] = current_user()['id'];
        
        $db->update('services', $updateData, ['id' => $serviceId]);
        $updatedService = $db->select('services', ['id' => $serviceId])[0];
        
        auth()->logActivity('service_updated', ['service_id' => $serviceId, 'name' => $updatedService['name']]);
        
        return ApiResponse::success($updatedService, 'Service updated successfully');
        
    } catch (Exception $e) {
        error_log("Update service error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to update service');
    }
}

function deleteService($serviceId) {
    require_permission('manage_services');
    
    try {
        $db = db();
        $service = $db->select('services', ['id' => $serviceId]);
        
        if (empty($service)) {
            return ApiResponse::notFound('Service not found');
        }
        
        $service = $service[0];
        
        $db->delete('services', ['id' => $serviceId]);
        
        auth()->logActivity('service_deleted', ['service_id' => $serviceId, 'name' => $service['name']]);
        
        return ApiResponse::success(null, 'Service deleted successfully');
        
    } catch (Exception $e) {
        error_log("Delete service error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to delete service');
    }
}