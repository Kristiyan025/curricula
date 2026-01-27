<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseInstance;
use App\Services\ScheduleService;
use App\Services\CourseService;

/**
 * Lecturer Controller
 * 
 * Handles lecturer-specific functionality: viewing schedule, managing course instances.
 */
class LecturerController extends BaseController
{
    private ScheduleService $scheduleService;
    private CourseService $courseService;
    
    public function __construct()
    {
        parent::__construct();
        $this->scheduleService = new ScheduleService();
        $this->courseService = new CourseService();
    }
    
    /**
     * Lecturer dashboard
     */
    public function index(): void
    {
        $this->requireRole('LECTURER', 'ASSISTANT');
        
        $user = $this->session->getUser();
        $period = $this->getCurrentAcademicPeriod();
        
        // Get courses taught by this lecturer
        $courses = $this->courseService->getCoursesByLecturer($user['id'], $period['year'], $period['semester']);
        
        // Get schedule
        $schedule = $this->scheduleService->getLecturerSchedule(
            $user['id'], $period['year'], $period['semester']
        );
        
        $this->render('lecturer/dashboard', [
            'courses' => $courses,
            'schedule' => $schedule,
            'period' => $period,
            'title' => 'Моето разписание'
        ]);
    }
    
    /**
     * View course instance details
     */
    public function courseInstance(int $id): void
    {
        $this->requireRole('LECTURER', 'ASSISTANT');
        
        $instance = CourseInstance::find($id);
        if (!$instance) {
            $this->session->setFlash('error', 'Курсът не е намерен');
            $this->redirect('/lecturer');
            return;
        }
        
        // Check if this lecturer is assigned to this course
        $user = $this->session->getUser();
        $isAssigned = $this->isLecturerAssigned($user['id'], $id);
        
        if (!$isAssigned) {
            $this->session->setFlash('error', 'Нямате достъп до този курс');
            $this->redirect('/lecturer');
            return;
        }
        
        $course = Course::find($instance->course_id);
        
        // Get enrolled students
        $students = $this->db->fetchAll(
            "SELECT u.*, s.faculty_number, sg.number as group_number
             FROM enrollment e
             JOIN student s ON e.student_id = s.user_id
             JOIN user u ON s.user_id = u.id
             JOIN student_group sg ON s.group_id = sg.id
             WHERE e.course_instance_id = :instance_id
             ORDER BY sg.number, u.last_name",
            ['instance_id' => $id]
        );
        
        // Get test ranges
        $testRanges = $this->db->fetchAll(
            "SELECT * FROM test_range WHERE course_instance_id = :id ORDER BY start_date",
            ['id' => $id]
        );
        
        $this->render('lecturer/course-instance', [
            'instance' => $instance,
            'course' => $course,
            'students' => $students,
            'testRanges' => $testRanges,
            'title' => $course->name_bg
        ]);
    }
    
    /**
     * Manage test ranges for a course
     */
    public function manageTestRanges(int $instanceId): void
    {
        $this->requireRole('LECTURER');
        
        $user = $this->session->getUser();
        if (!$this->isLecturerAssigned($user['id'], $instanceId)) {
            $this->session->setFlash('error', 'Нямате достъп');
            $this->redirect('/lecturer');
            return;
        }
        
        $instance = CourseInstance::find($instanceId);
        $course = $instance ? Course::find($instance->course_id) : null;
        
        $testRanges = $this->db->fetchAll(
            "SELECT * FROM test_range WHERE course_instance_id = :id ORDER BY start_date",
            ['id' => $instanceId]
        );
        
        $this->render('lecturer/test-ranges', [
            'instance' => $instance,
            'course' => $course,
            'testRanges' => $testRanges,
            'title' => 'Периоди за контролни'
        ]);
    }
    
    /**
     * Add test range
     */
    public function addTestRange(int $instanceId): void
    {
        $this->requireRole('LECTURER');
        
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Невалиден CSRF токен');
            $this->redirect("/lecturer/course/$instanceId/test-ranges");
            return;
        }
        
        $user = $this->session->getUser();
        if (!$this->isLecturerAssigned($user['id'], $instanceId)) {
            $this->session->setFlash('error', 'Нямате достъп');
            $this->redirect('/lecturer');
            return;
        }
        
