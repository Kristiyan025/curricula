<?php
namespace App\Services;

use App\Models\AcademicSettings;
use App\Core\Application;

/**
 * Schedule View Service
 * Handles retrieval of schedules for viewing
 */
class ScheduleService
{
    private $db;
    
    public function __construct()
    {
        $this->db = Application::getInstance()->getDb();
    }
    
    /**
     * Get weekly schedule for a stream (all groups)
     */
    public function getWeeklyScheduleByStream(int $streamId): array
    {
        $year = AcademicSettings::getCurrentYear();
        $semester = AcademicSettings::getCurrentSemester();
        
        return $this->db->fetchAll(
            "SELECT ws.*, c.name_bg as course_name, c.code as course_code,
                    r.number as room_number, g.name_bg as group_name,
                    u.full_name as assistant_name, c.is_elective
             FROM weekly_slot ws
             JOIN course_instance ci ON ws.course_instance_id = ci.id
             JOIN course c ON ci.course_id = c.id
             JOIN room r ON ws.room_id = r.id
             LEFT JOIN student_group g ON ws.group_id = g.id
             LEFT JOIN user u ON ws.assistant_id = u.id
             WHERE ws.variant_id IS NULL
               AND ci.academic_year = :year
               AND ci.semester = :semester
               AND (
                   (ws.group_id IN (SELECT id FROM student_group WHERE stream_id = :stream_id))
                   OR (ws.slot_type = 'LECTURE' AND c.is_elective = 0 
                       AND c.major_id = (SELECT major_id FROM major_stream WHERE id = :stream_id2))
               )
             ORDER BY FIELD(ws.day_of_week, 'MON', 'TUE', 'WED', 'THU', 'FRI'), ws.start_time",
            [
                'year' => $year,
                'semester' => $semester,
                'stream_id' => $streamId,
                'stream_id2' => $streamId
            ]
        );
    }
    
    /**
     * Get weekly schedule for a specific group
     */
    public function getWeeklyScheduleByGroup(int $groupId): array
    {
        $year = AcademicSettings::getCurrentYear();
        $semester = AcademicSettings::getCurrentSemester();
        
        // Get stream and major for the group
        $groupInfo = $this->db->fetchOne(
            "SELECT g.stream_id, ms.major_id 
             FROM student_group g
             JOIN major_stream ms ON g.stream_id = ms.id
             WHERE g.id = :group_id",
            ['group_id' => $groupId]
        );
        
        if (!$groupInfo) {
            return [];
        }
        
        return $this->db->fetchAll(
            "SELECT ws.*, c.name_bg as course_name, c.code as course_code,
                    r.number as room_number, g.name_bg as group_name,
                    u.full_name as assistant_name, c.is_elective
             FROM weekly_slot ws
             JOIN course_instance ci ON ws.course_instance_id = ci.id
             JOIN course c ON ci.course_id = c.id
             JOIN room r ON ws.room_id = r.id
             LEFT JOIN student_group g ON ws.group_id = g.id
             LEFT JOIN user u ON ws.assistant_id = u.id
             WHERE ws.variant_id IS NULL
               AND ci.academic_year = :year
               AND ci.semester = :semester
               AND (
                   ws.group_id = :group_id
                   OR (ws.slot_type = 'LECTURE' AND c.is_elective = 0 AND c.major_id = :major_id)
               )
             ORDER BY FIELD(ws.day_of_week, 'MON', 'TUE', 'WED', 'THU', 'FRI'), ws.start_time",
            [
                'year' => $year,
                'semester' => $semester,
                'group_id' => $groupId,
                'major_id' => $groupInfo['major_id']
            ]
        );
    }
    
