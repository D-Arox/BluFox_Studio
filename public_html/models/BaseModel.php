<?php
abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $timestamps = true;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function findAll($conditions = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $placeholders = str_repeat('?,', count($value) - 1) . '?';
                    $where[] = "{$field} IN ({$placeholders})";
                    $params = array_merge($params, $value);
                } else {
                    $where[] = "{$field} = ?";
                    $params[] = $value;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function findWhere($conditions) {
        $results = $this->findAll($conditions, null, 1);
        return empty($results) ? null : $results[0];
    }
    
    public function create($data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = array_keys($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";
        
        return $this->db->insert($sql, array_values($data));
    }
    
    public function update($id, $data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = "{$field} = ?";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(',', $fields) . " WHERE {$this->primaryKey} = ?";
        $params = array_merge(array_values($data), [$id]);
        
        return $this->db->update($sql, $params);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->delete($sql, [$id]);
    }
    
    public function softDelete($id) {
        return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }
    
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $field => $value) {
                $where[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result ? (int) $result['count'] : 0;
    }
    
    private function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    public function raw($sql, $params = []) {
        return $this->db->fetchAll($sql, $params);
    }
    
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    public function commit() {
        return $this->db->commit();
    }
    
    public function rollback() {
        return $this->db->rollback();
    }
}

class User extends BaseModel {
    protected $table = 'users';
    protected $fillable = [
        'roblox_id', 'username', 'display_name', 'email', 'email_verified',
        'avatar_url', 'unique_id', 'last_login', 'is_active', 'gdpr_consent',
        'cookie_consent', 'marketing_emails'
    ];
    
    public function findByRobloxId($robloxId) {
        return $this->findWhere(['roblox_id' => $robloxId]);
    }
    
    public function findByUniqueId($uniqueId) {
        return $this->findWhere(['unique_id' => $uniqueId]);
    }
    
    public function updateLastLogin($id) {
        return $this->update($id, ['last_login' => date('Y-m-d H:i:s')]);
    }
    
    public function getApiKeys($userId) {
        return $this->raw(
            "SELECT * FROM api_keys WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC",
            [$userId]
        );
    }
    
    public function getGames($userId) {
        return $this->raw(
            "SELECT * FROM user_games WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }
    
    public function getPurchases($userId) {
        return $this->raw(
            "SELECT p.*, pr.name as product_name, pr.version 
             FROM purchases p 
             JOIN products pr ON p.product_id = pr.id 
             WHERE p.user_id = ? 
             ORDER BY p.purchased_at DESC",
            [$userId]
        );
    }
}

class Product extends BaseModel {
    protected $table = 'products';
    protected $fillable = [
        'name', 'slug', 'description', 'short_description', 'price', 'currency',
        'product_type', 'version', 'download_count', 'is_active', 'featured', 'requires_roblox_verification',
        'file_path', 'documentation_url', 'changelog', 'features', 'requirements',
        'tags', 'seo_title', 'seo_description', 'seo_keywords'
    ];
    
    public function findBySlug($slug) {
        return $this->findWhere(['slug' => $slug, 'is_active' => true]);
    }
    
    public function getFeatured($limit = 6) {
        return $this->findAll(['is_active' => true, 'featured' => true], 'created_at DESC', $limit);
    }
    
    public function incrementDownloadCount($id) {
        return $this->raw(
            "UPDATE {$this->table} SET download_count = download_count + 1 WHERE id = ?",
            [$id]
        );
    }
}

class UserGame extends BaseModel {
    protected $table = 'user_games';
    protected $fillable = [
        'user_id', 'game_id', 'game_name', 'game_icon', 'is_verified',
        'verification_token', 'vault_enabled', 'vault_license_key'
    ];
    
    public function findByGameId($gameId, $userId) {
        return $this->findWhere(['game_id' => $gameId, 'user_id' => $userId]);
    }
    
    public function getVaultEnabledGames($userId) {
        return $this->findAll(['user_id' => $userId, 'vault_enabled' => true]);
    }
    
    public function generateLicenseKey($id) {
        $licenseKey = 'VLT-' . strtoupper(substr(md5(uniqid()), 0, 8) . '-' . substr(md5(time()), 0, 8));
        $this->update($id, ['vault_license_key' => $licenseKey]);
        return $licenseKey;
    }
}

class VaultStats extends BaseModel {
    protected $table = 'vault_stats';
    protected $fillable = [
        'game_id', 'player_count', 'active_vaults', 'total_operations',
        'data_stored_mb', 'performance_ms', 'error_count', 'uptime_seconds',
        'memory_usage_mb', 'raw_data'
    ];
    protected $timestamps = false;
    
    public function getRecentStats($gameId, $hours = 24) {
        return $this->raw(
            "SELECT * FROM {$this->table} 
             WHERE game_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? HOUR) 
             ORDER BY recorded_at DESC",
            [$gameId, $hours]
        );
    }
    
    public function getAggregatedStats($gameId, $days = 7) {
        return $this->raw(
            "SELECT 
                DATE(recorded_at) as date,
                AVG(player_count) as avg_players,
                MAX(player_count) as max_players,
                AVG(active_vaults) as avg_vaults,
                SUM(total_operations) as total_operations,
                AVG(performance_ms) as avg_performance,
                SUM(error_count) as total_errors,
                AVG(uptime_seconds / 300 * 100) as uptime_percentage
             FROM {$this->table} 
             WHERE game_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(recorded_at) 
             ORDER BY date DESC",
            [$gameId, $days]
        );
    }
}

class Purchase extends BaseModel {
    protected $table = 'purchases';
    protected $fillable = [
        'user_id', 'product_id', 'stripe_payment_intent_id', 'stripe_session_id',
        'amount', 'currency', 'status', 'purchased_at', 'expires_at',
        'download_count', 'max_downloads', 'metadata'
    ];
    protected $timestamps = false;
    
    public function findByStripePaymentIntent($paymentIntentId) {
        return $this->findWhere(['stripe_payment_intent_id' => $paymentIntentId]);
    }
    
    public function findUserPurchase($userId, $productId) {
        return $this->findWhere([
            'user_id' => $userId, 
            'product_id' => $productId, 
            'status' => 'completed'
        ]);
    }
    
    public function canDownload($purchaseId) {
        $purchase = $this->find($purchaseId);
        if (!$purchase || $purchase['status'] !== 'completed') {
            return false;
        }
        
        if ($purchase['download_count'] >= $purchase['max_downloads']) {
            return false;
        }
        
        // Check expiration
        if ($purchase['expires_at'] && strtotime($purchase['expires_at']) < time()) {
            return false;
        }
        
        return true;
    }
    
    public function incrementDownloadCount($id) {
        return $this->raw(
            "UPDATE {$this->table} SET download_count = download_count + 1 WHERE id = ?",
            [$id]
        );
    }
}

class Portfolio extends BaseModel {
    protected $table = 'portfolio_items';
    protected $fillable = [
        'title', 'slug', 'description', 'short_description', 'category',
        'roblox_game_id', 'thumbnail_url', 'gallery_images', 'technologies',
        'featured', 'view_count', 'external_links', 'is_published',
        'published_at', 'seo_title', 'seo_description'
    ];
    
    public function findBySlug($slug) {
        return $this->findWhere(['slug' => $slug, 'is_published' => true]);
    }
    
    public function getFeatured($limit = 6) {
        return $this->findAll(
            ['is_published' => true, 'featured' => true], 
            'published_at DESC', 
            $limit
        );
    }
    
    public function getByCategory($category, $limit = null) {
        return $this->findAll(
            ['category' => $category, 'is_published' => true], 
            'published_at DESC', 
            $limit
        );
    }
    
    public function incrementViewCount($id) {
        return $this->raw(
            "UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = ?",
            [$id]
        );
    }
    
    public function getPublished($orderBy = 'published_at DESC', $limit = null) {
        return $this->findAll(['is_published' => true], $orderBy, $limit);
    }
}

class ApiKey extends BaseModel {
    protected $table = 'api_keys';
    protected $fillable = [
        'user_id', 'key_hash', 'key_name', 'permissions', 'is_active',
        'last_used', 'expires_at', 'requests_count', 'rate_limit_reset',
        'rate_limit_requests'
    ];
    
    public function createApiKey($userId, $keyName, $permissions = []) {
        $mainClass = new MainClass();
        $apiKey = $mainClass->generateApiKey();
        $hashedKey = $mainClass->hashApiKey($apiKey);
        
        $id = $this->create([
            'user_id' => $userId,
            'key_hash' => $hashedKey,
            'key_name' => $keyName,
            'permissions' => json_encode($permissions),
            'is_active' => true,
            'rate_limit_reset' => date('Y-m-d H:i:s', time() + API_RATE_LIMIT_WINDOW)
        ]);
        
        return ['id' => $id, 'key' => $apiKey];
    }
    
    public function revokeKey($id, $userId) {
        return $this->raw(
            "UPDATE {$this->table} SET is_active = 0 WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }
    
    public function getUserKeys($userId) {
        return $this->findAll(['user_id' => $userId], 'created_at DESC');
    }
}

class ContactMessage extends BaseModel {
    protected $table = 'contact_messages';
    protected $fillable = [
        'name', 'email', 'subject', 'message', 'ip_address', 'user_agent', 'status'
    ];
    protected $timestamps = false;
    
    public function markAsRead($id) {
        return $this->update($id, ['status' => 'read']);
    }
    
    public function getUnread() {
        return $this->findAll(['status' => 'new'], 'created_at DESC');
    }
}
?>