<?php
namespace App\Controllers;

use App\Models\Major;
use App\Models\MajorStream;
use App\Models\StudentGroup;
use App\Models\User;
use App\Models\Room;
use App\Models\Course;
use App\Models\CourseInstance;
use App\Models\AcademicSettings;
use App\Services\UserService;
use App\Services\CourseService;
use App\Services\Scheduling\ScheduleGeneratorService;

/**
 * Admin Controller
 * 
 * Handles administrative functions: manage majors, users, rooms, courses, and schedule generation.
 */
class AdminController extends BaseController
{
    private UserService $userService;
    private CourseService $courseService;
    private ScheduleGeneratorService $scheduleGenerator;
    
    public function __construct()
    {
        parent::__construct();
        $this->userService = new UserService();
        $this->courseService = new CourseService();
        $this->scheduleGenerator = new ScheduleGeneratorService();
    }
    
    /**
     * Admin dashboard
     */
    public function index(): void
    {
        $this->requireRole('ADMIN');
        
        // Get summary statistics
        $stats = [
            'majors' => Major::count(),
            'users' => User::count(),
            'rooms' => Room::count(),
            'courses' => Course::count(),
        ];
        
        $period = $this->getCurrentAcademicPeriod();
        
        $this->render('admin/dashboard', [
            'stats' => $stats,
            'period' => $period,
            'title' => 'Администрация'
        ]);
    }
    
    // ==================== MAJORS ====================
    
    /**
     * List majors
     */
    public function majors(): void
    {
        $this->requireRole('ADMIN');
        
        $majors = Major::all();
        
        // Get streams for each major
        foreach ($majors as &$major) {
            $major->streams = MajorStream::where('major_id', $major->id);
        }
        
        $this->render('admin/majors/index', [
            'majors' => $majors,
            'title' => 'Специалности'
        ]);
    }
    
    /**
     * Show major create form
     */
    public function majorCreate(): void
    {
        $this->requireRole('ADMIN');
        
        $this->render('admin/majors/create', [
            'title' => 'Нова специалност'
        ]);
    }
    
    /**
     * Store new major
     */
    public function majorStore(): void
    {
        $this->requireRole('ADMIN');
        
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Невалиден CSRF токен');
            $this->redirect('/admin/majors/create');
            return;
        }
        
        $data = [
            'code' => trim($this->getPost('code', '')),
            'name_bg' => trim($this->getPost('name_bg', '')),
            'name_en' => trim($this->getPost('name_en', '')),
            'degree' => $this->getPost('degree', 'BACHELOR'),
            'duration_years' => (int) $this->getPost('duration_years', 4),
        ];
        
        // Validate
        if (empty($data['code']) || empty($data['name_bg'])) {
            $this->session->setFlash('error', 'Попълнете задължителните полета');
            $this->redirect('/admin/majors/create');
            return;
        }
        