    /**
     * Get weekly schedule for a lecturer
     */
    public function getWeeklyScheduleByLecturer(int $lecturerId): array
    {
        $year = AcademicSettings::getCurrentYear();
        $semester = AcademicSettings::getCurrentSemester();
        
        return $this->db->fetchAll(
            "SELECT ws.*, c.name_bg as course_name, c.code as course_code,
                    r.number as room_number, g.name_bg as group_name,
                    u.full_name as assistant_name, c.is_elective
             FROM weekly_slot ws
             JOIN course_instance ci ON ws.course_instance_id = ci.id
             JOIN course c ON ci.course_id = c.id
             JOIN room r ON ws.room_id = r.id
             LEFT JOIN student_group g ON ws.group_id = g.id
             LEFT JOIN user u ON ws.assistant_id = u.id
             WHERE ws.variant_id IS NULL
               AND ci.academic_year = :year
               AND ci.semester = :semester
               AND (
                   ci.id IN (SELECT course_instance_id FROM course_lecturer WHERE user_id = :lecturer_id)
                   OR ci.id IN (SELECT course_instance_id FROM course_assistant WHERE user_id = :lecturer_id2)
               )
             ORDER BY FIELD(ws.day_of_week, 'MON', 'TUE', 'WED', 'THU', 'FRI'), ws.start_time",
            [
                'year' => $year,
                'semester' => $semester,
                'lecturer_id' => $lecturerId,
                'lecturer_id2' => $lecturerId
            ]
        );
    }
    
    /**
     * Get weekly schedule for elective courses
     */
    public function getWeeklyScheduleElectives(): array
    {
        $year = AcademicSettings::getCurrentYear();
        $semester = AcademicSettings::getCurrentSemester();
        
        return $this->db->fetchAll(
            "SELECT ws.*, c.name_bg as course_name, c.code as course_code,
                    r.number as room_number, g.name_bg as group_name,
                    u.full_name as assistant_name
             FROM weekly_slot ws
             JOIN course_instance ci ON ws.course_instance_id = ci.id
             JOIN course c ON ci.course_id = c.id
             JOIN room r ON ws.room_id = r.id
             LEFT JOIN student_group g ON ws.group_id = g.id
             LEFT JOIN user u ON ws.assistant_id = u.id
             WHERE ws.variant_id IS NULL
               AND ci.academic_year = :year
               AND ci.semester = :semester
               AND c.is_elective = 1
             ORDER BY c.code, FIELD(ws.day_of_week, 'MON', 'TUE', 'WED', 'THU', 'FRI'), ws.start_time",
            ['year' => $year, 'semester' => $semester]
        );
    }
    
    /**
     * Get test schedule for a stream
     */
    public function getTestScheduleByStream(int $streamId): array
    {
        $year = AcademicSettings::getCurrentYear();
        $semester = AcademicSettings::getCurrentSemester();
        
        return $this->db->fetchAll(
            "SELECT ts.*, c.name_bg as course_name, c.code as course_code,
                    r.number as room_number, c.is_elective
             FROM test_schedule ts
             JOIN course_instance ci ON ts.course_instance_id = ci.id
             JOIN course c ON ci.course_id = c.id
             JOIN room r ON ts.room_id = r.id
             WHERE ts.variant_id IS NULL
               AND ci.academic_year = :year
               AND ci.semester = :semester
               AND (
                   c.is_elective = 1
                   OR c.major_id = (SELECT major_id FROM major_stream WHERE id = :stream_id)
               )
             ORDER BY ts.date, ts.start_time",
            [
                'year' => $year,
                'semester' => $semester,
                'stream_id' => $streamId
            ]
        );
    }
    
    /**
     * Get test schedule for elective courses
     */
    public function getTestScheduleElectives(): array
    {
        $year = AcademicSettings::getCurrentYear();
        $semester = AcademicSettings::getCurrentSemester();
        
        return $this->db->fetchAll(
            "SELECT ts.*, c.name_bg as course_name, c.code as course_code,
                    r.number as room_number
             FROM test_schedule ts
             JOIN course_instance ci ON ts.course_instance_id = ci.id
             JOIN course c ON ci.course_id = c.id
             JOIN room r ON ts.room_id = r.id
             WHERE ts.variant_id IS NULL
               AND ci.academic_year = :year
               AND ci.semester = :semester
               AND c.is_elective = 1
             ORDER BY ts.date, ts.start_time",
            ['year' => $year, 'semester' => $semester]
        );
    }
    
