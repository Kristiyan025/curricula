<?php
namespace App\Controllers;

use App\Models\Course;
use App\Models\CourseInstance;
use App\Services\ScheduleService;

/**
 * Student Controller
 * 
 * Handles student-specific functionality: viewing personal schedule, enrolling in electives.
 */
class StudentController extends BaseController
{
    private ScheduleService $scheduleService;
    
    public function __construct()
    {
        parent::__construct();
        $this->scheduleService = new ScheduleService();
    }
    
    /**
     * Student dashboard
     */
    public function index(): void
    {
        $this->requireRole('STUDENT');
        
        $user = $this->session->getUser();
        $period = $this->getCurrentAcademicPeriod();
        
        // Get student info
        $student = $this->db->fetch(
            "SELECT s.*, sg.number as group_number, sg.stream_id, 
                    ms.name as stream_name, ms.major_id,
                    m.name_bg as major_name
             FROM student s
             JOIN student_group sg ON s.group_id = sg.id
             JOIN major_stream ms ON sg.stream_id = ms.id
             JOIN major m ON ms.major_id = m.id
             WHERE s.user_id = :user_id",
            ['user_id' => $user['id']]
        );
        
        if (!$student) {
            $this->session->setFlash('error', 'Студентски профил не е намерен');
            $this->redirect('/');
            return;
        }
        
        // Get group schedule
        $schedule = $this->scheduleService->getGroupSchedule(
            $student['group_id'],
            $period['year'],
            $period['semester']
        );
        
        // Get enrolled electives
        $electives = $this->db->fetchAll(
            "SELECT c.*, ci.id as instance_id
             FROM enrollment e
             JOIN course_instance ci ON e.course_instance_id = ci.id
             JOIN course c ON ci.course_id = c.id
             WHERE e.student_id = :user_id 
             AND ci.academic_year = :year AND ci.semester = :semester
             AND c.is_elective = 1",
            ['user_id' => $user['id'], 'year' => $period['year'], 'semester' => $period['semester']]
        );
        
        // Get upcoming tests
        $tests = $this->scheduleService->getTestSchedule(
            $student['stream_id'],
            $period['year'],
            $period['semester']
        );
        
        // Get upcoming exams
        $exams = $this->scheduleService->getExamSchedule(
            $student['stream_id'],
            $period['year'],
            $period['semester']
        );
        
        $this->render('student/dashboard', [
            'student' => $student,
            'schedule' => $schedule,
            'electives' => $electives,
            'tests' => $tests,
            'exams' => $exams,
            'period' => $period,
            'title' => 'Моето разписание'
        ]);
    }
    
    /**
     * Available electives for enrollment
     */
    public function electives(): void
    {
        $this->requireRole('STUDENT');
        
        $user = $this->session->getUser();
        $period = $this->getCurrentAcademicPeriod();
        
        // Get all elective course instances
        $electives = $this->db->fetchAll(
            "SELECT ci.*, c.code, c.name_bg, c.credits, c.description,
                    (SELECT COUNT(*) FROM enrollment WHERE course_instance_id = ci.id) as enrolled_count,
                    (SELECT id FROM enrollment WHERE course_instance_id = ci.id AND student_id = :user_id) as my_enrollment
             FROM course_instance ci
             JOIN course c ON ci.course_id = c.id
             WHERE c.is_elective = 1
             AND ci.academic_year = :year AND ci.semester = :semester
             ORDER BY c.name_bg",
            ['user_id' => $user['id'], 'year' => $period['year'], 'semester' => $period['semester']]
        );
        
        $this->render('student/electives', [
            'electives' => $electives,
            'period' => $period,
            'title' => 'Избираеми дисциплини'
        ]);
    }
    
    /**
     * Enroll in elective
     */
    public function enroll(int $instanceId): void
    {
        $this->requireRole('STUDENT');
        
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Невалиден CSRF токен');
            $this->redirect('/student/electives');
            return;
        }
        
        $user = $this->session->getUser();
        
        // Check if course instance exists and is elective
        $instance = $this->db->fetch(
            "SELECT ci.*, c.is_elective, c.name_bg
             FROM course_instance ci
             JOIN course c ON ci.course_id = c.id
             WHERE ci.id = :id AND c.is_elective = 1",
            ['id' => $instanceId]
        );
        
        if (!$instance) {
            $this->session->setFlash('error', 'Курсът не е намерен или не е избираем');
            $this->redirect('/student/electives');
            return;
        }
        
        // Check if already enrolled
        $existing = $this->db->fetch(
            "SELECT id FROM enrollment WHERE course_instance_id = :instance_id AND student_id = :user_id",
            ['instance_id' => $instanceId, 'user_id' => $user['id']]
        );
        
        if ($existing) {
            $this->session->setFlash('error', 'Вече сте записани за този курс');
            $this->redirect('/student/electives');
            return;
        }
        
