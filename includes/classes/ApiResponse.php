<?php
class ApiResponse {
    private $data = null;
    private $message = '';
    private $statusCode = 200;
    private $errors = [];
    private $meta = [];
    
    public function __construct($data = null, $message = '', $statusCode = 200) {
        $this->data = $data;
        $this->message = $message;
        $this->statusCode = $statusCode;
    }
    
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        return new self($data, $message, $statusCode);
    }
    
    public static function error($message = 'Error', $statusCode = 400, $errors = []) {
        $response = new self(null, $message, $statusCode);
        $response->errors = $errors;
        return $response;
    }
    
    public static function notFound($message = 'Resource not found') {
        return self::error($message, 404);
    }
    
    public static function unauthorized($message = 'Unauthorized') {
        return self::error($message, 401);
    }
    
    public static function forbidden($message = 'Forbidden') {
        return self::error($message, 403);
    }
    
    public static function validationError($errors, $message = 'Validation failed') {
        return self::error($message, 422, $errors);
    }
    
    public static function serverError($message = 'Internal server error') {
        return self::error($message, 500);
    }
    
    public function setData($data) {
        $this->data = $data;
        return $this;
    }
    
    public function setMessage($message) {
        $this->message = $message;
        return $this;
    }
    
    public function setStatusCode($statusCode) {
        $this->statusCode = $statusCode;
        return $this;
    }
    
    public function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
        return $this;
    }
    
    public function setErrors($errors) {
        $this->errors = $errors;
        return $this;
    }
    
    public function addMeta($key, $value) {
        $this->meta[$key] = $value;
        return $this;
    }
    
    public function setMeta($meta) {
        $this->meta = $meta;
        return $this;
    }
    
    public function addPagination($pagination) {
        $this->meta['pagination'] = $pagination;
        return $this;
    }
    
    public function toArray() {
        $response = [
            'success' => $this->statusCode >= 200 && $this->statusCode < 300,
            'status_code' => $this->statusCode,
            'message' => $this->message,
            'data' => $this->data
        ];
        
        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }
        
        if (!empty($this->meta)) {
            $response['meta'] = $this->meta;
        }
        
        $response['timestamp'] = date('c');
        
        return $response;
    }
    
    public function toJson($options = JSON_PRETTY_PRINT) {
        return json_encode($this->toArray(), $options);
    }
    
    public function send($exit = true) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($this->statusCode);
        
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $allowedOrigins = [SITE_URL, 'http://localhost:3000', 'http://127.0.0.1:3000'];
            if (in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
                header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            }
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        header('Access-Control-Allow-Credentials: true');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            if ($exit) exit;
            return;
        }
        
        echo $this->toJson();
        
        if ($exit) {
            exit;
        }
    }
    
    public static function paginated($data, $pagination, $message = 'Success') {
        $response = self::success($data, $message);
        $response->addPagination($pagination);
        return $response;
    }
    
    public static function withMeta($data, $meta, $message = 'Success') {
        $response = self::success($data, $message);
        $response->setMeta($meta);
        return $response;
    }
}

class ApiValidator {
    private $errors = [];
    private $data = [];
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function required($fields) {
        if (is_string($fields)) {
            $fields = [$fields];
        }
        
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
                $this->addError($field, 'This field is required');
            }
        }
        
        return $this;
    }
    
    public function email($field) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, 'Must be a valid email address');
            }
        }
        return $this;
    }
    
    public function min($field, $length) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->addError($field, "Must be at least $length characters");
        }
        return $this;
    }
    
    public function max($field, $length) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->addError($field, "Must not exceed $length characters");
        }
        return $this;
    }
    
    public function numeric($field) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->addError($field, 'Must be a number');
        }
        return $this;
    }
    
    public function integer($field) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->addError($field, 'Must be an integer');
        }
        return $this;
    }
    
    public function url($field) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
                $this->addError($field, 'Must be a valid URL');
            }
        }
        return $this;
    }
    
    public function in($field, $values) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->addError($field, 'Invalid value selected');
        }
        return $this;
    }
    
    public function unique($field, $model, $except = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $modelInstance = new $model();
            $existing = $modelInstance->findBy($field, $this->data[$field]);
            
            if ($existing && (!$except || $existing['id'] != $except)) {
                $this->addError($field, 'This value is already taken');
            }
        }
        return $this;
    }
    
    public function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
        return $this;
    }
    
    public function passes() {
        return empty($this->errors);
    }
    
    public function fails() {
        return !$this->passes();
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function validated($fields = null) {
        if ($fields === null) {
            return $this->data;
        }
        
        if (is_string($fields)) {
            $fields = [$fields];
        }
        
        return array_intersect_key($this->data, array_flip($fields));
    }
}

class RateLimiter {
    private $key;
    private $limit;
    private $window;
    
    public function __construct($key, $limit = 60, $window = 3600) {
        $this->key = $key;
        $this->limit = $limit;
        $this->window = $window;
    }
    
    public function attempt() {
        $cacheFile = CACHE_PATH . '/rate_limit_' . md5($this->key) . '.json';
        
        $requests = [];
        if (file_exists($cacheFile)) {
            $requests = json_decode(file_get_contents($cacheFile), true) ?? [];
        }
        
        $now = time();
        $windowStart = $now - $this->window;
        
        $requests = array_filter($requests, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        if (count($requests) >= $this->limit) {
            return false;
        }
        
        $requests[] = $now;
        
        file_put_contents($cacheFile, json_encode($requests));
        
        return true;
    }
    
    public function remaining() {
        $cacheFile = CACHE_PATH . '/rate_limit_' . md5($this->key) . '.json';
        
        $requests = [];
        if (file_exists($cacheFile)) {
            $requests = json_decode(file_get_contents($cacheFile), true) ?? [];
        }
        
        $now = time();
        $windowStart = $now - $this->window;
        
        $requests = array_filter($requests, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        return max(0, $this->limit - count($requests));
    }
    
    public function retryAfter() {
        $cacheFile = CACHE_PATH . '/rate_limit_' . md5($this->key) . '.json';
        
        $requests = [];
        if (file_exists($cacheFile)) {
            $requests = json_decode(file_get_contents($cacheFile), true) ?? [];
        }
        
        if (empty($requests)) {
            return 0;
        }
        
        $oldestRequest = min($requests);
        return max(0, $this->window - (time() - $oldestRequest));
    }
}