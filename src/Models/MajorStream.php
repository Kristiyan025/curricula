<?php
namespace App\Models;

/**
 * MajorStream Model (Поток)
 */
class MajorStream extends Model
{
    protected static string $table = 'major_stream';
    
    /**
     * Get the major
     */
    public function getMajor(): ?Major
    {
        return Major::find($this->major_id);
    }
    
    /**
     * Get all groups in this stream
     */
    public function getGroups(): array
    {
        return StudentGroup::where('stream_id', $this->id);
    }
    
    /**
     * Get student count in this stream
     */
    public function getStudentCount(): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM student WHERE stream_id = :stream_id",
            ['stream_id' => $this->id]
        );
    }
    
    /**
     * Get all students in this stream
     */
    public function getStudents(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT u.*, s.year, s.group_id, g.name_bg as group_name
             FROM user u
             JOIN student s ON u.id = s.user_id
             JOIN student_group g ON s.group_id = g.id
             WHERE s.stream_id = :stream_id
             ORDER BY s.year, g.name_bg, u.full_name",
            ['stream_id' => $this->id]
        );
        
        return $rows;
    }
}
