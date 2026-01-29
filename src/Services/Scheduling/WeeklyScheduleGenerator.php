<?php
namespace App\Services\Scheduling;

use App\Models\AcademicSettings;
use App\Models\ScheduleVariant;
use App\Utils\Logger;

/**
 * Weekly Schedule Generator
 * 
 * Generates weekly schedules for lectures and exercises.
 * Mandatory courses Mon-Fri 08:00-18:00, electives can extend to 22:00.
 */
class WeeklyScheduleGenerator extends BaseScheduleGenerator
{
    private array $mandatoryInstances = [];
    private array $electiveInstances = [];
    private array $groupsByStream = [];
    private array $streamsByMajor = [];
    private array $instanceLecturers = [];
    private array $instanceAssistants = [];
    
    /**
     * Generate weekly schedule variants
     */
    public function generate(int $year, string $semester): array
    {
        $this->logger->log(Logger::SCHEDULE_GENERATION_STARTED, [
            'type' => 'WEEKLY',
            'year' => $year,
            'semester' => $semester
        ]);
        
        try {
            // Load data
            $this->loadCommonData($year, $semester);
            $this->loadWeeklyData($year, $semester);
            
            // Delete old non-selected variants
            $this->deleteOldVariants(ScheduleVariant::TYPE_WEEKLY, $year, $semester);
            
            // Generate items to schedule
            $items = $this->generateScheduleItems();
            
            // Generate available slots
            $mandatorySlots = $this->generateTimeSlots(8, 18, ['MON', 'TUE', 'WED', 'THU', 'FRI']);
            $electiveSlots = $this->generateTimeSlots(8, 22, ['MON', 'TUE', 'WED', 'THU', 'FRI']);
            
            $availableSlots = [
                'mandatory' => $mandatorySlots,
                'elective' => $electiveSlots
            ];
            
            // Run GA
            $solutions = $this->runGA($items, $availableSlots);
            
            // Save variants
            $variants = [];
            $names = ['A', 'B', 'C'];
            
            foreach ($solutions as $i => $solution) {
                $variant = $this->saveVariant(
                    ScheduleVariant::TYPE_WEEKLY,
                    $year,
                    $semester,
                    'Вариант ' . $names[$i],
                    $solution['fitness'],
                    $solution['chromosome']
                );
                $variants[] = $variant;
            }
            
            $this->logger->log(Logger::SCHEDULE_GENERATION_COMPLETED, [
                'type' => 'WEEKLY',
                'variants_count' => count($variants)
            ]);
            
            return $variants;
            
        } catch (\Exception $e) {
            $this->logger->log(Logger::SCHEDULE_GENERATION_FAILED, [
                'type' => 'WEEKLY',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Load weekly-specific data
     */
    private function loadWeeklyData(int $year, string $semester): void
    {
        // Load course instances with course info
        $instances = $this->db->fetchAll(
            "SELECT ci.*, c.name_bg, c.code, c.is_elective, c.major_id, c.year as course_year
             FROM course_instance ci
             JOIN course c ON ci.course_id = c.id
             WHERE ci.academic_year = :year AND ci.semester = :semester",
            ['year' => $year, 'semester' => $semester]
        );
        
        foreach ($instances as $instance) {
            if ($instance['is_elective']) {
                $this->electiveInstances[$instance['id']] = $instance;
            } else {
                $this->mandatoryInstances[$instance['id']] = $instance;
            }
            
            // Load lecturers
            $this->instanceLecturers[$instance['id']] = $this->db->fetchAll(
                "SELECT user_id FROM course_lecturer WHERE course_instance_id = :id",
                ['id' => $instance['id']]
            );
            $this->instanceLecturers[$instance['id']] = array_column(
                $this->instanceLecturers[$instance['id']], 'user_id'
            );
            
            // Load assistants
            $this->instanceAssistants[$instance['id']] = $this->db->fetchAll(
                "SELECT user_id FROM course_assistant WHERE course_instance_id = :id",
                ['id' => $instance['id']]
            );
            $this->instanceAssistants[$instance['id']] = array_column(
                $this->instanceAssistants[$instance['id']], 'user_id'
            );
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
     * Generate items to be scheduled
     * Each item represents a lecture or exercise slot
     */
    private function generateScheduleItems(): array
    {
        $items = [];
        
        // Mandatory courses - schedule for each stream/group
        foreach ($this->mandatoryInstances as $instance) {
            $majorId = $instance['major_id'];
            $courseYear = $instance['course_year'];
            $streams = $this->streamsByMajor[$majorId] ?? [];
            
            foreach ($streams as $stream) {
                // One lecture per week for the whole stream
                $items[] = [
                    'type' => 'lecture',
                    'instance_id' => $instance['id'],
                    'stream_id' => $stream['id'],
                    'major_id' => $majorId,
                    'course_year' => $courseYear,
                    'is_elective' => false,
                    'duration' => $instance['lecture_duration_hours'],
                    'lecturers' => $this->instanceLecturers[$instance['id']] ?? []
                ];
                
                // Exercises per group
                $groups = $this->groupsByStream[$stream['id']] ?? [];
                $exerciseCount = $instance['exercise_count_per_week'];
                $assistants = $this->instanceAssistants[$instance['id']] ?? [];
                
                foreach ($groups as $group) {
                    for ($ex = 0; $ex < $exerciseCount; $ex++) {
                        // Assign assistant round-robin
                        $assistantId = !empty($assistants) 
                            ? $assistants[($ex + $group['id']) % count($assistants)]
                            : null;
                        
                        $items[] = [
                            'type' => 'exercise',
                            'instance_id' => $instance['id'],
                            'stream_id' => $stream['id'],
                            'group_id' => $group['id'],
                            'major_id' => $majorId,
                            'course_year' => $courseYear,
                            'is_elective' => false,
                            'duration' => $instance['exercise_duration_hours'],
                            'assistant_id' => $assistantId
                        ];
                    }
                }
            }
        }
        
        // Elective courses - global scheduling
        foreach ($this->electiveInstances as $instance) {
            // One lecture per week
            $items[] = [
                'type' => 'lecture',
                'instance_id' => $instance['id'],
                'stream_id' => null,
                'major_id' => null,
                'course_year' => null,
                'is_elective' => true,
                'duration' => $instance['lecture_duration_hours'],
                'lecturers' => $this->instanceLecturers[$instance['id']] ?? []
            ];
            
            // Exercises (no specific group for electives)
            $exerciseCount = $instance['exercise_count_per_week'];
            $assistants = $this->instanceAssistants[$instance['id']] ?? [];
            
            for ($ex = 0; $ex < $exerciseCount; $ex++) {
                $assistantId = !empty($assistants) ? $assistants[$ex % count($assistants)] : null;
                
                $items[] = [
                    'type' => 'exercise',
                    'instance_id' => $instance['id'],
                    'stream_id' => null,
                    'group_id' => null,
                    'major_id' => null,
                    'course_year' => null,
                    'is_elective' => true,
                    'duration' => $instance['exercise_duration_hours'],
                    'assistant_id' => $assistantId
                ];
            }
        }
        
        return $items;
    }
    
    /**
     * Create a random chromosome
     * Note: Items are NOT shuffled to ensure all chromosomes have the same item at each index.
     * This is required for crossover to work correctly (swapping genes at same index).
     * Randomness comes from time slot and room selection.
     */
    protected function createRandomChromosome(array $items, array $availableSlots): array
    {
        $chromosome = [];
        
        foreach ($items as $index => $item) {
            $slots = $item['is_elective'] ? $availableSlots['elective'] : $availableSlots['mandatory'];
            
            // Random slot
            $slotIndex = array_rand($slots);
            $slot = $slots[$slotIndex];
            
            // Extend slot for duration
            $startHour = (int) substr($slot['start'], 0, 2);
            $endHour = $startHour + $item['duration'];
            
            $chromosome[$index] = [
                'item' => $item,
                'day' => $slot['day'],
                'start_time' => sprintf('%02d:00:00', $startHour),
                'end_time' => sprintf('%02d:00:00', $endHour),
                'room_id' => $this->getRandomRoom()['id']
            ];
        }
        
        return $chromosome;
    }
    
    /**
     * Calculate penalty for a chromosome
     */
    protected function calculatePenalty(array $chromosome): float
    {
        $penalty = 0;
        
        // Build lookup structures
        $roomUsage = []; // [day][room_id][] = time range
        $lecturerUsage = []; // [day][user_id][] = time range
        $groupUsage = []; // [day][group_id][] = time range
        $streamUsage = []; // [day][stream_id][] = time range (for lectures)
        $yearMajorUsage = []; // [day][major_id][course_year][] = time range (for year conflicts)
        
        foreach ($chromosome as $gene) {
            $item = $gene['item'];
            $day = $gene['day'];
            $start = $gene['start_time'];
            $end = $gene['end_time'];
            $roomId = $gene['room_id'];
            
            $timeRange = ['start' => $start, 'end' => $end, 'gene' => $gene];
            
            // Check room conflicts
            $roomUsage[$day][$roomId] = $roomUsage[$day][$roomId] ?? [];
            foreach ($roomUsage[$day][$roomId] as $existing) {
                if ($this->timesOverlap($start, $end, $existing['start'], $existing['end'])) {
                    $penalty += $this->hardConstraintPenalty['room_conflict'];
                }
            }
            $roomUsage[$day][$roomId][] = $timeRange;
            
            // Check lecturer conflicts
            if ($item['type'] === 'lecture' && !empty($item['lecturers'])) {
                foreach ($item['lecturers'] as $lecturerId) {
                    $lecturerUsage[$day][$lecturerId] = $lecturerUsage[$day][$lecturerId] ?? [];
                    foreach ($lecturerUsage[$day][$lecturerId] as $existing) {
                        if ($this->timesOverlap($start, $end, $existing['start'], $existing['end'])) {
                            $penalty += $this->hardConstraintPenalty['lecturer_conflict'];
                        }
                    }
                    $lecturerUsage[$day][$lecturerId][] = $timeRange;
                    
                    // Check preferences
                    $room = $this->rooms[array_search($roomId, array_column($this->rooms, 'id'))] ?? [];
                    $penalty += $this->checkPreferences($lecturerId, true, (int)substr($start, 0, 2), $room);
                }
            }
            
            // Check assistant conflicts
            if ($item['type'] === 'exercise' && !empty($item['assistant_id'])) {
                $assistantId = $item['assistant_id'];
                $lecturerUsage[$day][$assistantId] = $lecturerUsage[$day][$assistantId] ?? [];
                foreach ($lecturerUsage[$day][$assistantId] as $existing) {
                    if ($this->timesOverlap($start, $end, $existing['start'], $existing['end'])) {
                        $penalty += $this->hardConstraintPenalty['lecturer_conflict'];
                    }
                }
                $lecturerUsage[$day][$assistantId][] = $timeRange;
                
                // Check if assistant is a student and has own classes
                // (This would require loading student schedule - simplified here)
                
                // Check preferences
                $room = $this->rooms[array_search($roomId, array_column($this->rooms, 'id'))] ?? [];
                $penalty += $this->checkPreferences($assistantId, false, (int)substr($start, 0, 2), $room);
            }
            
            // Check year-major conflicts (mandatory courses in same major+year should not overlap)
            if (!$item['is_elective'] && !empty($item['major_id']) && !empty($item['course_year'])) {
                $majorId = $item['major_id'];
                $courseYear = $item['course_year'];
                
                $yearMajorUsage[$day][$majorId][$courseYear] = $yearMajorUsage[$day][$majorId][$courseYear] ?? [];
                foreach ($yearMajorUsage[$day][$majorId][$courseYear] as $existing) {
                    if ($this->timesOverlap($start, $end, $existing['start'], $existing['end'])) {
                        // Same major, same year, overlapping times = conflict
                        $penalty += $this->hardConstraintPenalty['group_conflict'];
                    }
                }
                $yearMajorUsage[$day][$majorId][$courseYear][] = $timeRange;
            }
            
            // Check group conflicts (for mandatory courses)
            if (!$item['is_elective'] && !empty($item['group_id'])) {
                $groupId = $item['group_id'];
                $groupUsage[$day][$groupId] = $groupUsage[$day][$groupId] ?? [];
                foreach ($groupUsage[$day][$groupId] as $existing) {
                    if ($this->timesOverlap($start, $end, $existing['start'], $existing['end'])) {
                        $penalty += $this->hardConstraintPenalty['group_conflict'];
                    }
                }
                $groupUsage[$day][$groupId][] = $timeRange;
            }
            
            // Check stream conflicts (for lectures)
            if (!$item['is_elective'] && $item['type'] === 'lecture' && !empty($item['stream_id'])) {
                $streamId = $item['stream_id'];
                $streamUsage[$day][$streamId] = $streamUsage[$day][$streamId] ?? [];
                foreach ($streamUsage[$day][$streamId] as $existing) {
                    if ($this->timesOverlap($start, $end, $existing['start'], $existing['end'])) {
                        $penalty += $this->hardConstraintPenalty['group_conflict'];
                    }
                }
                $streamUsage[$day][$streamId][] = $timeRange;
                
                // Add to all groups in stream
                $groups = $this->groupsByStream[$streamId] ?? [];
                foreach ($groups as $group) {
                    $groupUsage[$day][$group['id']] = $groupUsage[$day][$group['id']] ?? [];
                    $groupUsage[$day][$group['id']][] = $timeRange;
                }
            }
            
            // Check elective conflicts (soft constraint - minimize overlap)
            if ($item['is_elective']) {
                // Count overlaps with other electives and mandatory
                // (Simplified - would need to check enrolled students)
                foreach ($streamUsage as $dayData) {
                    foreach ($dayData as $ranges) {
                        foreach ($ranges as $existing) {
                            if ($this->timesOverlap($start, $end, $existing['start'], $existing['end'])) {
                                $penalty += $this->softConstraintPenalty['elective_conflict'];
                            }
                        }
                    }
                }
            }
            
            // Check time range (mandatory within 08-18)
            if (!$item['is_elective']) {
                $startHour = (int) substr($start, 0, 2);
                $endHour = (int) substr($end, 0, 2);
                if ($startHour < 8 || $endHour > 18) {
                    $penalty += $this->hardConstraintPenalty['time_range_violation'];
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
        foreach ($chromosome as $index => &$gene) {
            if (mt_rand() / mt_getrandmax() < $this->mutationRate) {
                $item = $gene['item'];
                $slots = $item['is_elective'] ? $availableSlots['elective'] : $availableSlots['mandatory'];
                
                // Randomly change day/time or room
                if (mt_rand(0, 1) === 0) {
                    // Change time slot
                    $slotIndex = array_rand($slots);
                    $slot = $slots[$slotIndex];
                    $startHour = (int) substr($slot['start'], 0, 2);
                    $endHour = $startHour + $item['duration'];
                    
                    $gene['day'] = $slot['day'];
                    $gene['start_time'] = sprintf('%02d:00:00', $startHour);
                    $gene['end_time'] = sprintf('%02d:00:00', $endHour);
                } else {
                    // Change room
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
        // Try to fix hard constraint violations
        $maxIterations = 100;
        $iteration = 0;
        
        while ($iteration < $maxIterations) {
            $violations = $this->findViolations($chromosome);
            
            if (empty($violations)) {
                break;
            }
            
            // Fix first violation
            $violation = $violations[0];
            $index = $violation['index'];
            $item = $chromosome[$index]['item'];
            
            // Try different slots
            $slots = $item['is_elective'] ? $availableSlots['elective'] : $availableSlots['mandatory'];
            shuffle($slots);
            
            foreach ($slots as $slot) {
                $startHour = (int) substr($slot['start'], 0, 2);
                $endHour = $startHour + $item['duration'];
                
                // Try different rooms
                $rooms = $this->rooms;
                shuffle($rooms);
                
                foreach ($rooms as $room) {
                    $testGene = [
                        'item' => $item,
                        'day' => $slot['day'],
                        'start_time' => sprintf('%02d:00:00', $startHour),
                        'end_time' => sprintf('%02d:00:00', $endHour),
                        'room_id' => $room['id']
                    ];
                    
                    $testChromosome = $chromosome;
                    $testChromosome[$index] = $testGene;
                    
                    if (!$this->hasViolationAt($testChromosome, $index)) {
                        $chromosome[$index] = $testGene;
                        break 2;
                    }
                }
            }
            
            $iteration++;
        }
        
        return $chromosome;
    }
    
    /**
     * Find all hard constraint violations
     */
    private function findViolations(array $chromosome): array
    {
        $violations = [];
        
        foreach ($chromosome as $index => $gene) {
            if ($this->hasViolationAt($chromosome, $index)) {
                $violations[] = ['index' => $index, 'gene' => $gene];
            }
        }
        
        return $violations;
    }
    
    /**
     * Check if gene at index has a hard constraint violation
     */
    private function hasViolationAt(array $chromosome, int $targetIndex): bool
    {
        $targetGene = $chromosome[$targetIndex];
        $item = $targetGene['item'];
        
        foreach ($chromosome as $index => $gene) {
            if ($index === $targetIndex) continue;
            
            $otherItem = $gene['item'];
            
            // Same day?
            if ($gene['day'] !== $targetGene['day']) continue;
            
            // Times overlap?
            if (!$this->timesOverlap(
                $targetGene['start_time'], $targetGene['end_time'],
                $gene['start_time'], $gene['end_time']
            )) continue;
            
            // Room conflict
            if ($gene['room_id'] === $targetGene['room_id']) {
                return true;
            }
            
            // Lecturer conflict
            if ($item['type'] === 'lecture' && $otherItem['type'] === 'lecture') {
                $lecturers1 = $item['lecturers'] ?? [];
                $lecturers2 = $otherItem['lecturers'] ?? [];
                if (array_intersect($lecturers1, $lecturers2)) {
                    return true;
                }
            }
            
            // Assistant conflict
            if (!empty($item['assistant_id']) && !empty($otherItem['assistant_id'])) {
                if ($item['assistant_id'] === $otherItem['assistant_id']) {
                    return true;
                }
            }
            
            // Group conflict (for mandatory)
            if (!$item['is_elective'] && !$otherItem['is_elective']) {
                if (!empty($item['group_id']) && !empty($otherItem['group_id'])) {
                    if ($item['group_id'] === $otherItem['group_id']) {
                        return true;
                    }
                }
                
                // Stream lecture conflicts with group exercises
                if ($item['type'] === 'lecture' && !empty($item['stream_id'])) {
                    if (!empty($otherItem['group_id'])) {
                        $group = $this->groups[$otherItem['group_id']] ?? null;
                        if ($group && $group['stream_id'] === $item['stream_id']) {
                            return true;
                        }
                    }
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
            
            $this->db->insert('weekly_slot', [
                'course_instance_id' => $item['instance_id'],
                'day_of_week' => $gene['day'],
                'start_time' => $gene['start_time'],
                'end_time' => $gene['end_time'],
                'room_id' => $gene['room_id'],
                'slot_type' => strtoupper($item['type']),
                'group_id' => $item['group_id'] ?? null,
                'assistant_id' => $item['assistant_id'] ?? null,
                'variant_id' => $variantId
            ]);
        }
    }
}