        // Check enrollment limits (if any)
        $enrolledCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM enrollment WHERE course_instance_id = :id",
            ['id' => $instanceId]
        )['count'];
        
        // Assuming max 50 students per elective (could be configurable)
        if ($enrolledCount >= 50) {
            $this->session->setFlash('error', 'Курсът е пълен');
            $this->redirect('/student/electives');
            return;
        }
        
        // Enroll
        $this->db->insert('enrollment', [
            'course_instance_id' => $instanceId,
            'student_id' => $user['id'],
            'enrolled_at' => date('Y-m-d H:i:s'),
        ]);
        
        $this->logger->log('STUDENT_ENROLLED', [
            'student_id' => $user['id'],
            'course_instance_id' => $instanceId,
            'course_name' => $instance['name_bg']
        ], $user['id']);
        
        $this->session->setFlash('success', "Успешно се записахте за {$instance['name_bg']}");
        $this->redirect('/student/electives');
    }
    
    /**
     * Unenroll from elective
     */
    public function unenroll(int $instanceId): void
    {
        $this->requireRole('STUDENT');
        
        $user = $this->session->getUser();
        
        // Check enrollment exists
        $enrollment = $this->db->fetch(
            "SELECT e.id, c.name_bg
             FROM enrollment e
             JOIN course_instance ci ON e.course_instance_id = ci.id
             JOIN course c ON ci.course_id = c.id
             WHERE e.course_instance_id = :instance_id AND e.student_id = :user_id",
            ['instance_id' => $instanceId, 'user_id' => $user['id']]
        );
        
        if (!$enrollment) {
            $this->session->setFlash('error', 'Не сте записани за този курс');
            $this->redirect('/student/electives');
            return;
        }
        
        // Check if unenrollment is still allowed (e.g., within first 2 weeks)
        // For now, allow anytime
        
        $this->db->delete('enrollment', 'id = :id', ['id' => $enrollment['id']]);
        
        $this->logger->log('STUDENT_UNENROLLED', [
            'student_id' => $user['id'],
            'course_instance_id' => $instanceId,
            'course_name' => $enrollment['name_bg']
        ], $user['id']);
        
        $this->session->setFlash('success', "Успешно се отписахте от {$enrollment['name_bg']}");
        $this->redirect('/student/electives');
    }
    
    /**
     * View personal schedule (combined mandatory + electives)
     */
    public function schedule(): void
    {
        $this->requireRole('STUDENT');
        
        $user = $this->session->getUser();
        $period = $this->getCurrentAcademicPeriod();
        $view = $this->getQuery('view', 'table');
        
        // Get student info
        $student = $this->db->fetch(
            "SELECT s.*, sg.number as group_number, sg.stream_id
             FROM student s
             JOIN student_group sg ON s.group_id = sg.id
             WHERE s.user_id = :user_id",
            ['user_id' => $user['id']]
        );
        
        // Get group schedule (mandatory)
        $groupSchedule = $this->scheduleService->getGroupSchedule(
            $student['group_id'],
            $period['year'],
            $period['semester']
        );
        
        // Get elective schedule
        $electiveSchedule = $this->getElectiveSchedule($user['id'], $period);
        
        // Merge schedules
        $schedule = $this->mergeSchedules($groupSchedule, $electiveSchedule);
        
        $this->render('student/schedule', [
            'student' => $student,
            'schedule' => $schedule,
            'period' => $period,
            'view' => $view,
            'title' => 'Моето разписание'
        ]);
    }
    
    /**
     * Get elective course schedule for student
     */
    private function getElectiveSchedule(int $studentId, array $period): array
    {
        return $this->db->fetchAll(
            "SELECT ws.*, c.name_bg as course_name, c.code as course_code, r.number as room_number
             FROM weekly_slot ws
             JOIN course_instance ci ON ws.course_instance_id = ci.id
             JOIN course c ON ci.course_id = c.id
             JOIN room r ON ws.room_id = r.id
             JOIN enrollment e ON e.course_instance_id = ci.id
             WHERE e.student_id = :student_id
             AND ci.academic_year = :year AND ci.semester = :semester
             AND c.is_elective = 1
             AND ws.variant_id IN (
                 SELECT id FROM schedule_variant 
                 WHERE type = 'WEEKLY' AND is_selected = 1
             )
             ORDER BY ws.day_of_week, ws.start_time",
            ['student_id' => $studentId, 'year' => $period['year'], 'semester' => $period['semester']]
        );
    }
    
    /**
     * Merge mandatory and elective schedules
     */
    private function mergeSchedules(array $mandatory, array $electives): array
    {
        // Mark electives
        foreach ($electives as &$slot) {
            $slot['is_elective'] = true;
        }
        
        // Merge
        $merged = array_merge($mandatory, $electives);
        
        // Sort by day and time
        usort($merged, function($a, $b) {
            $days = ['MON' => 1, 'TUE' => 2, 'WED' => 3, 'THU' => 4, 'FRI' => 5, 'SAT' => 6, 'SUN' => 7];
            $dayA = $days[$a['day_of_week']] ?? 0;
            $dayB = $days[$b['day_of_week']] ?? 0;
            
            if ($dayA !== $dayB) {
                return $dayA - $dayB;
            }
            
            return strcmp($a['start_time'], $b['start_time']);
        });
        
        return $merged;
    }
}
