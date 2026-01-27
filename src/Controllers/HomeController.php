<?php
namespace App\Controllers;

use App\Models\Major;
use App\Models\MajorStream;
use App\Services\ScheduleService;

/**
 * Home Controller
 * 
 * Handles public pages and schedule viewing.
 */
class HomeController extends BaseController
{
    /**
     * Home page - redirect to appropriate schedule view
     */
    public function index(): void
    {
        // Get all majors for navigation
        $majors = Major::all();
        
        $this->render('home/index', [
            'majors' => $majors,
            'title' => 'Curricula - Разписания ФМИ'
        ]);
    }
    
    /**
     * View schedule by stream
     */
    public function scheduleByStream(): void
    {
        $streamId = (int) $this->getQuery('stream_id');
        $view = $this->getQuery('view', 'table'); // table or calendar
        
        $period = $this->getCurrentAcademicPeriod();
        
        $scheduleService = new ScheduleService();
        $schedule = $scheduleService->getStreamSchedule(
            $streamId,
            $period['year'],
            $period['semester']
        );
        
        $stream = MajorStream::find($streamId);
        $major = $stream ? Major::find($stream->major_id) : null;
        
        // Get all majors for navigation
        $majors = Major::all();
        
        $this->render('schedule/stream', [
            'schedule' => $schedule,
            'stream' => $stream,
            'major' => $major,
            'majors' => $majors,
            'view' => $view,
            'period' => $period,
            'title' => $stream ? "Разписание - {$stream->name_bg}" : 'Разписание'
        ]);
    }
    
    /**
     * View schedule by group
     */
    public function scheduleByGroup(): void
    {
        $groupId = (int) $this->getQuery('group_id');
        $view = $this->getQuery('view', 'table');
        
        $period = $this->getCurrentAcademicPeriod();
        
        $scheduleService = new ScheduleService();
        $schedule = $scheduleService->getGroupSchedule(
            $groupId,
            $period['year'],
            $period['semester']
        );
        
        $group = \App\Models\StudentGroup::find($groupId);
        $stream = $group ? MajorStream::find($group->stream_id) : null;
        $major = $stream ? Major::find($stream->major_id) : null;
        
        $majors = Major::all();
        
        $this->render('schedule/group', [
            'schedule' => $schedule,
            'group' => $group,
            'stream' => $stream,
            'major' => $major,
            'majors' => $majors,
            'view' => $view,
            'period' => $period,
            'title' => $group ? "Разписание - Група {$group->number}" : 'Разписание'
        ]);
    }
    
    /**
     * View lecturer schedule
     */
    public function lecturerSchedule(): void
    {
        $lecturerId = (int) $this->getQuery('lecturer_id');
        $view = $this->getQuery('view', 'table');
        
        $period = $this->getCurrentAcademicPeriod();
        
        $scheduleService = new ScheduleService();
        $schedule = $scheduleService->getLecturerSchedule(
            $lecturerId,
            $period['year'],
            $period['semester']
        );
        
        $lecturer = \App\Models\User::find($lecturerId);
        $majors = Major::all();
        
        $this->render('schedule/lecturer', [
            'schedule' => $schedule,
            'lecturer' => $lecturer,
            'majors' => $majors,
            'view' => $view,
            'period' => $period,
            'title' => $lecturer ? "Разписание - {$lecturer->first_name} {$lecturer->last_name}" : 'Разписание на преподавател'
        ]);
    }
    
    /**
     * View room schedule
     */
    public function roomSchedule(): void
    {
        $roomId = (int) $this->getQuery('room_id');
        $view = $this->getQuery('view', 'table');
        
        $period = $this->getCurrentAcademicPeriod();
        
        $scheduleService = new ScheduleService();
        $schedule = $scheduleService->getRoomSchedule(
            $roomId,
            $period['year'],
            $period['semester']
        );
        
        $room = \App\Models\Room::find($roomId);
        $majors = Major::all();
        $rooms = \App\Models\Room::all();
        
        $this->render('schedule/room', [
            'schedule' => $schedule,
            'room' => $room,
            'rooms' => $rooms,
            'majors' => $majors,
            'view' => $view,
            'period' => $period,
            'title' => $room ? "Разписание - Зала {$room->number}" : 'Разписание на зала'
        ]);
    }
    
    /**
     * Test schedule view
     */
    public function testSchedule(): void
    {
        $streamId = (int) $this->getQuery('stream_id', 0);
        
        $period = $this->getCurrentAcademicPeriod();
        
        $scheduleService = new ScheduleService();
        $schedule = $scheduleService->getTestSchedule(
            $streamId,
            $period['year'],
            $period['semester']
        );
        
        $stream = $streamId ? MajorStream::find($streamId) : null;
        $major = $stream ? Major::find($stream->major_id) : null;
        $majors = Major::all();
        
        $this->render('schedule/tests', [
            'schedule' => $schedule,
            'stream' => $stream,
            'major' => $major,
            'majors' => $majors,
            'period' => $period,
            'title' => 'Контролни'
        ]);
    }
    
    /**
     * Exam schedule view
     */
    public function examSchedule(): void
    {
        $streamId = (int) $this->getQuery('stream_id', 0);
        $sessionType = $this->getQuery('session', 'REGULAR');
        
        $period = $this->getCurrentAcademicPeriod();
        
        $scheduleService = new ScheduleService();
        $schedule = $scheduleService->getExamSchedule(
            $streamId,
            $period['year'],
            $period['semester'],
            $sessionType
        );
        
        $stream = $streamId ? MajorStream::find($streamId) : null;
        $major = $stream ? Major::find($stream->major_id) : null;
        $majors = Major::all();
        
        $sessionLabel = $sessionType === 'REGULAR' ? 'Редовна сесия' : 'Ликвидационна сесия';
        
        $this->render('schedule/exams', [
            'schedule' => $schedule,
            'stream' => $stream,
            'major' => $major,
            'majors' => $majors,
            'period' => $period,
            'sessionType' => $sessionType,
            'title' => "Изпити - $sessionLabel"
        ]);
    }
    
    /**
     * About page
     */
    public function about(): void
    {
        $this->render('home/about', [
            'title' => 'За системата'
        ]);
    }
}
