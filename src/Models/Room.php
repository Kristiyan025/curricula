<?php
namespace App\Models;

/**
 * Room Model (Аудитория)
 */
class Room extends Model
{
    protected static string $table = 'room';
    
    /**
     * Find room by number
     */
    public static function findByNumber(string $number): ?self
    {
        return self::findWhere('number', $number);
    }
    
    /**
     * Check if room has white board
     */
    public function hasWhiteBoard(): bool
    {
        return $this->white_boards > 0;
    }
    
    /**
     * Check if room has black board
     */
    public function hasBlackBoard(): bool
    {
        return $this->black_boards > 0;
    }
    
    /**
     * Get total board count
     */
    public function getTotalBoards(): int
    {
        return $this->white_boards + $this->black_boards;
    }
    
    /**
     * Get rooms by floor
     */
    public static function getByFloor(int $floor): array
    {
        return self::where('floor', $floor);
    }
    
    /**
     * Check if room is available at given time slot
     */
    public function isAvailableAt(string $dayOfWeek, string $startTime, string $endTime, ?int $excludeSlotId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM weekly_slot 
                WHERE room_id = :room_id 
                  AND day_of_week = :day 
                  AND variant_id IS NULL
                  AND ((start_time < :end_time AND end_time > :start_time))";
        
        $params = [
            'room_id' => $this->id,
            'day' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime
        ];
        
        if ($excludeSlotId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeSlotId;
        }
        
        $count = (int) $this->db->fetchColumn($sql, $params);
        
        return $count === 0;
    }
    
    /**
     * Check if room is available for test at given datetime
     */
    public function isAvailableForTest(string $date, string $startTime, string $endTime, ?int $excludeTestId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM test_schedule 
                WHERE room_id = :room_id 
                  AND date = :date 
                  AND variant_id IS NULL
                  AND ((start_time < :end_time AND end_time > :start_time))";
        
        $params = [
            'room_id' => $this->id,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime
        ];
        
        if ($excludeTestId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeTestId;
        }
        
        $count = (int) $this->db->fetchColumn($sql, $params);
        
        return $count === 0;
    }
    
    /**
     * Check if room is available for exam at given datetime
     */
    public function isAvailableForExam(string $date, string $startTime, string $endTime, ?int $excludeExamId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM exam_schedule 
                WHERE room_id = :room_id 
                  AND date = :date 
                  AND variant_id IS NULL
                  AND ((start_time < :end_time AND end_time > :start_time))";
        
        $params = [
            'room_id' => $this->id,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime
        ];
        
        if ($excludeExamId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeExamId;
        }
        
        $count = (int) $this->db->fetchColumn($sql, $params);
        
        return $count === 0;
    }
    
    /**
     * Get weekly schedule for this room
     */
    public function getWeeklySchedule(): array
    {
        return $this->db->fetchAll(
            "SELECT ws.*, c.name_bg as course_name, c.code as course_code,
                    g.name_bg as group_name, u.full_name as assistant_name
             FROM weekly_slot ws
             JOIN course_instance ci ON ws.course_instance_id = ci.id
             JOIN course c ON ci.course_id = c.id
             LEFT JOIN student_group g ON ws.group_id = g.id
             LEFT JOIN user u ON ws.assistant_id = u.id
             WHERE ws.room_id = :room_id AND ws.variant_id IS NULL
             ORDER BY FIELD(ws.day_of_week, 'MON', 'TUE', 'WED', 'THU', 'FRI'), ws.start_time",
            ['room_id' => $this->id]
        );
    }
}