        try {
            $major = Major::create($data);
            $this->session->setFlash('success', 'Специалността е създадена');
            $this->redirect('/admin/majors');
        } catch (\Exception $e) {
            $this->session->setFlash('error', 'Грешка: ' . $e->getMessage());
            $this->redirect('/admin/majors/create');
        }
    }
    
    /**
     * Edit major form
     */
    public function majorEdit(int $id): void
    {
        $this->requireRole('ADMIN');
        
        $major = Major::find($id);
        if (!$major) {
            $this->session->setFlash('error', 'Специалността не е намерена');
            $this->redirect('/admin/majors');
            return;
        }
        
        $major->streams = MajorStream::where('major_id', $major->id);
        
        $this->render('admin/majors/edit', [
            'major' => $major,
            'title' => 'Редактиране на специалност'
        ]);
    }
    
    /**
     * Update major
     */
    public function majorUpdate(int $id): void
    {
        $this->requireRole('ADMIN');
        
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Невалиден CSRF токен');
            $this->redirect("/admin/majors/$id/edit");
            return;
        }
        
        $major = Major::find($id);
        if (!$major) {
            $this->session->setFlash('error', 'Специалността не е намерена');
            $this->redirect('/admin/majors');
            return;
        }
        
        $major->code = trim($this->getPost('code', ''));
        $major->name_bg = trim($this->getPost('name_bg', ''));
        $major->name_en = trim($this->getPost('name_en', ''));
        $major->degree = $this->getPost('degree', 'BACHELOR');
        $major->duration_years = (int) $this->getPost('duration_years', 4);
        
        if ($major->save()) {
            $this->session->setFlash('success', 'Специалността е обновена');
        } else {
            $this->session->setFlash('error', 'Грешка при запазване');
        }
        
        $this->redirect('/admin/majors');
    }
    
    /**
     * Delete major
     */
    public function majorDelete(int $id): void
    {
        $this->requireRole('ADMIN');
        
        $major = Major::find($id);
        if ($major && $major->delete()) {
            $this->session->setFlash('success', 'Специалността е изтрита');
        } else {
            $this->session->setFlash('error', 'Грешка при изтриване');
        }
        
        $this->redirect('/admin/majors');
    }
    
    // ==================== USERS ====================
    
    /**
     * List users
     */
    public function users(): void
    {
        $this->requireRole('ADMIN');
        
        $role = $this->getQuery('role');
        
        if ($role) {
            $users = $this->userService->getUsersByRole($role);
        } else {
            $users = User::all();
        }
        
        $this->render('admin/users/index', [
            'users' => $users,
            'selectedRole' => $role,
            'title' => 'Потребители'
        ]);
    }
    
    /**
     * Show user create form
     */
    public function userCreate(): void
    {
        $this->requireRole('ADMIN');
        
        $majors = Major::all();
        $streams = MajorStream::all();
        
        $this->render('admin/users/create', [
            'majors' => $majors,
            'streams' => $streams,
            'title' => 'Нов потребител'
        ]);
    }
    
    /**
     * Store new user
     */
    public function userStore(): void
    {
        $this->requireRole('ADMIN');
        
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Невалиден CSRF токен');
            $this->redirect('/admin/users/create');
            return;
        }
        
        $data = [
            'email' => trim($this->getPost('email', '')),
            'password' => $this->getPost('password', ''),
            'first_name' => trim($this->getPost('first_name', '')),
            'last_name' => trim($this->getPost('last_name', '')),
        ];
        
        $roles = $this->getPost('roles', []);
        $data['roles'] = $roles;
        
        // Validate
        if (empty($data['email']) || empty($data['password']) || 
            empty($data['first_name']) || empty($data['last_name'])) {
            $this->session->setFlash('error', 'Попълнете задължителните полета');
            $this->redirect('/admin/users/create');
            return;
        }
        
        try {
            $result = $this->userService->createUser($data);
            
            if (!$result['success']) {
                $this->session->setFlash('error', $result['message']);
                $this->redirect('/admin/users/create');
                return;
            }
            
            // If student, create student record
            if (in_array('STUDENT', $roles)) {
                $studentData = [
                    'faculty_number' => trim($this->getPost('faculty_number', '')),
                    'group_id' => (int) $this->getPost('group_id', 0),
                ];
                
                if (!empty($studentData['faculty_number']) && $studentData['group_id']) {
                    $this->db->insert('student', [
                        'user_id' => $result['id'],
                        'faculty_number' => $studentData['faculty_number'],
                        'group_id' => $studentData['group_id'],
                        'enrollment_year' => (int) date('Y'),
                    ]);
                }
            }
            
            $this->session->setFlash('success', 'Потребителят е създаден');
            $this->redirect('/admin/users');
        } catch (\Exception $e) {
            $this->session->setFlash('error', 'Грешка: ' . $e->getMessage());
            $this->redirect('/admin/users/create');
        }
    }
    
    /**
     * Edit user form
     */
    public function userEdit(int $id): void
    {
        $this->requireRole('ADMIN');
        
        $user = User::find($id);
        if (!$user) {
            $this->session->setFlash('error', 'Потребителят не е намерен');
            $this->redirect('/admin/users');
            return;
        }
        
        $userRoles = $this->userService->getUserRoles($id);
        $majors = Major::all();
        $streams = MajorStream::all();
        
        // Get student info if applicable
        $student = $this->db->fetch(
            "SELECT * FROM student WHERE user_id = :user_id",
            ['user_id' => $id]
        );
        
        // Get lecturer info if applicable
        $lecturer = $this->db->fetch(
            "SELECT * FROM lecturer_data WHERE user_id = :user_id",
            ['user_id' => $id]
        );
        
        $this->render('admin/users/edit', [
            'user' => $user,
            'userRoles' => $userRoles,
            'lecturer' => $lecturer,
            'student' => $student,
            'majors' => $majors,
            'streams' => $streams,
            'title' => 'Редактиране на потребител'
        ]);
    }
    
    /**
     * Update user
     */
    public function userUpdate(int $id): void
    {
        $this->requireRole('ADMIN');
        
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Невалиден CSRF токен');
            $this->redirect("/admin/users/$id/edit");
            return;
        }
        
        try {
            $data = [
                'email' => trim($this->getPost('email', '')),
                'first_name' => trim($this->getPost('first_name', '')),
                'last_name' => trim($this->getPost('last_name', '')),
                'is_active' => (bool) $this->getPost('is_active', false),
            ];
            
            $password = $this->getPost('password', '');
            if (!empty($password)) {
                $data['password'] = $password;
            }
            
            $roles = $this->getPost('roles', []);
            $data['roles'] = $roles;
            
            $result = $this->userService->updateUser($id, $data);
            
            if ($result['success']) {
                $this->session->setFlash('success', 'Потребителят е обновен');
            } else {
                $this->session->setFlash('error', $result['message']);
            }
        } catch (\Exception $e) {
            $this->session->setFlash('error', 'Грешка: ' . $e->getMessage());
        }
        
        $this->redirect('/admin/users');
    }
    
    /**
     * Delete user
     */
    public function userDelete(int $id): void
    {
        $this->requireRole('ADMIN');
        
        if ($this->userService->deleteUser($id)) {
            $this->session->setFlash('success', 'Потребителят е изтрит');
        } else {
            $this->session->setFlash('error', 'Грешка при изтриване');
        }
        
        $this->redirect('/admin/users');
    }
    
    // ==================== ROOMS ====================
    
    /**
     * List rooms
     */
    public function rooms(): void
    {
        $this->requireRole('ADMIN');
        
        $rooms = Room::all();
        
        $this->render('admin/rooms/index', [
            'rooms' => $rooms,
            'title' => 'Зали'
        ]);
    }
    
    /**
     * Create room form
     */
    public function roomCreate(): void
    {
        $this->requireRole('ADMIN');
        
        $this->render('admin/rooms/create', [
            'title' => 'Нова зала'
        ]);
    }
    
    /**
     * Store room
     */
    public function roomStore(): void
    {
        $this->requireRole('ADMIN');
        
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Невалиден CSRF токен');
            $this->redirect('/admin/rooms/create');
            return;
        }
        
        $data = [
            'number' => trim($this->getPost('number', '')),
            'building' => trim($this->getPost('building', '')),
            'floor' => (int) $this->getPost('floor', 0),
            'capacity' => (int) $this->getPost('capacity', 30),
            'room_type' => $this->getPost('room_type', 'LECTURE_HALL'),
            'has_projector' => (bool) $this->getPost('has_projector', false),
            'has_computers' => (bool) $this->getPost('has_computers', false),
            'black_boards' => (int) $this->getPost('black_boards', 0),
            'white_boards' => (int) $this->getPost('white_boards', 0),
        ];
        
        try {
            Room::create($data);
            $this->session->setFlash('success', 'Залата е създадена');
            $this->redirect('/admin/rooms');
        } catch (\Exception $e) {
            $this->session->setFlash('error', 'Грешка: ' . $e->getMessage());
            $this->redirect('/admin/rooms/create');
        }
    }
    
    /**
     * Edit room form
     */
    public function roomEdit(int $id): void
    {
        $this->requireRole('ADMIN');
        
        $room = Room::find($id);
        if (!$room) {
            $this->session->setFlash('error', 'Залата не е намерена');
            $this->redirect('/admin/rooms');
            return;
        }
        
        $this->render('admin/rooms/edit', [
            'room' => $room,
            'title' => 'Редактиране на зала'
        ]);
    }
    
    /**
     * Update room
     */
    public function roomUpdate(int $id): void
    {
        $this->requireRole('ADMIN');
        
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Невалиден CSRF токен');
            $this->redirect("/admin/rooms/$id/edit");
            return;
        }
        
        $room = Room::find($id);
        if (!$room) {
            $this->session->setFlash('error', 'Залата не е намерена');
            $this->redirect('/admin/rooms');
            return;
        }
        
        $room->number = trim($this->getPost('number', ''));
        $room->building = trim($this->getPost('building', ''));
        $room->floor = (int) $this->getPost('floor', 0);
        $room->capacity = (int) $this->getPost('capacity', 30);
        $room->room_type = $this->getPost('room_type', 'LECTURE_HALL');
        $room->has_projector = (bool) $this->getPost('has_projector', false);
        $room->has_computers = (bool) $this->getPost('has_computers', false);
        $room->black_boards = (int) $this->getPost('black_boards', 0);
        $room->white_boards = (int) $this->getPost('white_boards', 0);
        
        if ($room->save()) {
            $this->session->setFlash('success', 'Залата е обновена');
        } else {
            $this->session->setFlash('error', 'Грешка при запазване');
        }
        
        $this->redirect('/admin/rooms');
    }
    
    /**
     * Delete room
     */
    public function roomDelete(int $id): void
    {
        $this->requireRole('ADMIN');
        
        $room = Room::find($id);
        if ($room && $room->delete()) {
            $this->session->setFlash('success', 'Залата е изтрита');
        } else {
            $this->session->setFlash('error', 'Грешка при изтриване');
        }
        
        $this->redirect('/admin/rooms');
    }
    
    // ==================== COURSES ====================
    
    /**
     * List courses
     */
    public function courses(): void
    {
        $this->requireRole('ADMIN');
        
        $majorId = (int) $this->getQuery('major_id', 0);
        
        $courses = $majorId 
            ? $this->courseService->getCoursesByMajor($majorId)
            : Course::all();
        
        $majors = Major::all();
        
        $this->render('admin/courses/index', [
            'courses' => $courses,
            'majors' => $majors,
            'selectedMajor' => $majorId,
            'title' => 'Курсове'
        ]);
    }
    
    /**
     * Create course form
     */
    public function courseCreate(): void
    {
        $this->requireRole('ADMIN');
        
        $majors = Major::all();
        $allCourses = Course::all(); // For prerequisites
        
        $this->render('admin/courses/create', [
            'majors' => $majors,
            'allCourses' => $allCourses,
            'title' => 'Нов курс'
        ]);
    }
    
    /**
     * Store course
     */
    public function courseStore(): void
    {
        $this->requireRole('ADMIN');
        
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Невалиден CSRF токен');
            $this->redirect('/admin/courses/create');
            return;
        }
        
        $data = [
            'code' => trim($this->getPost('code', '')),
            'name_bg' => trim($this->getPost('name_bg', '')),
            'name_en' => trim($this->getPost('name_en', '')),
            'outline_bg' => trim($this->getPost('description', '')),
            'major_id' => (int) $this->getPost('major_id', 0) ?: null,
            'credits' => (int) $this->getPost('credits', 5),
            'is_elective' => (bool) $this->getPost('is_elective', false),
        ];
        
        $prerequisites = $this->getPost('prerequisites', []);
        if (!empty($prerequisites)) {
            $data['prerequisites'] = array_map(fn($id) => ['id' => (int) $id, 'is_recommended' => 0], $prerequisites);
        }
        
        $result = $this->courseService->createCourse($data);
        
        if ($result['success']) {
            $this->session->setFlash('success', 'Курсът е създаден');
            $this->redirect('/admin/courses');
        } else {
            $this->session->setFlash('error', $result['message']);
            $this->redirect('/admin/courses/create');
        }
    }
    
    /**
     * Edit course
     */
    public function courseEdit(int $id): void
    {
        $this->requireRole('ADMIN');
        
        $course = Course::find($id);
        if (!$course) {
            $this->session->setFlash('error', 'Курсът не е намерен');
            $this->redirect('/admin/courses');
            return;
        }
        
        $majors = Major::all();
        $allCourses = Course::all();
        $prerequisites = $this->courseService->getPrerequisites($id);
        
        $this->render('admin/courses/edit', [
            'course' => $course,
            'majors' => $majors,
            'allCourses' => $allCourses,
            'prerequisites' => $prerequisites,
            'title' => 'Редактиране на курс'
        ]);
    }
    
    /**
     * Update course
     */
    public function courseUpdate(int $id): void
    {
        $this->requireRole('ADMIN');
        
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Невалиден CSRF токен');
            $this->redirect("/admin/courses/$id/edit");
            return;
        }
        
        $data = [
            'name_bg' => trim($this->getPost('name_bg', '')),
            'name_en' => trim($this->getPost('name_en', '')),
            'outline_bg' => trim($this->getPost('description', '')),
            'major_id' => (int) $this->getPost('major_id', 0) ?: null,
            'credits' => (int) $this->getPost('credits', 5),
            'is_elective' => (bool) $this->getPost('is_elective', false),
        ];
        
        $prerequisites = $this->getPost('prerequisites', []);
        if (!empty($prerequisites)) {
            $data['prerequisites'] = array_map(fn($prereqId) => ['id' => (int) $prereqId, 'is_recommended' => 0], $prerequisites);
        } else {
            $data['prerequisites'] = [];
        }
        
        $result = $this->courseService->updateCourse($id, $data);
        
        if ($result['success']) {
            $this->session->setFlash('success', 'Курсът е обновен');
        } else {
            $this->session->setFlash('error', $result['message']);
        }
        
        $this->redirect('/admin/courses');
    }
    
    /**
     * Delete course
     */
    public function courseDelete(int $id): void
    {
        $this->requireRole('ADMIN');
        
        if ($this->courseService->deleteCourse($id)) {
            $this->session->setFlash('success', 'Курсът е изтрит');
        } else {
            $this->session->setFlash('error', 'Грешка при изтриване');
        }
        
        $this->redirect('/admin/courses');
    }
    
    // ==================== SCHEDULE GENERATION ====================
    
    /**
     * Schedule generation page
     */
    public function scheduleGeneration(): void
    {
        $this->requireRole('ADMIN');
        
        $period = $this->getCurrentAcademicPeriod();
        
        // Get existing variants
        $weeklyVariants = $this->scheduleGenerator->getVariants(
            'WEEKLY', $period['year'], $period['semester']
        );
        $testVariants = $this->scheduleGenerator->getVariants(
            'TEST', $period['year'], $period['semester']
        );
        $examRegularVariants = $this->scheduleGenerator->getVariants(
            'EXAM', $period['year'], $period['semester'], 'REGULAR'
        );
        $examLiquidationVariants = $this->scheduleGenerator->getVariants(
            'EXAM', $period['year'], $period['semester'], 'LIQUIDATION'
        );
        
        $this->render('admin/schedule/generation', [
            'period' => $period,
            'weeklyVariants' => $weeklyVariants,
            'testVariants' => $testVariants,
            'examRegularVariants' => $examRegularVariants,
            'examLiquidationVariants' => $examLiquidationVariants,
            'title' => 'Генериране на разписания'
        ]);
    }
    
    /**
     * Generate weekly schedule
     */
    public function generateWeekly(): void
    {
        $this->requireRole('ADMIN');
        
        $period = $this->getCurrentAcademicPeriod();
        
        try {
            $variants = $this->scheduleGenerator->generateWeeklySchedule(
                $period['year'], $period['semester']
            );
            
            $this->session->setFlash('success', 
                'Генерирани са ' . count($variants) . ' варианта на седмичното разписание');
        } catch (\Exception $e) {
            $this->session->setFlash('error', 'Грешка: ' . $e->getMessage());
        }
        
        $this->redirect('/admin/schedule');
    }
    
    /**
     * Generate test schedule
     */
    public function generateTests(): void
    {
        $this->requireRole('ADMIN');
        
        $period = $this->getCurrentAcademicPeriod();
        
        try {
            $variants = $this->scheduleGenerator->generateTestSchedule(
                $period['year'], $period['semester']
            );
            
            $this->session->setFlash('success', 
                'Генерирани са ' . count($variants) . ' варианта на разписание за контролни');
        } catch (\Exception $e) {
            $this->session->setFlash('error', 'Грешка: ' . $e->getMessage());
        }
        
        $this->redirect('/admin/schedule');
    }
    
    /**
     * Generate exam schedule
     */
    public function generateExams(): void
    {
        $this->requireRole('ADMIN');
        
        $period = $this->getCurrentAcademicPeriod();
        $sessionType = $this->getPost('session_type', 'REGULAR');
        
        try {
            $variants = $this->scheduleGenerator->generateExamSchedule(
                $period['year'], $period['semester'], $sessionType
            );
            
            $sessionLabel = $sessionType === 'REGULAR' ? 'редовна' : 'ликвидационна';
            $this->session->setFlash('success', 
                "Генерирани са " . count($variants) . " варианта за $sessionLabel сесия");
        } catch (\Exception $e) {
            $this->session->setFlash('error', 'Грешка: ' . $e->getMessage());
        }
        
        $this->redirect('/admin/schedule');
    }
    
    /**
     * Select a schedule variant
     */
    public function selectVariant(int $id): void
    {
        $this->requireRole('ADMIN');
        
        if ($this->scheduleGenerator->selectVariant($id)) {
            $this->session->setFlash('success', 'Вариантът е избран');
        } else {
            $this->session->setFlash('error', 'Грешка при избор на вариант');
        }
        
        $this->redirect('/admin/schedule');
    }
    
    /**
     * Delete a schedule variant
     */
    public function deleteVariant(int $id): void
    {
        $this->requireRole('ADMIN');
        
        if ($this->scheduleGenerator->deleteVariant($id)) {
            $this->session->setFlash('success', 'Вариантът е изтрит');
        } else {
            $this->session->setFlash('error', 'Грешка при изтриване');
        }
        
        $this->redirect('/admin/schedule');
    }
    
    /**
     * View a schedule variant
     */
    public function viewVariant(int $id): void
    {
        $this->requireRole('ADMIN');
        
        $variant = \App\Models\ScheduleVariant::find($id);
        
        if (!$variant) {
            $this->session->setFlash('error', 'Вариантът не е намерен');
            $this->redirect('/admin/schedule');
            return;
        }
        
        $scheduleData = [];
        $type = $variant->type;
        
        if ($type === 'WEEKLY') {
            // Get weekly slots for this variant with major info
            $scheduleData = $this->db->fetchAll(
                "SELECT ws.*, 
                        c.name_bg as course_name, c.code as course_code,
                        c.is_elective as is_elective,
                        c.major_id as course_major_id,
                        m.name_bg as major_name,
                        m.abbreviation as major_abbr,
                        r.number as room_number,
                        g.name_bg as group_name,
                        ms.name_bg as stream_name,
                        ms.major_id as stream_major_id,
                        u.full_name as assistant_name
                 FROM weekly_slot ws
                 JOIN course_instance ci ON ws.course_instance_id = ci.id
                 JOIN course c ON ci.course_id = c.id
                 JOIN room r ON ws.room_id = r.id
                 LEFT JOIN major m ON c.major_id = m.id
                 LEFT JOIN student_group g ON ws.group_id = g.id
                 LEFT JOIN major_stream ms ON g.stream_id = ms.id
                 LEFT JOIN user u ON ws.assistant_id = u.id
                 WHERE ws.variant_id = :variant_id
                 ORDER BY ws.day_of_week, ws.start_time",
                ['variant_id' => $id]
            );
        } elseif ($type === 'TEST') {
            // Get test schedules for this variant
            $scheduleData = $this->db->fetchAll(
                "SELECT ts.*, 
                        c.name_bg as course_name, c.code as course_code,
                        r.number as room_number
                 FROM test_schedule ts
                 JOIN course_instance ci ON ts.course_instance_id = ci.id
                 JOIN course c ON ci.course_id = c.id
                 JOIN room r ON ts.room_id = r.id
                 WHERE ts.variant_id = :variant_id
                 ORDER BY ts.date, ts.start_time",
                ['variant_id' => $id]
            );
        } elseif ($type === 'EXAM') {
            // Get exam schedules for this variant
            $scheduleData = $this->db->fetchAll(
                "SELECT es.*, 
                        c.name_bg as course_name, c.code as course_code,
                        r.number as room_number
                 FROM exam_schedule es
                 JOIN course_instance ci ON es.course_instance_id = ci.id
                 JOIN course c ON ci.course_id = c.id
                 JOIN room r ON es.room_id = r.id
                 WHERE es.variant_id = :variant_id
                 ORDER BY es.date, es.start_time",
                ['variant_id' => $id]
            );
        }
        
        // Get all majors for filter
        $majors = \App\Models\Major::all();
        
        $this->render('admin/schedule/view', [
            'variant' => $variant,
            'scheduleData' => $scheduleData,
            'type' => $type,
            'majors' => $majors,
            'title' => 'Преглед на вариант: ' . $variant->name
        ]);
    }
    
    // ==================== SETTINGS ====================
    
    /**
     * Academic settings page
     */
    public function settings(): void
    {
        $this->requireRole('ADMIN');
        
        $settings = AcademicSettings::getCurrentSettings();
        
        $this->render('admin/settings', [
            'settings' => $settings,
            'title' => 'Настройки'
        ]);
    }
    
    /**
     * Update settings
     */
    public function updateSettings(): void
    {
        $this->requireRole('ADMIN');
        
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Невалиден CSRF токен');
            $this->redirect('/admin/settings');
            return;
        }
        
        $data = [
            'academic_year' => (int) $this->getPost('academic_year'),
            'winter_semester_start' => $this->getPost('winter_semester_start'),
            'winter_semester_end' => $this->getPost('winter_semester_end'),
            'summer_semester_start' => $this->getPost('summer_semester_start'),
            'summer_semester_end' => $this->getPost('summer_semester_end'),
        ];
        
        $settings = AcademicSettings::getCurrentSettings();
        
        if ($settings) {
            foreach ($data as $key => $value) {
                $settings->$key = $value;
            }
            $settings->save();
        } else {
            AcademicSettings::create($data);
        }
        
        $this->session->setFlash('success', 'Настройките са запазени');
        $this->redirect('/admin/settings');
    }
    
    /**
     * View system logs
     */
    public function logs(): void
    {
        $this->requireRole('ADMIN');
        
        $page = (int) $this->getQuery('page', 1);
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $logs = $this->db->fetchAll(
            "SELECT l.* 
             FROM log l 
             ORDER BY l.timestamp DESC
             LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );
        
        $total = $this->db->fetch("SELECT COUNT(*) as count FROM log")['count'];
        
        $this->render('admin/logs', [
            'logs' => $logs,
            'page' => $page,
            'totalPages' => ceil($total / $limit),
            'title' => 'Системни логове'
        ]);
    }
}