        $startDate = $this->getPost('start_date');
        $endDate = $this->getPost('end_date');
        
        if (empty($startDate) || empty($endDate)) {
            $this->session->setFlash('error', 'Въведете начална и крайна дата');
            $this->redirect("/lecturer/course/$instanceId/test-ranges");
            return;
        }
        
        $this->db->insert('test_range', [
            'course_instance_id' => $instanceId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        
        $this->session->setFlash('success', 'Периодът е добавен');
        $this->redirect("/lecturer/course/$instanceId/test-ranges");
    }
    
    /**
     * Delete test range
     */
    public function deleteTestRange(int $instanceId, int $rangeId): void
    {
        $this->requireRole('LECTURER');
        
        $user = $this->session->getUser();
        if (!$this->isLecturerAssigned($user['id'], $instanceId)) {
            $this->session->setFlash('error', 'Нямате достъп');
            $this->redirect('/lecturer');
            return;
        }
        
        $this->db->delete('test_range', 'id = :id', ['id' => $rangeId]);
        
        $this->session->setFlash('success', 'Периодът е изтрит');
        $this->redirect("/lecturer/course/$instanceId/test-ranges");
    }
    
    /**
     * Preferences page
     */
    public function preferences(): void
    {
        $this->requireRole('LECTURER', 'ASSISTANT');
        
        $user = $this->session->getUser();
        
        $preferences = $this->db->fetchAll(
            "SELECT * FROM user_preference WHERE user_id = :user_id ORDER BY priority DESC",
            ['user_id' => $user['id']]
        );
        
        $this->render('lecturer/preferences', [
            'preferences' => $preferences,
            'title' => 'Моите предпочитания'
        ]);
    }
    
    /**
     * Save preference
     */
    public function savePreference(): void
    {
        $this->requireRole('LECTURER', 'ASSISTANT');
        
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Невалиден CSRF токен');
            $this->redirect('/lecturer/preferences');
            return;
        }
        
        $user = $this->session->getUser();
        
        $data = [
            'user_id' => $user['id'],
            'preference_type' => $this->getPost('preference_type'),
            'priority' => (int) $this->getPost('priority', 5),
        ];
        
        // Check if preference exists
        $existing = $this->db->fetch(
            "SELECT id FROM user_preference 
             WHERE user_id = :user_id AND preference_type = :type",
            ['user_id' => $user['id'], 'type' => $data['preference_type']]
        );
        
        if ($existing) {
            $this->db->update('user_preference', 
                ['priority' => $data['priority']], 
                'id = :id',
                ['id' => $existing['id']]
            );
        } else {
            $this->db->insert('user_preference', $data);
        }
        
        $this->session->setFlash('success', 'Предпочитанието е запазено');
        $this->redirect('/lecturer/preferences');
    }
    
    /**
     * Delete preference
     */
    public function deletePreference(int $id): void
    {
        $this->requireRole('LECTURER', 'ASSISTANT');
        
        $user = $this->session->getUser();
        
        // Ensure it's the user's preference
        $this->db->query(
            "DELETE FROM user_preference WHERE id = :id AND user_id = :user_id",
            ['id' => $id, 'user_id' => $user['id']]
        );
        
        $this->session->setFlash('success', 'Предпочитанието е изтрито');
        $this->redirect('/lecturer/preferences');
    }
    
    /**
     * Check if lecturer is assigned to course instance
     */
    private function isLecturerAssigned(int $userId, int $instanceId): bool
    {
        $lecturer = $this->db->fetch(
            "SELECT id FROM course_lecturer 
             WHERE user_id = :user_id AND course_instance_id = :instance_id",
            ['user_id' => $userId, 'instance_id' => $instanceId]
        );
        
        if ($lecturer) {
            return true;
        }
        
        $assistant = $this->db->fetch(
            "SELECT id FROM course_assistant 
             WHERE user_id = :user_id AND course_instance_id = :instance_id",
            ['user_id' => $userId, 'instance_id' => $instanceId]
        );
        
        return (bool) $assistant;
    }
}
