<?php
namespace App\Utils;

use App\Database\Database;

/**
 * Logger Class - Logs events to database
 */
class Logger
{
    private Database $db;
    
    // Log message constants
    const USER_LOGIN = 'USER_LOGIN';
    const USER_LOGOUT = 'USER_LOGOUT';
    const USER_CREATED = 'USER_CREATED';
    const USER_UPDATED = 'USER_UPDATED';
    const USER_DELETED = 'USER_DELETED';
    
    const COURSE_CREATED = 'COURSE_CREATED';
    const COURSE_UPDATED = 'COURSE_UPDATED';
    const COURSE_DELETED = 'COURSE_DELETED';
    
    const COURSE_INSTANCE_CREATED = 'COURSE_INSTANCE_CREATED';
    const COURSE_INSTANCE_UPDATED = 'COURSE_INSTANCE_UPDATED';
    const COURSE_INSTANCE_DELETED = 'COURSE_INSTANCE_DELETED';
    
    const SCHEDULE_GENERATION_STARTED = 'SCHEDULE_GENERATION_STARTED';
    const SCHEDULE_GENERATION_COMPLETED = 'SCHEDULE_GENERATION_COMPLETED';
    const SCHEDULE_GENERATION_FAILED = 'SCHEDULE_GENERATION_FAILED';
    const SCHEDULE_VARIANT_SELECTED = 'SCHEDULE_VARIANT_SELECTED';
    
    const PHASE_CHANGED = 'PHASE_CHANGED';
    const ERROR = 'ERROR';
    
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    /**
     * Log an event to the database
     * 
     * @param string $messageCode The constant message code
     * @param array $params Optional parameters as key-value pairs
     * @param int|null $userId Optional user ID to associate with the log
     */
    public function log(string $messageCode, array $params = [], ?int $userId = null): void
    {
        try {
            // Include user_id in params if provided
            if ($userId !== null) {
                $params['user_id'] = $userId;
            }
            
            $sql = "INSERT INTO log (timestamp, message_code, parameters) VALUES (NOW(), :code, :params)";
            $this->db->execute($sql, [
                'code' => $messageCode,
                'params' => json_encode($params, JSON_UNESCAPED_UNICODE)
            ]);
        } catch (\Exception $e) {
            // Silently fail logging - don't break the application
            error_log("Failed to log event: " . $e->getMessage());
        }
    }
    
    /**
     * Get log entries with optional filters
     */
    public function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM log WHERE 1=1";
        $params = [];
        
        if (!empty($filters['code'])) {
            $sql .= " AND message_code = :code";
            $params['code'] = $filters['code'];
        }
        
        if (!empty($filters['from_date'])) {
            $sql .= " AND timestamp >= :from_date";
            $params['from_date'] = $filters['from_date'];
        }
        
        if (!empty($filters['to_date'])) {
            $sql .= " AND timestamp <= :to_date";
            $params['to_date'] = $filters['to_date'];
        }
        
        $sql .= " ORDER BY timestamp DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
}
