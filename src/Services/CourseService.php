<?php
namespace App\Services;

use App\Models\Course;
use App\Models\CourseInstance;
use App\Core\Application;
use App\Utils\Logger;

/**
 * Course Management Service
 */
class CourseService
{
    private $db;
    private $logger;
    
    public function __construct()
    {
        $app = Application::getInstance();
        $this->db = $app->getDb();
        $this->logger = $app->getLogger();
    }
    
    /**
     * Get all courses
     */
    public function getAllCourses(): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, m.name_bg as major_name
             FROM course c
             LEFT JOIN major m ON c.major_id = m.id
             ORDER BY c.is_elective, c.code"
        );
    }
    
    /**
     * Get course by ID with details
     */
    public function getCourseById(int $id): ?array
    {
        $course = $this->db->fetchOne(
            "SELECT c.*, m.name_bg as major_name
             FROM course c
             LEFT JOIN major m ON c.major_id = m.id
             WHERE c.id = :id",
            ['id' => $id]
        );
        
        if (!$course) {
            return null;
        }
        
        // Get prerequisites
        $course['prerequisites'] = $this->db->fetchAll(
            "SELECT c.*, cp.is_recommended
             FROM course c
             JOIN course_prerequisite cp ON c.id = cp.prereq_id
             WHERE cp.course_id = :course_id",
            ['course_id' => $id]
        );
        
        // Get instances
        $course['instances'] = $this->db->fetchAll(
            "SELECT * FROM course_instance WHERE course_id = :course_id ORDER BY academic_year DESC, semester",
            ['course_id' => $id]
        );
        
        return $course;
    }
    
    /**
     * Create a new course
     */
    public function createCourse(array $data): array
    {
        // Validate required fields
        if (empty($data['code']) || empty($data['name_bg']) || empty($data['outline_bg'])) {
            return ['success' => false, 'message' => 'Липсват задължителни полета'];
        }
        
        // Check if code exists
        if (Course::findByCode($data['code'])) {
            return ['success' => false, 'message' => 'Код на курс вече съществува'];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Validate major_id for mandatory courses
            $majorId = null;
            if (empty($data['is_elective']) || !$data['is_elective']) {
                if (empty($data['major_id'])) {
                    return ['success' => false, 'message' => 'Задължителните курсове изискват специалност'];
                }
                $majorId = $data['major_id'];
            }
            
            $courseId = $this->db->insert('course', [
                'code' => $data['code'],
                'name_bg' => $data['name_bg'],
                'outline_bg' => $data['outline_bg'],
                'credits' => $data['credits'] ?? 5,
                'is_elective' => $data['is_elective'] ?? 0,
                'major_id' => $majorId
            ]);
            
            // Add prerequisites
            if (!empty($data['prerequisites'])) {
                foreach ($data['prerequisites'] as $prereq) {
                    $this->db->insert('course_prerequisite', [
                        'course_id' => $courseId,
                        'prereq_id' => $prereq['id'],
                        'is_recommended' => $prereq['is_recommended'] ?? 0
                    ]);
                }
            }
            
            $this->db->commit();
            
            $this->logger->log(Logger::COURSE_CREATED, [
                'course_id' => $courseId,
                'code' => $data['code']
            ]);
            
            return ['success' => true, 'id' => $courseId];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Грешка при създаване: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update a course
     */
    public function updateCourse(int $id, array $data): array
    {
        $course = Course::find($id);
        
        if (!$course) {
            return ['success' => false, 'message' => 'Курсът не е намерен'];
        }
        
        try {
            $this->db->beginTransaction();
            
            $updateData = [];
            
            if (isset($data['name_bg'])) {
                $updateData['name_bg'] = $data['name_bg'];
            }
            if (isset($data['outline_bg'])) {
                $updateData['outline_bg'] = $data['outline_bg'];
            }
            if (isset($data['credits'])) {
                $updateData['credits'] = $data['credits'];
            }
            if (isset($data['is_elective'])) {
                $updateData['is_elective'] = $data['is_elective'];
                if ($data['is_elective']) {
                    $updateData['major_id'] = null;
                }
            }
            if (isset($data['major_id']) && empty($data['is_elective'])) {
                $updateData['major_id'] = $data['major_id'];
            }
            
            if (!empty($updateData)) {
                $this->db->update('course', $updateData, 'id = :id', ['id' => $id]);
            }
            
            // Update prerequisites if provided
            if (isset($data['prerequisites'])) {
                $this->db->delete('course_prerequisite', 'course_id = :course_id', ['course_id' => $id]);
                
                foreach ($data['prerequisites'] as $prereq) {
                    $this->db->insert('course_prerequisite', [
                        'course_id' => $id,
                        'prereq_id' => $prereq['id'],
                        'is_recommended' => $prereq['is_recommended'] ?? 0
                    ]);
                }
            }
            
            $this->db->commit();
            
            $this->logger->log(Logger::COURSE_UPDATED, ['course_id' => $id]);
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Грешка при обновяване: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete a course
     */
    public function deleteCourse(int $id): array
    {
        $course = Course::find($id);
        
        if (!$course) {
            return ['success' => false, 'message' => 'Курсът не е намерен'];
        }
        
        try {
            $this->db->delete('course', 'id = :id', ['id' => $id]);
            
            $this->logger->log(Logger::COURSE_DELETED, [
                'course_id' => $id,
                'code' => $course->code
            ]);
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Грешка при изтриване: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all course instances
     */
    public function getAllInstances(?int $year = null, ?string $semester = null): array
    {
        $sql = "SELECT ci.*, c.name_bg as course_name, c.code as course_code, c.is_elective,
                       m.name_bg as major_name
                FROM course_instance ci
                JOIN course c ON ci.course_id = c.id
                LEFT JOIN major m ON c.major_id = m.id
                WHERE 1=1";
        
        $params = [];
        
        if ($year !== null) {
            $sql .= " AND ci.academic_year = :year";
            $params['year'] = $year;
        }
        
        if ($semester !== null) {
            $sql .= " AND ci.semester = :semester";
            $params['semester'] = $semester;
        }
        
        $sql .= " ORDER BY ci.academic_year DESC, ci.semester, c.is_elective, c.code";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get course instance by ID with details
     */
    public function getInstanceById(int $id): ?array
    {
        $instance = $this->db->fetchOne(
            "SELECT ci.*, c.name_bg as course_name, c.code as course_code, c.is_elective, c.credits,
                    m.name_bg as major_name
             FROM course_instance ci
             JOIN course c ON ci.course_id = c.id
             LEFT JOIN major m ON c.major_id = m.id
             WHERE ci.id = :id",
            ['id' => $id]
        );
        
        if (!$instance) {
            return null;
        }
        
        // Get lecturers
        $instance['lecturers'] = $this->db->fetchAll(
            "SELECT u.* FROM user u
             JOIN course_lecturer cl ON u.id = cl.user_id
             WHERE cl.course_instance_id = :instance_id",
            ['instance_id' => $id]
        );
        
        // Get assistants
        $instance['assistants'] = $this->db->fetchAll(
            "SELECT u.* FROM user u
             JOIN course_assistant ca ON u.id = ca.user_id
             WHERE ca.course_instance_id = :instance_id",
            ['instance_id' => $id]
        );
        
        // Get test ranges
        $instance['test_ranges'] = $this->db->fetchAll(
            "SELECT * FROM test_range WHERE course_instance_id = :instance_id ORDER BY test_index",
            ['instance_id' => $id]
        );
        
        return $instance;
    }
    
    /**
     * Create a course instance
     */
    public function createInstance(array $data): array
    {
        // Validate required fields
        if (empty($data['course_id']) || empty($data['academic_year']) || empty($data['semester'])) {
            return ['success' => false, 'message' => 'Липсват задължителни полета'];
        }
        
        // Check if instance already exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM course_instance 
             WHERE course_id = :course_id 
               AND academic_year = :year 
               AND semester = :semester",
            [
                'course_id' => $data['course_id'],
                'year' => $data['academic_year'],
                'semester' => $data['semester']
            ]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => 'Паралелка за този курс вече съществува'];
        }
        
        try {
            $this->db->beginTransaction();
            
            $instanceId = $this->db->insert('course_instance', [
                'course_id' => $data['course_id'],
                'academic_year' => $data['academic_year'],
                'semester' => $data['semester'],
                'exercise_count_per_week' => $data['exercise_count_per_week'] ?? 1,
                'test_count' => $data['test_count'] ?? 0,
                'exam_date_count' => $data['exam_date_count'] ?? 1,
                'allow_test_during_lecture' => $data['allow_test_during_lecture'] ?? 0,
                'test_duration_hours' => $data['test_duration_hours'] ?? 2,
                'exam_duration_hours' => $data['exam_duration_hours'] ?? 3,
                'lecture_duration_hours' => $data['lecture_duration_hours'] ?? 2,
                'exercise_duration_hours' => $data['exercise_duration_hours'] ?? 2
            ]);
            
            // Add lecturers
            if (!empty($data['lecturers'])) {
                foreach ($data['lecturers'] as $lecturerId) {
                    $this->db->insert('course_lecturer', [
                        'course_instance_id' => $instanceId,
                        'user_id' => $lecturerId
                    ]);
                }
            }
            
            // Add assistants
            if (!empty($data['assistants'])) {
                foreach ($data['assistants'] as $assistantId) {
                    $this->db->insert('course_assistant', [
                        'course_instance_id' => $instanceId,
                        'user_id' => $assistantId
                    ]);
                }
            }
            
            // Add test ranges
            if (!empty($data['test_ranges'])) {
                foreach ($data['test_ranges'] as $index => $range) {
                    $this->db->insert('test_range', [
                        'course_instance_id' => $instanceId,
                        'test_index' => $index + 1,
                        'start_date' => $range['start_date'],
                        'end_date' => $range['end_date']
                    ]);
                }
            }
            
            $this->db->commit();
            
            $this->logger->log(Logger::COURSE_INSTANCE_CREATED, [
                'instance_id' => $instanceId,
                'course_id' => $data['course_id']
            ]);
            
            return ['success' => true, 'id' => $instanceId];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Грешка при създаване: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update a course instance
     */
    public function updateInstance(int $id, array $data): array
    {
        $instance = CourseInstance::find($id);
        
        if (!$instance) {
            return ['success' => false, 'message' => 'Паралелката не е намерена'];
        }
        
        try {
            $this->db->beginTransaction();
            
            $updateData = [];
            $fields = ['exercise_count_per_week', 'test_count', 'exam_date_count', 
                       'allow_test_during_lecture', 'test_duration_hours', 'exam_duration_hours',
                       'lecture_duration_hours', 'exercise_duration_hours'];
            
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (!empty($updateData)) {
                $this->db->update('course_instance', $updateData, 'id = :id', ['id' => $id]);
            }
            
            // Update lecturers
            if (isset($data['lecturers'])) {
                $this->db->delete('course_lecturer', 'course_instance_id = :id', ['id' => $id]);
                foreach ($data['lecturers'] as $lecturerId) {
                    $this->db->insert('course_lecturer', [
                        'course_instance_id' => $id,
                        'user_id' => $lecturerId
                    ]);
                }
            }
            
            // Update assistants
            if (isset($data['assistants'])) {
                $this->db->delete('course_assistant', 'course_instance_id = :id', ['id' => $id]);
                foreach ($data['assistants'] as $assistantId) {
                    $this->db->insert('course_assistant', [
                        'course_instance_id' => $id,
                        'user_id' => $assistantId
                    ]);
                }
            }
            
            // Update test ranges
            if (isset($data['test_ranges'])) {
                $this->db->delete('test_range', 'course_instance_id = :id', ['id' => $id]);
                foreach ($data['test_ranges'] as $index => $range) {
                    $this->db->insert('test_range', [
                        'course_instance_id' => $id,
                        'test_index' => $index + 1,
                        'start_date' => $range['start_date'],
                        'end_date' => $range['end_date']
                    ]);
                }
            }
            
            $this->db->commit();
            
            $this->logger->log(Logger::COURSE_INSTANCE_UPDATED, ['instance_id' => $id]);
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Грешка при обновяване: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete a course instance
     */
    public function deleteInstance(int $id): array
    {
        $instance = CourseInstance::find($id);
        
        if (!$instance) {
            return ['success' => false, 'message' => 'Паралелката не е намерена'];
        }
        
        try {
            $this->db->delete('course_instance', 'id = :id', ['id' => $id]);
            
            $this->logger->log(Logger::COURSE_INSTANCE_DELETED, [
                'instance_id' => $id,
                'course_id' => $instance->course_id
            ]);
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Грешка при изтриване: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get courses by major
     */
    public function getCoursesByMajor(int $majorId): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, m.name_bg as major_name
             FROM course c
             LEFT JOIN major m ON c.major_id = m.id
             WHERE c.major_id = :major_id OR c.is_elective = 1
             ORDER BY c.is_elective, c.code",
            ['major_id' => $majorId]
        );
    }
    
    /**
     * Get courses by lecturer
     * @param int $lecturerId
     * @param string|null $year Academic year (defaults to current)
     * @param string|null $semester Semester (defaults to current)
     */
    public function getCoursesByLecturer(int $lecturerId, ?string $year = null, ?string $semester = null): array
    {
        $year = $year ?? \App\Models\AcademicSettings::getCurrentYear();
        $semester = $semester ?? \App\Models\AcademicSettings::getCurrentSemester();
        
        return $this->db->fetchAll(
            "SELECT DISTINCT c.*, ci.id as instance_id, ci.academic_year, ci.semester,
                    m.name_bg as major_name
             FROM course c
             JOIN course_instance ci ON c.id = ci.course_id
             JOIN course_lecturer cl ON ci.id = cl.course_instance_id
             LEFT JOIN major m ON c.major_id = m.id
             WHERE cl.user_id = :lecturer_id
               AND ci.academic_year = :year
               AND ci.semester = :semester
             ORDER BY c.code",
            ['lecturer_id' => $lecturerId, 'year' => $year, 'semester' => $semester]
        );
    }
    
    /**
     * Get course prerequisites
     */
    public function getPrerequisites(int $courseId): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, cp.is_recommended
             FROM course c
             JOIN course_prerequisite cp ON c.id = cp.prereq_id
             WHERE cp.course_id = :course_id
             ORDER BY c.code",
            ['course_id' => $courseId]
        );
    }
    
    /**
     * Get course instances (alias for getAllInstances with current period)
     * @param int|null $courseId
     * @param string|null $year Academic year (defaults to current)
     * @param string|null $semester Semester (defaults to current)
     */
    public function getCourseInstances(?int $courseId = null, ?string $year = null, ?string $semester = null): array
    {
        $year = $year ?? \App\Models\AcademicSettings::getCurrentYear();
        $semester = $semester ?? \App\Models\AcademicSettings::getCurrentSemester();
        
        $sql = "SELECT ci.*, c.name_bg as course_name, c.code as course_code, c.is_elective,
                       m.name_bg as major_name
                FROM course_instance ci
                JOIN course c ON ci.course_id = c.id
                LEFT JOIN major m ON c.major_id = m.id
                WHERE ci.academic_year = :year AND ci.semester = :semester";
        
        $params = ['year' => $year, 'semester' => $semester];
        
        if ($courseId !== null) {
            $sql .= " AND ci.course_id = :course_id";
            $params['course_id'] = $courseId;
        }
        
        $sql .= " ORDER BY c.is_elective, c.code";
        
        return $this->db->fetchAll($sql, $params);
    }
}
