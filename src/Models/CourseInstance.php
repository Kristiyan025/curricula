<?php
namespace App\Models;

/**
 * CourseInstance Model (Паралелка)
 */
class CourseInstance extends Model
{
    protected static string $table = 'course_instance';
    
    /**
     * Get the course
     */
    public function getCourse(): ?Course
    {
        return Course::find($this->course_id);
    }
    
    /**
     * Check if instance is for elective course
     */
    public function isElective(): bool
    {
        $course = $this->getCourse();
        return $course ? $course->isElective() : false;
    }
    
    /**
     * Get lecturers for this instance
     */
    public function getLecturers(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT u.* FROM user u
             JOIN course_lecturer cl ON u.id = cl.user_id
             WHERE cl.course_instance_id = :instance_id",
            ['instance_id' => $this->id]
        );
        
        return array_map(fn($row) => new User($row), $rows);
    }
    
    /**
     * Get assistants for this instance
     */
    public function getAssistants(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT u.* FROM user u
             JOIN course_assistant ca ON u.id = ca.user_id
             WHERE ca.course_instance_id = :instance_id",
            ['instance_id' => $this->id]
        );
        
        return array_map(fn($row) => new User($row), $rows);
    }
    
    /**
     * Add a lecturer
     */
    public function addLecturer(int $userId): void
    {
        $this->db->insert('course_lecturer', [
            'course_instance_id' => $this->id,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Remove a lecturer
     */
    public function removeLecturer(int $userId): void
    {
        $this->db->delete('course_lecturer',
            'course_instance_id = :instance_id AND user_id = :user_id',
            ['instance_id' => $this->id, 'user_id' => $userId]
        );
    }
    
    /**
     * Add an assistant
     */
    public function addAssistant(int $userId): void
    {
        $this->db->insert('course_assistant', [
            'course_instance_id' => $this->id,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Remove an assistant
     */
    public function removeAssistant(int $userId): void
    {
        $this->db->delete('course_assistant',
            'course_instance_id = :instance_id AND user_id = :user_id',
            ['instance_id' => $this->id, 'user_id' => $userId]
        );
    }
    
    /**
     * Get test ranges
     */
    public function getTestRanges(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM test_range 
             WHERE course_instance_id = :instance_id 
             ORDER BY test_index",
            ['instance_id' => $this->id]
        );
    }
    
    /**
     * Set test range
     */
    public function setTestRange(int $testIndex, string $startDate, string $endDate): void
    {
        $existing = $this->db->fetchOne(
            "SELECT id FROM test_range 
             WHERE course_instance_id = :instance_id AND test_index = :index",
            ['instance_id' => $this->id, 'index' => $testIndex]
        );
        
        if ($existing) {
            $this->db->update('test_range', 
                ['start_date' => $startDate, 'end_date' => $endDate],
                'id = :id',
                ['id' => $existing['id']]
            );
        } else {
            $this->db->insert('test_range', [
                'course_instance_id' => $this->id,
                'test_index' => $testIndex,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
        }
    }
    
    /**
     * Get weekly schedule slots
     */
    public function getWeeklySlots(?int $variantId = null): array
    {
        $sql = "SELECT ws.*, r.number as room_number, g.name_bg as group_name, u.full_name as assistant_name
                FROM weekly_slot ws
                JOIN room r ON ws.room_id = r.id
                LEFT JOIN student_group g ON ws.group_id = g.id
                LEFT JOIN user u ON ws.assistant_id = u.id
                WHERE ws.course_instance_id = :instance_id";
        
        $params = ['instance_id' => $this->id];
        
        if ($variantId === null) {
            $sql .= " AND ws.variant_id IS NULL";
        } else {
            $sql .= " AND ws.variant_id = :variant_id";
            $params['variant_id'] = $variantId;
        }
        
        $sql .= " ORDER BY FIELD(ws.day_of_week, 'MON', 'TUE', 'WED', 'THU', 'FRI'), ws.start_time";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get test schedule
     */
    public function getTestSchedule(?int $variantId = null): array
    {
        $sql = "SELECT ts.*, r.number as room_number
                FROM test_schedule ts
                JOIN room r ON ts.room_id = r.id
                WHERE ts.course_instance_id = :instance_id";
        
        $params = ['instance_id' => $this->id];
        
        if ($variantId === null) {
            $sql .= " AND ts.variant_id IS NULL";
        } else {
            $sql .= " AND ts.variant_id = :variant_id";
            $params['variant_id'] = $variantId;
        }
        
        $sql .= " ORDER BY ts.date, ts.start_time";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get exam schedule
     */
    public function getExamSchedule(?int $variantId = null): array
    {
        $sql = "SELECT es.*, r.number as room_number
                FROM exam_schedule es
                JOIN room r ON es.room_id = r.id
                WHERE es.course_instance_id = :instance_id";
        
        $params = ['instance_id' => $this->id];
        
        if ($variantId === null) {
            $sql .= " AND es.variant_id IS NULL";
        } else {
            $sql .= " AND es.variant_id = :variant_id";
            $params['variant_id'] = $variantId;
        }
        
        $sql .= " ORDER BY es.date, es.start_time";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get enrolled students
     */
    public function getEnrolledStudents(): array
    {
        return $this->db->fetchAll(
            "SELECT u.*, s.year, s.major_id, s.group_id, g.name_bg as group_name, m.name_bg as major_name
             FROM user u
             JOIN enrollment e ON u.id = e.student_id
             JOIN student s ON u.id = s.user_id
             JOIN student_group g ON s.group_id = g.id
             JOIN major m ON s.major_id = m.id
             WHERE e.course_instance_id = :instance_id
             ORDER BY m.name_bg, g.name_bg, u.full_name",
            ['instance_id' => $this->id]
        );
    }
    
    /**
     * Get enrollment count
     */
    public function getEnrollmentCount(): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM enrollment WHERE course_instance_id = :instance_id",
            ['instance_id' => $this->id]
        );
    }
    
    /**
     * Enroll a student
     */
    public function enrollStudent(int $studentId): void
    {
        $existing = $this->db->fetchOne(
            "SELECT id FROM enrollment 
             WHERE student_id = :student_id AND course_instance_id = :instance_id",
            ['student_id' => $studentId, 'instance_id' => $this->id]
        );
        
        if (!$existing) {
            $this->db->insert('enrollment', [
                'student_id' => $studentId,
                'course_instance_id' => $this->id
            ]);
        }
    }
    
    /**
     * Unenroll a student
     */
    public function unenrollStudent(int $studentId): void
    {
        $this->db->delete('enrollment',
            'student_id = :student_id AND course_instance_id = :instance_id',
            ['student_id' => $studentId, 'instance_id' => $this->id]
        );
    }
    
    /**
     * Get instances for a semester
     */
    public static function getBySemester(int $year, string $semester): array
    {
        $db = \App\Core\Application::getInstance()->getDb();
        
        $rows = $db->fetchAll(
            "SELECT ci.*, c.name_bg as course_name, c.code as course_code, c.is_elective
             FROM course_instance ci
             JOIN course c ON ci.course_id = c.id
             WHERE ci.academic_year = :year AND ci.semester = :semester
             ORDER BY c.is_elective, c.code",
            ['year' => $year, 'semester' => $semester]
        );
        
        return array_map(fn($row) => new self($row), $rows);
    }
    
    /**
     * Get mandatory instances for a semester and major
     */
    public static function getMandatoryBySemesterAndMajor(int $year, string $semester, int $majorId): array
    {
        $db = \App\Core\Application::getInstance()->getDb();
        
        $rows = $db->fetchAll(
            "SELECT ci.*, c.name_bg as course_name, c.code as course_code
             FROM course_instance ci
             JOIN course c ON ci.course_id = c.id
             WHERE ci.academic_year = :year 
               AND ci.semester = :semester
               AND c.is_elective = 0
               AND c.major_id = :major_id
             ORDER BY c.code",
            ['year' => $year, 'semester' => $semester, 'major_id' => $majorId]
        );
        
        return array_map(fn($row) => new self($row), $rows);
    }
    
    /**
     * Get elective instances for a semester
     */
    public static function getElectivesBySemester(int $year, string $semester): array
    {
        $db = \App\Core\Application::getInstance()->getDb();
        
        $rows = $db->fetchAll(
            "SELECT ci.*, c.name_bg as course_name, c.code as course_code
             FROM course_instance ci
             JOIN course c ON ci.course_id = c.id
             WHERE ci.academic_year = :year 
               AND ci.semester = :semester
               AND c.is_elective = 1
             ORDER BY c.code",
            ['year' => $year, 'semester' => $semester]
        );
        
        return array_map(fn($row) => new self($row), $rows);
    }
}
