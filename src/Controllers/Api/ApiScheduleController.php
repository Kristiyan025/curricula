<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\ScheduleService;
use App\Models\Major;
use App\Models\MajorStream;
use App\Models\StudentGroup;
use App\Models\Room;

/**
 * API Schedule Controller
 * 
 * RESTful API for schedule data.
 */
class ApiScheduleController extends BaseController
{
    private ScheduleService $scheduleService;
    
    public function __construct()
    {
        parent::__construct();
        $this->scheduleService = new ScheduleService();
    }
    
    /**
     * Get weekly schedule for a stream
     * GET /api/schedule/stream/{id}
     */
    public function getStreamSchedule(int $streamId): void
    {
        $year = (int) $this->getQuery('year', date('Y'));
        $semester = $this->getQuery('semester', 'WINTER');
        
        $schedule = $this->scheduleService->getStreamSchedule($streamId, $year, $semester);
        $stream = MajorStream::find($streamId);
        $major = $stream ? Major::find($stream->major_id) : null;
        
        $this->jsonSuccess([
            'stream' => $stream ? [
                'id' => $stream->id,
                'name' => $stream->name_bg,
                'major' => $major ? [
                    'id' => $major->id,
                    'name_bg' => $major->name_bg,
                    'name_en' => $major->name_en,
                ] : null
            ] : null,
            'academic_year' => $year,
            'semester' => $semester,
            'schedule' => $this->formatScheduleForApi($schedule)
        ]);
    }
    
    /**
     * Get weekly schedule for a group
     * GET /api/schedule/group/{id}
     */
    public function getGroupSchedule(int $groupId): void
    {
        $year = (int) $this->getQuery('year', date('Y'));
        $semester = $this->getQuery('semester', 'WINTER');
        
        $schedule = $this->scheduleService->getGroupSchedule($groupId, $year, $semester);
        $group = StudentGroup::find($groupId);
        
        $this->jsonSuccess([
            'group' => $group ? [
                'id' => $group->id,
                'number' => $group->number,
                'stream_id' => $group->stream_id,
            ] : null,
            'academic_year' => $year,
            'semester' => $semester,
            'schedule' => $this->formatScheduleForApi($schedule)
        ]);
    }
    
    /**
     * Get schedule for a lecturer
     * GET /api/schedule/lecturer/{id}
     */
    public function getLecturerSchedule(int $lecturerId): void
    {
        $year = (int) $this->getQuery('year', date('Y'));
        $semester = $this->getQuery('semester', 'WINTER');
        
        $schedule = $this->scheduleService->getLecturerSchedule($lecturerId, $year, $semester);
        
        $lecturer = $this->db->fetch(
            "SELECT id, first_name, last_name, email FROM user WHERE id = :id",
            ['id' => $lecturerId]
        );
        
        $this->jsonSuccess([
            'lecturer' => $lecturer,
            'academic_year' => $year,
            'semester' => $semester,
            'schedule' => $this->formatScheduleForApi($schedule)
        ]);
    }
    
    /**
     * Get schedule for a room
     * GET /api/schedule/room/{id}
     */
    public function getRoomSchedule(int $roomId): void
    {
        $year = (int) $this->getQuery('year', date('Y'));
        $semester = $this->getQuery('semester', 'WINTER');
        
        $schedule = $this->scheduleService->getRoomSchedule($roomId, $year, $semester);
        $room = Room::find($roomId);
        
        $this->jsonSuccess([
            'room' => $room ? [
                'id' => $room->id,
                'number' => $room->number,
                'building' => $room->building,
                'capacity' => $room->capacity,
                'room_type' => $room->room_type,
            ] : null,
            'academic_year' => $year,
            'semester' => $semester,
            'schedule' => $this->formatScheduleForApi($schedule)
        ]);
    }
    
