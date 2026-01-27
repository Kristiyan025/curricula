<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\Course;
use App\Models\CourseInstance;
use App\Services\CourseService;

/**
 * API Courses Controller
 * 
 * RESTful API for course data.
 */
class ApiCoursesController extends BaseController
{
    private CourseService $courseService;
    
    public function __construct()
    {
        parent::__construct();
        $this->courseService = new CourseService();
    }
    
    /**
     * List all courses
     * GET /api/courses
     */
    public function index(): void
    {
        $majorId = (int) $this->getQuery('major_id', 0);
        $isElective = $this->getQuery('elective');
        
        if ($majorId) {
            $courses = $this->courseService->getCoursesByMajor($majorId);
        } else {
            $courses = Course::all();
        }
        
        // Filter by elective status if specified
        if ($isElective !== null) {
            $isElective = filter_var($isElective, FILTER_VALIDATE_BOOLEAN);
            $courses = array_filter($courses, fn($c) => (bool)$c->is_elective === $isElective);
        }
        
        $this->jsonSuccess([
            'courses' => array_map(fn($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name_bg' => $c->name_bg,
                'name_en' => $c->name_en,
                'major_id' => $c->major_id,
                'credits' => $c->credits,
                'is_elective' => (bool) $c->is_elective,
            ], array_values($courses))
        ]);
    }
    
    /**
     * Get single course
     * GET /api/courses/{id}
     */
    public function show(int $id): void
    {
        $course = Course::find($id);
        
        if (!$course) {
            $this->jsonError('Course not found', 404);
            return;
        }
        
        $prerequisites = $this->courseService->getPrerequisites($id);
        
        $this->jsonSuccess([
            'course' => [
                'id' => $course->id,
                'code' => $course->code,
                'name_bg' => $course->name_bg,
                'name_en' => $course->name_en,
                'major_id' => $course->major_id,
                'credits' => $course->credits,
                'is_elective' => (bool) $course->is_elective,
                'description' => $course->description,
                'prerequisites' => array_map(fn($p) => [
                    'id' => $p['prerequisite_course_id'],
                    'code' => $p['code'] ?? null,
                    'name_bg' => $p['name_bg'] ?? null,
                ], $prerequisites)
            ]
        ]);
    }
    
    /**
     * Create course (admin only)
     * POST /api/courses
     */
    public function store(): void
    {
        $this->requireRole('ADMIN');
        
        $data = $this->getJsonBody();
        
        // Validate required fields
        $required = ['code', 'name_bg'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->jsonError("Field '$field' is required", 400);
                return;
            }
        }
        
        $courseData = [
            'code' => $data['code'],
            'name_bg' => $data['name_bg'],
            'outline_bg' => $data['description'] ?? $data['outline_bg'] ?? '',
            'major_id' => !empty($data['major_id']) ? (int) $data['major_id'] : null,
            'credits' => (int) ($data['credits'] ?? 5),
            'is_elective' => (bool) ($data['is_elective'] ?? false),
        ];
        
        if (!empty($data['prerequisites'])) {
            $courseData['prerequisites'] = array_map(
                fn($id) => ['id' => (int) $id, 'is_recommended' => 0],
                $data['prerequisites']
            );
        }
        
        $result = $this->courseService->createCourse($courseData);
        
        if ($result['success']) {
            $this->jsonSuccess([
                'course' => [
                    'id' => $result['id'],
                    'code' => $data['code'],
                    'name_bg' => $data['name_bg'],
                ]
            ], 'Course created');
        } else {
            $this->jsonError($result['message'], 400);
        }
    }
    
    /**
     * Update course (admin only)
     * PUT /api/courses/{id}
     */
    public function update(int $id): void
    {
        $this->requireRole('ADMIN');
        
        $course = Course::find($id);
        if (!$course) {
            $this->jsonError('Course not found', 404);
            return;
        }
        
        $data = $this->getJsonBody();
        
        $updateData = [
            'name_bg' => $data['name_bg'] ?? $course->name_bg,
            'outline_bg' => $data['description'] ?? $data['outline_bg'] ?? $course->outline_bg,
            'major_id' => isset($data['major_id']) ? ((int) $data['major_id'] ?: null) : $course->major_id,
            'credits' => (int) ($data['credits'] ?? $course->credits),
            'is_elective' => (bool) ($data['is_elective'] ?? $course->is_elective),
        ];
        
        if (isset($data['prerequisites'])) {
            $updateData['prerequisites'] = array_map(
                fn($prereqId) => ['id' => (int) $prereqId, 'is_recommended' => 0],
                $data['prerequisites']
            );
        }
        
        $result = $this->courseService->updateCourse($id, $updateData);
        
        if ($result['success']) {
            $this->jsonSuccess([], 'Course updated');
        } else {
            $this->jsonError($result['message'], 400);
        }
    }
    
    /**
     * Delete course (admin only)
     * DELETE /api/courses/{id}
     */
    public function destroy(int $id): void
    {
        $this->requireRole('ADMIN');
        
        if ($this->courseService->deleteCourse($id)) {
            $this->jsonSuccess([], 'Course deleted');
        } else {
            $this->jsonError('Failed to delete course', 400);
        }
    }
    
    /**
     * Get course instances for a semester
     * GET /api/courses/{id}/instances
     */
    public function instances(int $courseId): void
    {
        $year = (int) $this->getQuery('year', date('Y'));
        $semester = $this->getQuery('semester');
        
        $instances = $this->courseService->getCourseInstances($courseId, $year, $semester);
        
        $this->jsonSuccess([
            'course_id' => $courseId,
            'instances' => array_map(fn($i) => [
                'id' => $i->id,
                'academic_year' => $i->academic_year,
                'semester' => $i->semester,
                'lecture_duration_hours' => $i->lecture_duration_hours,
                'exercise_duration_hours' => $i->exercise_duration_hours,
                'exercise_count_per_week' => $i->exercise_count_per_week,
            ], $instances)
        ]);
    }
}