    /**
     * Get exam schedule for a stream
     */
    public function getExamScheduleByStream(int $streamId, ?string $sessionType = null): array
    {
        $year = AcademicSettings::getCurrentYear();
        $semester = AcademicSettings::getCurrentSemester();
        
        if ($sessionType === null) {
            $sessionType = AcademicSettings::getSessionType();
        }
        
        return $this->db->fetchAll(
            "SELECT es.*, c.name_bg as course_name, c.code as course_code,
                    r.number as room_number, c.is_elective
             FROM exam_schedule es
             JOIN course_instance ci ON es.course_instance_id = ci.id
             JOIN course c ON ci.course_id = c.id
             JOIN room r ON es.room_id = r.id
             WHERE es.variant_id IS NULL
               AND ci.academic_year = :year
               AND ci.semester = :semester
               AND (
                   c.is_elective = 0
                   AND c.major_id = (SELECT major_id FROM major_stream WHERE id = :stream_id)
               )
             ORDER BY es.date, es.start_time",
            [
                'year' => $year,
                'semester' => $semester,
                'stream_id' => $streamId
            ]
        );
    }
    
    /**
     * Get exam schedule for elective courses
     */
    public function getExamScheduleElectives(?string $sessionType = null): array
    {
        $year = AcademicSettings::getCurrentYear();
        $semester = AcademicSettings::getCurrentSemester();
        
        return $this->db->fetchAll(
            "SELECT es.*, c.name_bg as course_name, c.code as course_code,
                    r.number as room_number
             FROM exam_schedule es
             JOIN course_instance ci ON es.course_instance_id = ci.id
             JOIN course c ON ci.course_id = c.id
             JOIN room r ON es.room_id = r.id
             WHERE es.variant_id IS NULL
               AND ci.academic_year = :year
               AND ci.semester = :semester
               AND c.is_elective = 1
             ORDER BY es.date, es.start_time",
            ['year' => $year, 'semester' => $semester]
        );
    }
    
    /**
     * Format weekly schedule as a grid (for table display)
     */
    public function formatWeeklyScheduleAsGrid(array $slots): array
    {
        $grid = [];
        $days = ['MON', 'TUE', 'WED', 'THU', 'FRI'];
        $hours = range(8, 21);
        
        // Initialize grid
        foreach ($days as $day) {
            $grid[$day] = [];
            foreach ($hours as $hour) {
                $grid[$day][$hour] = null;
            }
        }
        
        // Fill grid with slots
        foreach ($slots as $slot) {
            $day = $slot['day_of_week'];
            $startHour = (int) substr($slot['start_time'], 0, 2);
            $endHour = (int) substr($slot['end_time'], 0, 2);
            
            for ($h = $startHour; $h < $endHour; $h++) {
                if (!isset($grid[$day][$h])) {
                    $grid[$day][$h] = [];
                } else if ($grid[$day][$h] === null) {
                    $grid[$day][$h] = [];
                }
                
                $grid[$day][$h][] = $slot;
            }
        }
        
        return $grid;
    }
    
    /**
     * Format schedule as calendar events (for monthly view)
     */
    public function formatScheduleAsCalendarEvents(array $items, string $type): array
    {
        $events = [];
        
        foreach ($items as $item) {
            $events[] = [
                'id' => $item['id'],
                'title' => $item['course_code'] . ' - ' . $item['course_name'],
                'start' => $item['date'] . 'T' . $item['start_time'],
                'end' => $item['date'] . 'T' . $item['end_time'],
                'room' => $item['room_number'],
                'type' => $type,
                'is_elective' => $item['is_elective'] ?? false,
                'extendedProps' => [
                    'course_instance_id' => $item['course_instance_id'],
                    'room_number' => $item['room_number'],
                    'index' => $item[$type . '_index'] ?? null
                ]
            ];
        }
        
        return $events;
    }
    
    /**
     * Get day of week label in Bulgarian
     */
    public static function getDayLabel(string $day): string
    {
        $labels = [
            'MON' => 'Понеделник',
            'TUE' => 'Вторник',
            'WED' => 'Сряда',
            'THU' => 'Четвъртък',
            'FRI' => 'Петък',
            'SAT' => 'Събота',
            'SUN' => 'Неделя'
        ];
        
        return $labels[$day] ?? $day;
    }
    