    /**
     * Get test schedule
     * GET /api/schedule/tests
     */
    public function getTestSchedule(): void
    {
        $streamId = (int) $this->getQuery('stream_id', 0);
        $year = (int) $this->getQuery('year', date('Y'));
        $semester = $this->getQuery('semester', 'WINTER');
        
        $schedule = $this->scheduleService->getTestSchedule($streamId, $year, $semester);
        
        $this->jsonSuccess([
            'stream_id' => $streamId ?: null,
            'academic_year' => $year,
            'semester' => $semester,
            'tests' => $schedule
        ]);
    }
    
    /**
     * Get exam schedule
     * GET /api/schedule/exams
     */
    public function getExamSchedule(): void
    {
        $streamId = (int) $this->getQuery('stream_id', 0);
        $year = (int) $this->getQuery('year', date('Y'));
        $semester = $this->getQuery('semester', 'WINTER');
        $sessionType = $this->getQuery('session_type', 'REGULAR');
        
        $schedule = $this->scheduleService->getExamSchedule($streamId, $year, $semester, $sessionType);
        
        $this->jsonSuccess([
            'stream_id' => $streamId ?: null,
            'academic_year' => $year,
            'semester' => $semester,
            'session_type' => $sessionType,
            'exams' => $schedule
        ]);
    }
    
    /**
     * Get available majors and streams
     * GET /api/schedule/navigation
     */
    public function getNavigation(): void
    {
        $majors = Major::all();
        $result = [];
        
        foreach ($majors as $major) {
            $streams = MajorStream::where('major_id', $major->id);
            $streamData = [];
            
            foreach ($streams as $stream) {
                $groups = StudentGroup::where('stream_id', $stream->id);
                $streamData[] = [
                    'id' => $stream->id,
                    'name' => $stream->name_bg,
                    'groups' => array_map(fn($g) => [
                        'id' => $g->id,
                        'number' => $g->number
                    ], $groups)
                ];
            }
            
            $result[] = [
                'id' => $major->id,
                'code' => $major->code,
                'name_bg' => $major->name_bg,
                'name_en' => $major->name_en,
                'degree' => $major->degree,
                'streams' => $streamData
            ];
        }
        
        $this->jsonSuccess(['majors' => $result]);
    }
    
    /**
     * Get all rooms
     * GET /api/schedule/rooms
     */
    public function getRooms(): void
    {
        $rooms = Room::all();
        
        $this->jsonSuccess([
            'rooms' => array_map(fn($r) => [
                'id' => $r->id,
                'number' => $r->number,
                'building' => $r->building,
                'floor' => $r->floor,
                'capacity' => $r->capacity,
                'room_type' => $r->room_type,
                'has_projector' => (bool) $r->has_projector,
                'has_computers' => (bool) $r->has_computers,
            ], $rooms)
        ]);
    }
    
    /**
     * Format schedule data for API response
     */
    private function formatScheduleForApi(array $schedule): array
    {
        $formatted = [
            'by_day' => [],
            'items' => []
        ];
        
        $days = ['MON' => 'Monday', 'TUE' => 'Tuesday', 'WED' => 'Wednesday', 
                 'THU' => 'Thursday', 'FRI' => 'Friday', 'SAT' => 'Saturday'];
        
        foreach ($days as $code => $name) {
            $formatted['by_day'][$code] = [];
        }
        
        foreach ($schedule as $slot) {
            $item = [
                'id' => $slot['id'] ?? null,
                'day_of_week' => $slot['day_of_week'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'slot_type' => $slot['slot_type'] ?? 'LECTURE',
                'course' => [
                    'code' => $slot['course_code'] ?? null,
                    'name' => $slot['course_name'] ?? null,
                ],
                'room' => [
                    'number' => $slot['room_number'] ?? null,
                ],
                'group_id' => $slot['group_id'] ?? null,
            ];
            
            $formatted['items'][] = $item;
            
            if (isset($formatted['by_day'][$slot['day_of_week']])) {
                $formatted['by_day'][$slot['day_of_week']][] = $item;
            }
        }
        
        return $formatted;
    }
}
