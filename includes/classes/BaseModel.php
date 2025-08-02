<?php

class BaseModel {    
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $casts = [];
    protected $timestamps = true;
    protected $softDeletes = false;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function find($id)  {
        $result = $this->db->select($this->table, [$this->primaryKey => $id]);
        if (empty($result)) {
            return null;
        }
        return $this->transformRecord($result[0]);
    }

    public function findBy($field, $value) {
        $result = $this->db->select($this->table, [$field => $value]);
        if (empty($result)) {
            return null;
        }
        return $this->transformRecord($result[0]);
    }

    public function all($orderBy = null, $limit = null) {
        $result = $this->db->select($this->table, [], '*', $orderBy, $limit);
        return array_map([$this, 'transformRecord'], $result);
    }

    public function where($conditions, $orderBy = null, $limit = null) {
        $result = $this->db->select($this->table, $conditions, '*', $orderBy, $limit);
        return array_map([$this, 'transformRecord'], $result);
    }

    public function first($conditions = []) {
        $result = $this->db->select($this->table, $conditions, '*', '', '1');
        if (empty($result)) {
            return null;
        }
        return $this->transformRecord($result[0]);
    }

    public function create($data) {
        $filteredData = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $filteredData['created_at'] = date('Y-m-d H:i:s');
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $id = $this->db->insert($this->table, $filteredData);
        return $this->find($id);
    }

    public function update($id, $data) {
        $filteredData = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $this->db->update($this->table, $filteredData, [$this->primaryKey => $id]);
        return $this->find($id);
    }

    public function delete($id) {
        if ($this->softDeletes) {
            return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
        }
        return $this->db->delete($this->table, [$this->primaryKey => $id]);
    }

    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                $whereClause[] = "$key = :$key";
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if ($this->softDeletes) {
            $sql .= empty($conditions) ? " WHERE deleted_at IS NULL" : " AND deleted_at IS NULL";
        }
        
        $this->db->prepare($sql);
        
        foreach ($conditions as $key => $value) {
            $this->db->bind(":$key", $value);
        }
        
        $result = $this->db->fetch();
        return (int) $result['count'];
    }

    public function paginate($page = 1, $perPage = 15, $conditions = [], $orderBy = null) {
        $offset = ($page - 1) * $perPage;
        $total = $this->count($conditions);
        $totalPages = ceil($total / $perPage);
        
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                $whereClause[] = "$key = :$key";
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if ($this->softDeletes) {
            $sql .= empty($conditions) ? " WHERE deleted_at IS NULL" : " AND deleted_at IS NULL";
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        $sql .= " LIMIT $perPage OFFSET $offset";
        
        $this->db->prepare($sql);
        
        foreach ($conditions as $key => $value) {
            $this->db->bind(":$key", $value);
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
                'previous_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < $totalPages ? $page + 1 : null
            ]
        ];
    }

    public function search($query, $fields = [], $page = 1, $perPage = 15) {
        if (empty($fields)) {
            return $this->paginate($page, $perPage);
        }
        
        $searchConditions = [];
        $searchParams = [];
        
        foreach ($fields as $field) {
            $searchConditions[] = "$field LIKE :search_$field";
            $searchParams["search_$field"] = "%$query%";
        }
        
        $offset = ($page - 1) * $perPage;
        
        $countSql = "SELECT COUNT(*) as count FROM {$this->table} WHERE (" . implode(' OR ', $searchConditions) . ")";
        if ($this->softDeletes) {
            $countSql .= " AND deleted_at IS NULL";
        }
        
        $this->db->prepare($countSql);
        foreach ($searchParams as $key => $value) {
            $this->db->bind(":$key", $value);
        }
        $totalResult = $this->db->fetch();
        $total = (int) $totalResult['count'];
        $totalPages = ceil($total / $perPage);
        
        $sql = "SELECT * FROM {$this->table} WHERE (" . implode(' OR ', $searchConditions) . ")";
        if ($this->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= " LIMIT $perPage OFFSET $offset";
        
        $this->db->prepare($sql);
        foreach ($searchParams as $key => $value) {
            $this->db->bind(":$key", $value);
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
                'query' => $query
            ]
        ];
    }

    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function transformRecord($record) {
        if (!$record) {
            return null;
        }
        
        // Apply casts
        foreach ($this->casts as $field => $type) {
            if (isset($record[$field])) {
                switch ($type) {
                    case 'array':
                    case 'json':
                        $record[$field] = json_decode($record[$field], true);
                        break;
                    case 'boolean':
                        $record[$field] = (bool) $record[$field];
                        break;
                    case 'integer':
                        $record[$field] = (int) $record[$field];
                        break;
                    case 'float':
                        $record[$field] = (float) $record[$field];
                        break;
                    case 'datetime':
                        $record[$field] = $record[$field] ? new DateTime($record[$field]) : null;
                        break;
                }
            }
        }
        
        // Hide sensitive fields
        foreach ($this->hidden as $field) {
            unset($record[$field]);
        }
        
        return $record;
    }

    public function bulkInsert($data) {
        if (empty($data)) {
            return false;
        }
        
        $columns = array_keys($data[0]);
        $placeholders = [];
        $values = [];
        
        foreach ($data as $index => $row) {
            $rowPlaceholders = [];
            foreach ($columns as $column) {
                $placeholder = ":row_{$index}_{$column}";
                $rowPlaceholders[] = $placeholder;
                $values[$placeholder] = $row[$column] ?? null;
            }
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES " . implode(', ', $placeholders);
        
        $this->db->prepare($sql);
        foreach ($values as $placeholder => $value) {
            $this->db->bind($placeholder, $value);
        }
        
        return $this->db->execute();
    }

    public function getColumns() {
        $sql = "DESCRIBE {$this->table}";
        $this->db->prepare($sql);
        return $this->db->fetchAll();
    }

    public function truncate() {
        $sql = "TRUNCATE TABLE {$this->table}";
        return $this->db->getConnection()->exec($sql);
    }

    public function raw($sql, $params = []) {
        $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->fetchAll();
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

    public function getLastInsertId() {
        return $this->db->lastInsertId();
    }
    
    public function exists($conditions) {
        return $this->count($conditions) > 0;
    }
    
    public function firstOrCreate($conditions, $data = []) {
        $record = $this->first($conditions);
        if ($record) {
            return $record;
        }
        return $this->create(array_merge($conditions, $data));
    }
    
    public function updateOrCreate($conditions, $data = []) {
        $record = $this->first($conditions);
        if ($record) {
            return $this->update($record[$this->primaryKey], $data);
        }
        return $this->create(array_merge($conditions, $data));
    }
    
    public function restore($id) {
        if (!$this->softDeletes) {
            return false;
        }
        return $this->update($id, ['deleted_at' => null]);
    }
    
    public function forceDelete($id) {
        return $this->db->delete($this->table, [$this->primaryKey => $id]);
    }
    
    public function onlyTrashed() {
        if (!$this->softDeletes) {
            return [];
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NOT NULL";
        $this->db->prepare($sql);
        $records = $this->db->fetchAll();
        return array_map([$this, 'transformRecord'], $records);
    }
}