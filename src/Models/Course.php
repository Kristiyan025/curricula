<?php
namespace App\Models;

/**
 * Course Model (Курс)
 */
class Course extends Model
{
    protected static string $table = 'course';
    
    /**
     * Get the major (for mandatory courses)
     */
    public function getMajor(): ?Major
    {
        if (!$this->major_id) {
            return null;
        }
        return Major::find($this->major_id);
    }
    
    /**
     * Check if course is elective
     */
    public function isElective(): bool
    {
        return (bool) $this->is_elective;
    }
    
    /**
     * Check if course is mandatory
     */
    public function isMandatory(): bool
    {
        return !$this->is_elective;
    }
    
    /**
     * Get prerequisites (required)
     */
    public function getRequiredPrerequisites(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT c.* FROM course c
             JOIN course_prerequisite cp ON c.id = cp.prereq_id
             WHERE cp.course_id = :course_id AND cp.is_recommended = 0",
            ['course_id' => $this->id]
        );
        
        return array_map(fn($row) => new self($row), $rows);
    }
    
    /**
     * Get prerequisites (recommended)
     */
    public function getRecommendedPrerequisites(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT c.* FROM course c
             JOIN course_prerequisite cp ON c.id = cp.prereq_id
             WHERE cp.course_id = :course_id AND cp.is_recommended = 1",
            ['course_id' => $this->id]
        );
        
        return array_map(fn($row) => new self($row), $rows);
    }
    
    /**
     * Get all prerequisites
     */
    public function getAllPrerequisites(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT c.*, cp.is_recommended FROM course c
             JOIN course_prerequisite cp ON c.id = cp.prereq_id
             WHERE cp.course_id = :course_id",
            ['course_id' => $this->id]
        );
        
        return $rows;
    }
    
    /**
     * Add a prerequisite
     */
    public function addPrerequisite(int $prereqId, bool $isRecommended = false): void
    {
        $this->db->insert('course_prerequisite', [
            'course_id' => $this->id,
            'prereq_id' => $prereqId,
            'is_recommended' => $isRecommended ? 1 : 0
        ]);
    }
    
    /**
     * Remove a prerequisite
     */
    public function removePrerequisite(int $prereqId): void
    {
        $this->db->delete('course_prerequisite', 
            'course_id = :course_id AND prereq_id = :prereq_id',
            ['course_id' => $this->id, 'prereq_id' => $prereqId]
        );
    }
    
    /**
     * Get course instances
     */
    public function getInstances(): array
    {
        return CourseInstance::where('course_id', $this->id);
    }
    
    /**
     * Get current semester instance
     */
    public function getCurrentInstance(int $year, string $semester): ?CourseInstance
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM course_instance 
             WHERE course_id = :course_id 
               AND academic_year = :year 
               AND semester = :semester",
            [
                'course_id' => $this->id,
                'year' => $year,
                'semester' => $semester
            ]
        );
        
        return $row ? new CourseInstance($row) : null;
    }
    
    /**
     * Find course by code
     */
    public static function findByCode(string $code): ?self
    {
        return self::findWhere('code', $code);
    }
    
    /**
     * Get all elective courses
     */
    public static function getElectives(): array
    {
        return self::where('is_elective', 1);
    }
    
    /**
     * Get all mandatory courses
     */
    public static function getMandatory(): array
    {
        return self::where('is_elective', 0);
    }
    
    /**
     * Get mandatory courses for a specific major
     */
    public static function getMandatoryForMajor(int $majorId): array
    {
        $db = \App\Core\Application::getInstance()->getDb();
        
        $rows = $db->fetchAll(
            "SELECT * FROM course WHERE is_elective = 0 AND major_id = :major_id",
            ['major_id' => $majorId]
        );
        
        return array_map(fn($row) => new self($row), $rows);
    }
}
