<?php
namespace App\Models;

/**
 * StudentGroup Model (Група)
 */
class StudentGroup extends Model
{
    protected static string $table = 'student_group';
    
    /**
     * Get the stream
     */
    public function getStream(): ?MajorStream
    {
        return MajorStream::find($this->stream_id);
    }
    
    /**
     * Get the major (via stream)
     */
    public function getMajor(): ?Major
    {
        $stream = $this->getStream();
        return $stream ? $stream->getMajor() : null;
    }
    
    /**
     * Get all students in this group
     */
    public function getStudents(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT u.*, s.year, s.major_id
             FROM user u
             JOIN student s ON u.id = s.user_id
             WHERE s.group_id = :group_id
             ORDER BY u.full_name",
            ['group_id' => $this->id]
        );
        
        return $rows;
    }
    
    /**
     * Get student count
     */
    public function getStudentCount(): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM student WHERE group_id = :group_id",
            ['group_id' => $this->id]
        );
    }
    
    /**
     * Get weekly schedule for this group
     */
    public function getWeeklySchedule(): array
    {
        return $this->db->fetchAll(
            "SELECT ws.*, c.name_bg as course_name, c.code as course_code, 
                    r.number as room_number, u.full_name as assistant_name
             FROM weekly_slot ws
             JOIN course_instance ci ON ws.course_instance_id = ci.id
             JOIN course c ON ci.course_id = c.id
             JOIN room r ON ws.room_id = r.id
             LEFT JOIN user u ON ws.assistant_id = u.id
             WHERE (ws.group_id = :group_id OR (ws.slot_type = 'LECTURE' AND ws.group_id IS NULL))
               AND ws.variant_id IS NULL
             ORDER BY FIELD(ws.day_of_week, 'MON', 'TUE', 'WED', 'THU', 'FRI'), ws.start_time",
            ['group_id' => $this->id]
        );
    }
}
