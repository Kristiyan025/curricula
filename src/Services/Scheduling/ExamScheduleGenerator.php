<?php
namespace App\Services\Scheduling;

use App\Models\AcademicSettings;
use App\Models\ScheduleVariant;
use App\Utils\Logger;

/**
 * Exam Schedule Generator
 * 
 * Generates exam session schedules (REGULAR and LIQUIDATION).
 * Handles prerequisite constraints and exam spacing requirements.
 */
class ExamScheduleGenerator extends BaseScheduleGenerator
{
    private array $prerequisites = [];
    private array $groupsByStream = [];
    private array $streamsByMajor = [];
    private string $sessionType;
    
    // Session date ranges
    private ?string $sessionStartDate = null;
    private ?string $sessionEndDate = null;
    
    // Exam constraints
    private const EXAM_START_HOUR = 8;
    private const EXAM_END_HOUR = 20;
    private const MIN_DAYS_BETWEEN_EXAMS = 2; // Minimum days between exams for same group
    
    /**
     * Generate exam schedule variants
     */
    public function generate(int $year, string $semester, string $sessionType = 'REGULAR'): array
    {
        $this->sessionType = $sessionType;
        
        $this->logger->log(Logger::SCHEDULE_GENERATION_STARTED, [
            'type' => 'EXAM',
            'year' => $year,
            'semester' => $semester,
            'session_type' => $sessionType
        ]);
        
        try {
            // Load data
            $this->loadCommonData($year, $semester);
            $this->loadExamData($year, $semester);
            $this->loadSessionDates($year, $semester);
            
            if (!$this->sessionStartDate || !$this->sessionEndDate) {
                throw new \Exception("Session dates not configured for $year $semester $sessionType");
            }
            
            // Delete old non-selected variants
            $this->deleteOldVariants(ScheduleVariant::TYPE_EXAM, $year, $semester, $sessionType);
            
            // Generate items to schedule
            $items = $this->generateScheduleItems();
            
            if (empty($items)) {
                return [];
            }
            
            // Generate available slots
            $availableSlots = $this->generateExamSlots();
            
            // Run GA
            $solutions = $this->runGA($items, $availableSlots);
            
            // Save variants
            $variants = [];
            $names = ['A', 'B', 'C'];
            $sessionLabel = $sessionType === 'REGULAR' ? 'Редовна сесия' : 'Ликвидационна сесия';
            
            foreach ($solutions as $i => $solution) {
                $variant = $this->saveVariant(
                    ScheduleVariant::TYPE_EXAM,
                    $year,
                    $semester,
                    "$sessionLabel - Вариант " . $names[$i],
                    $solution['fitness'],
                    $solution['chromosome'],
                    $sessionType
                );
                $variants[] = $variant;
            }
            
            $this->logger->log(Logger::SCHEDULE_GENERATION_COMPLETED, [
                'type' => 'EXAM',
                'session_type' => $sessionType,
                'variants_count' => count($variants)
            ]);
            
            return $variants;
            
        } catch (\Exception $e) {
            $this->logger->log(Logger::SCHEDULE_GENERATION_FAILED, [
                'type' => 'EXAM',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Load exam-specific data
     */
    private function loadExamData(int $year, string $semester): void
    {
        // Load course instances
        $instances = $this->db->fetchAll(
            "SELECT ci.*, c.name_bg, c.code, c.is_elective, c.major_id
             FROM course_instance ci
             JOIN course c ON ci.course_id = c.id
             WHERE ci.academic_year = :year AND ci.semester = :semester",
            ['year' => $year, 'semester' => $semester]
        );
        
        foreach ($instances as $instance) {
            $this->courseInstances[$instance['id']] = $instance;
        }
        
        // Load prerequisites
        $prereqs = $this->db->fetchAll(
            "SELECT cp.* FROM course_prerequisite cp
             JOIN course c ON cp.course_id = c.id
             JOIN course_instance ci ON ci.course_id = c.id
             WHERE ci.academic_year = :year AND ci.semester = :semester",
            ['year' => $year, 'semester' => $semester]
        );
        
        foreach ($prereqs as $prereq) {
            $this->prerequisites[$prereq['course_id']][] = $prereq['prerequisite_course_id'];
        }
        
        // Load groups by stream
        $groups = $this->db->fetchAll(
            "SELECT g.*, ms.major_id 
             FROM student_group g 
             JOIN major_stream ms ON g.stream_id = ms.id"
        );
        
        foreach ($groups as $group) {
            $this->groupsByStream[$group['stream_id']][] = $group;
            $this->groups[$group['id']] = $group;
        }
        
        // Load streams by major
        $streams = $this->db->fetchAll("SELECT * FROM major_stream");
        foreach ($streams as $stream) {
            $this->streamsByMajor[$stream['major_id']][] = $stream;
        }
    }
    
    /**
     * Load session dates from academic settings
     */
    private function loadSessionDates(int $year, string $semester): void
    {
        $settings = AcademicSettings::getCurrentSettings();
        
        if (!$settings) {
            return;
        }
        
        $settingKey = $this->sessionType === 'REGULAR' 
            ? 'exam_session_start' 
            : 'liquidation_session_start';
        $endKey = $this->sessionType === 'REGULAR'
            ? 'exam_session_end'
            : 'liquidation_session_end';
        
        $this->sessionStartDate = $settings->$settingKey ?? null;
        $this->sessionEndDate = $settings->$endKey ?? null;
        
        // If not in academic settings, try to derive from semester dates
        if (!$this->sessionStartDate) {
            $semesterEnd = $semester === 'WINTER' 
                ? $settings->winter_semester_end 
                : $settings->summer_semester_end;
            
            if ($semesterEnd) {
                $endDate = new \DateTime($semesterEnd);
                $this->sessionStartDate = $endDate->modify('+1 day')->format('Y-m-d');
                $this->sessionEndDate = $endDate->modify('+3 weeks')->format('Y-m-d');
            }
        }
    }
    
    /**
     * Generate items to schedule (exams)
     */
    private function generateScheduleItems(): array
    {
        $items = [];
        
        foreach ($this->courseInstances as $instance) {
            $majorId = $instance['major_id'];
            $streams = $this->streamsByMajor[$majorId] ?? [];
            $courseId = $instance['course_id'];
            
            // Get prerequisites for ordering
            $prereqIds = $this->prerequisites[$courseId] ?? [];
            
            if ($instance['is_elective']) {
                // One exam for all enrolled students
                $items[] = [
                    'instance_id' => $instance['id'],
                    'course_id' => $courseId,
                    'stream_id' => null,
                    'group_id' => null,
                    'major_id' => null,
                    'is_elective' => true,
                    'duration' => 3, // 3 hours default for exams
                    'prerequisites' => $prereqIds
                ];
            } else {
                // One exam per stream
                foreach ($streams as $stream) {
                    $items[] = [
                        'instance_id' => $instance['id'],
                        'course_id' => $courseId,
                        'stream_id' => $stream['id'],
                        'group_id' => null,
                        'major_id' => $majorId,
                        'is_elective' => false,
                        'duration' => 3,
                        'prerequisites' => $prereqIds
                    ];
                }
            }
        }
        
        // Sort by number of prerequisites (schedule prereqs first)
        usort($items, fn($a, $b) => count($a['prerequisites']) <=> count($b['prerequisites']));
        
        return $items;
    }
    
    /**
     * Generate available exam slots
     */
    private function generateExamSlots(): array
    {
        $slots = [];
        
        $start = new \DateTime($this->sessionStartDate);
        $end = new \DateTime($this->sessionEndDate);
        
        $current = clone $start;
        while ($current <= $end) {
            $dayOfWeek = (int) $current->format('N');
            
            // Skip Sundays (7)
            if ($dayOfWeek !== 7) {
                $date = $current->format('Y-m-d');
                
                // Generate morning and afternoon slots
                $slots[] = [
                    'date' => $date,
                    'start' => '08:00:00',
                    'end' => '11:00:00'
                ];
                $slots[] = [
                    'date' => $date,
                    'start' => '12:00:00',
                    'end' => '15:00:00'
                ];
                $slots[] = [
                    'date' => $date,
                    'start' => '15:00:00',
                    'end' => '18:00:00'
                ];
            }
            
            $current->modify('+1 day');
        }
        
        return $slots;
    }
    
    /**
     * Create a random chromosome
     */
    protected function createRandomChromosome(array $items, array $availableSlots): array
    {
        $chromosome = [];
        $scheduledCourses = []; // Track scheduled course dates for prerequisites
        
        foreach ($items as $index => $item) {
            // Filter slots based on prerequisites
            $validSlots = $this->filterSlotsForPrereqs($availableSlots, $item, $scheduledCourses);
            
            if (empty($validSlots)) {
                $validSlots = $availableSlots;
            }
            
            // Random slot
            $slotIndex = array_rand($validSlots);
            $slot = $validSlots[$slotIndex];
            
            $chromosome[$index] = [
                'item' => $item,
                'date' => $slot['date'],
                'start_time' => $slot['start'],
                'end_time' => $slot['end'],
                'room_id' => $this->getRandomRoom()['id']
            ];
            
            // Track for prerequisites
            $scheduledCourses[$item['course_id']] = $slot['date'];
        }
        
        return $chromosome;
    }
    
    /**
     * Filter slots to ensure prerequisites are scheduled first
     */
    private function filterSlotsForPrereqs(array $slots, array $item, array $scheduledCourses): array
    {
        $prereqIds = $item['prerequisites'];
        
        if (empty($prereqIds)) {
            return $slots;
        }
        
        // Find latest prerequisite date
        $latestPrereqDate = null;
        foreach ($prereqIds as $prereqId) {
            if (isset($scheduledCourses[$prereqId])) {
                $prereqDate = $scheduledCourses[$prereqId];
                if (!$latestPrereqDate || $prereqDate > $latestPrereqDate) {
                    $latestPrereqDate = $prereqDate;
                }
            }
        }
        
        if (!$latestPrereqDate) {
            return $slots;
        }
        
        // Filter to slots after latest prerequisite
        return array_filter($slots, function($slot) use ($latestPrereqDate) {
            return $slot['date'] > $latestPrereqDate;
        });
    }
    
    /**
     * Calculate penalty for a chromosome
     */
    protected function calculatePenalty(array $chromosome): float
    {
        $penalty = 0;
        
        // Build lookup structures
        $roomUsage = []; // [date][room_id][] = time range
        $streamExamDates = []; // [stream_id][] = date
        $courseExamDates = []; // [course_id] = date
        
        // First pass: collect all course exam dates
        foreach ($chromosome as $gene) {
            $courseExamDates[$gene['item']['course_id']] = $gene['date'];
        }
        
        foreach ($chromosome as $gene) {
            $item = $gene['item'];
            $date = $gene['date'];
            $start = $gene['start_time'];
            $end = $gene['end_time'];
            $roomId = $gene['room_id'];
            
            $timeRange = ['start' => $start, 'end' => $end, 'gene' => $gene];
            
            // Check room conflicts
            $roomUsage[$date][$roomId] = $roomUsage[$date][$roomId] ?? [];
            foreach ($roomUsage[$date][$roomId] as $existing) {
                if ($this->timesOverlap($start, $end, $existing['start'], $existing['end'])) {
                    $penalty += $this->hardConstraintPenalty['room_conflict'];
                }
            }
            $roomUsage[$date][$roomId][] = $timeRange;
            
            // Check prerequisite ordering (hard constraint)
            foreach ($item['prerequisites'] as $prereqId) {
                if (isset($courseExamDates[$prereqId])) {
                    $prereqDate = $courseExamDates[$prereqId];
                    if ($prereqDate >= $date) {
                        // Prerequisite scheduled on or after this course - major violation
                        $penalty += $this->hardConstraintPenalty['time_range_violation'] * 2;
                    }
                }
            }
            
            // Check stream exam spacing (soft constraint)
            if (!$item['is_elective'] && !empty($item['stream_id'])) {
                $streamId = $item['stream_id'];
                $streamExamDates[$streamId] = $streamExamDates[$streamId] ?? [];
                
                foreach ($streamExamDates[$streamId] as $prevDate) {
                    $daysDiff = abs((strtotime($date) - strtotime($prevDate)) / 86400);
                    
                    if ($daysDiff < self::MIN_DAYS_BETWEEN_EXAMS) {
                        // Exams too close together
                        $penalty += 50 * (self::MIN_DAYS_BETWEEN_EXAMS - $daysDiff);
                    }
                    
                    // Same day is a hard constraint for mandatory courses
                    if ($daysDiff === 0) {
                        $penalty += $this->hardConstraintPenalty['group_conflict'];
                    }
                }
                
                $streamExamDates[$streamId][] = $date;
            }
            
            // Check date is within session range
            if ($date < $this->sessionStartDate || $date > $this->sessionEndDate) {
                $penalty += $this->hardConstraintPenalty['time_range_violation'];
            }
            
            // Soft penalty for electives overlapping with mandatory
            if ($item['is_elective']) {
                foreach ($streamExamDates as $dates) {
                    foreach ($dates as $streamDate) {
                        if ($streamDate === $date) {
                            $penalty += $this->softConstraintPenalty['elective_conflict'];
                        }
                    }
                }
            }
        }
        
        return $penalty;
    }
    
    /**
     * Mutate a chromosome
     */
    protected function mutate(array $chromosome, array $availableSlots): array
    {
        // Build scheduled courses map
        $scheduledCourses = [];
        foreach ($chromosome as $gene) {
            $scheduledCourses[$gene['item']['course_id']] = $gene['date'];
        }
        
        foreach ($chromosome as $index => &$gene) {
            if (mt_rand() / mt_getrandmax() < $this->mutationRate) {
                $item = $gene['item'];
                
                // Filter to valid slots
                $validSlots = $this->filterSlotsForPrereqs($availableSlots, $item, $scheduledCourses);
                if (empty($validSlots)) {
                    $validSlots = $availableSlots;
                }
                
                // Randomly change date/time or room
                if (mt_rand(0, 1) === 0) {
                    $slotIndex = array_rand($validSlots);
                    $slot = $validSlots[$slotIndex];
                    
                    $gene['date'] = $slot['date'];
                    $gene['start_time'] = $slot['start'];
                    $gene['end_time'] = $slot['end'];
                    
                    // Update scheduled courses
                    $scheduledCourses[$item['course_id']] = $slot['date'];
                } else {
                    $gene['room_id'] = $this->getRandomRoom()['id'];
                }
            }
        }
        
        return $chromosome;
    }
    
    /**
     * Local repair using CSP
     */
    protected function localRepair(array $chromosome, array $availableSlots): array
    {
        // Build scheduled courses map
        $scheduledCourses = [];
        foreach ($chromosome as $gene) {
            $scheduledCourses[$gene['item']['course_id']] = $gene['date'];
        }
        
        $maxIterations = 50;
        $iteration = 0;
        
        while ($iteration < $maxIterations) {
            $violations = $this->findViolations($chromosome, $scheduledCourses);
            
            if (empty($violations)) {
                break;
            }
            
            // Fix first violation
            $violation = $violations[0];
            $index = $violation['index'];
            $item = $chromosome[$index]['item'];
            
            // Try different slots
            $validSlots = $this->filterSlotsForPrereqs($availableSlots, $item, $scheduledCourses);
            if (empty($validSlots)) {
                $validSlots = $availableSlots;
            }
            shuffle($validSlots);
            
            foreach ($validSlots as $slot) {
                $rooms = $this->rooms;
                shuffle($rooms);
                
                foreach ($rooms as $room) {
                    $testGene = [
                        'item' => $item,
                        'date' => $slot['date'],
                        'start_time' => $slot['start'],
                        'end_time' => $slot['end'],
                        'room_id' => $room['id']
                    ];
                    
                    $testChromosome = $chromosome;
                    $testChromosome[$index] = $testGene;
                    
                    $testScheduled = $scheduledCourses;
                    $testScheduled[$item['course_id']] = $slot['date'];
                    
                    if (!$this->hasViolationAt($testChromosome, $index, $testScheduled)) {
                        $chromosome[$index] = $testGene;
                        $scheduledCourses[$item['course_id']] = $slot['date'];
                        break 2;
                    }
                }
            }
            
            $iteration++;
        }
        
        return $chromosome;
    }
    
    /**
     * Find hard constraint violations
     */
    private function findViolations(array $chromosome, array $scheduledCourses): array
    {
        $violations = [];
        
        foreach ($chromosome as $index => $gene) {
            if ($this->hasViolationAt($chromosome, $index, $scheduledCourses)) {
                $violations[] = ['index' => $index, 'gene' => $gene];
            }
        }
        
        return $violations;
    }
    
    /**
     * Check if gene at index has hard constraint violation
     */
    private function hasViolationAt(array $chromosome, int $targetIndex, array $scheduledCourses): bool
    {
        $targetGene = $chromosome[$targetIndex];
        $item = $targetGene['item'];
        
        // Check date is within session range
        if ($targetGene['date'] < $this->sessionStartDate || $targetGene['date'] > $this->sessionEndDate) {
            return true;
        }
        
        // Check prerequisite ordering
        foreach ($item['prerequisites'] as $prereqId) {
            if (isset($scheduledCourses[$prereqId])) {
                if ($scheduledCourses[$prereqId] >= $targetGene['date']) {
                    return true;
                }
            }
        }
        
        foreach ($chromosome as $index => $gene) {
            if ($index === $targetIndex) continue;
            
            $otherItem = $gene['item'];
            
            // Same date?
            if ($gene['date'] !== $targetGene['date']) continue;
            
            // Times overlap?
            if (!$this->timesOverlap(
                $targetGene['start_time'], $targetGene['end_time'],
                $gene['start_time'], $gene['end_time']
            )) continue;
            
            // Room conflict
            if ($gene['room_id'] === $targetGene['room_id']) {
                return true;
            }
            
            // Same stream mandatory exams can't be same day
            if (!$item['is_elective'] && !$otherItem['is_elective']) {
                if (!empty($item['stream_id']) && $item['stream_id'] === $otherItem['stream_id']) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Save schedule items to database
     */
    protected function saveScheduleItems(int $variantId, array $items, string $type): void
    {
        foreach ($items as $gene) {
            $item = $gene['item'];
            
            $this->db->insert('exam_schedule', [
                'course_instance_id' => $item['instance_id'],
                'session_type' => $this->sessionType,
                'exam_date' => $gene['date'],
                'start_time' => $gene['start_time'],
                'end_time' => $gene['end_time'],
                'room_id' => $gene['room_id'],
                'stream_id' => $item['stream_id'],
                'variant_id' => $variantId
            ]);
        }
    }
}