    /**
     * Get day abbreviation in Bulgarian
     */
    public static function getDayAbbr(string $day): string
    {
        $labels = [
            'MON' => 'Пн',
            'TUE' => 'Вт',
            'WED' => 'Ср',
            'THU' => 'Чт',
            'FRI' => 'Пт',
            'SAT' => 'Сб',
            'SUN' => 'Нд'
        ];
        
        return $labels[$day] ?? $day;
    }
    
    /**
     * Get stream schedule (alias for getWeeklyScheduleByStream)
     * @param int $streamId
     * @param string|null $year Academic year (defaults to current)
     * @param string|null $semester Semester (defaults to current)
     */
    public function getStreamSchedule(int $streamId, ?string $year = null, ?string $semester = null): array
    {
        // Note: year/semester params are accepted for API compatibility but schedule always uses current period
        return $this->getWeeklyScheduleByStream($streamId);
    }
    
    /**
     * Get group schedule (alias for getWeeklyScheduleByGroup)
     * @param int $groupId
     * @param string|null $year Academic year (defaults to current)
     * @param string|null $semester Semester (defaults to current)
     */
    public function getGroupSchedule(int $groupId, ?string $year = null, ?string $semester = null): array
    {
        return $this->getWeeklyScheduleByGroup($groupId);
    }
    
    /**
     * Get lecturer schedule (alias for getWeeklyScheduleByLecturer)
     * @param int $lecturerId
     * @param string|null $year Academic year (defaults to current)
     * @param string|null $semester Semester (defaults to current)
     */
    public function getLecturerSchedule(int $lecturerId, ?string $year = null, ?string $semester = null): array
    {
        return $this->getWeeklyScheduleByLecturer($lecturerId);
    }
    
    /**
     * Get room schedule
     * @param int $roomId
     * @param string|null $year Academic year (defaults to current)
     * @param string|null $semester Semester (defaults to current)
     */
    public function getRoomSchedule(int $roomId, ?string $year = null, ?string $semester = null): array
    {
        $year = AcademicSettings::getCurrentYear();
        $semester = AcademicSettings::getCurrentSemester();
        
        return $this->db->fetchAll(
            "SELECT ws.*, c.name_bg as course_name, c.code as course_code,
                    r.number as room_number, g.name_bg as group_name,
                    u.full_name as assistant_name, c.is_elective
             FROM weekly_slot ws
             JOIN course_instance ci ON ws.course_instance_id = ci.id
             JOIN course c ON ci.course_id = c.id
             JOIN room r ON ws.room_id = r.id
             LEFT JOIN student_group g ON ws.group_id = g.id
             LEFT JOIN user u ON ws.assistant_id = u.id
             WHERE ws.variant_id IS NULL
               AND ci.academic_year = :year
               AND ci.semester = :semester
               AND ws.room_id = :room_id
             ORDER BY FIELD(ws.day_of_week, 'MON', 'TUE', 'WED', 'THU', 'FRI'), ws.start_time",
            [
                'year' => $year,
                'semester' => $semester,
                'room_id' => $roomId
            ]
        );
    }
    
    /**
     * Get test schedule (alias for getTestScheduleByStream or all tests)
     * @param int|null $streamId
     * @param string|null $year Academic year (defaults to current)
     * @param string|null $semester Semester (defaults to current)
     */
    public function getTestSchedule(?int $streamId = null, ?string $year = null, ?string $semester = null): array
    {
        if ($streamId !== null) {
            return $this->getTestScheduleByStream($streamId);
        }
        return $this->getTestScheduleElectives();
    }
    
    /**
     * Get exam schedule (alias for getExamScheduleByStream or all exams)
     * @param int|null $streamId
     * @param string|null $year Academic year (defaults to current)
     * @param string|null $semester Semester (defaults to current)
     * @param string|null $sessionType Session type (REGULAR, CORRECTIVE, EARLY)
     */
    public function getExamSchedule(?int $streamId = null, ?string $year = null, ?string $semester = null, ?string $sessionType = null): array
    {
        if ($streamId !== null) {
            return $this->getExamScheduleByStream($streamId, $sessionType);
        }
        return $this->getExamScheduleElectives($sessionType);
    }
}
