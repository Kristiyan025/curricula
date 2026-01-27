<?php
namespace App\Models;

/**
 * Major Model (Специалност)
 */
class Major extends Model
{
    protected static string $table = 'major';
    
    /**
     * Get all streams for this major
     */
    public function getStreams(): array
    {
        return MajorStream::where('major_id', $this->id);
    }
    
    /**
     * Get all groups for this major (across all streams)
     */
    public function getGroups(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT g.*, ms.name_bg as stream_name
             FROM student_group g
             JOIN major_stream ms ON g.stream_id = ms.id
             WHERE ms.major_id = :major_id",
            ['major_id' => $this->id]
        );
        
        return $rows;
    }
    
    /**
     * Get all courses for this major (mandatory)
     */
    public function getCourses(): array
    {
        return Course::where('major_id', $this->id);
    }
    
    /**
     * Get student count
     */
    public function getStudentCount(): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM student WHERE major_id = :major_id",
            ['major_id' => $this->id]
        );
    }
    
    /**
     * Find major by abbreviation
     */
    public static function findByAbbreviation(string $abbreviation): ?self
    {
        return self::findWhere('abbreviation', $abbreviation);
    }
}
