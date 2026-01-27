<?php
namespace App\Services\Scheduling;

use App\Models\AcademicSettings;
use App\Models\ScheduleVariant;
use App\Utils\Logger;

/**
 * Test Schedule Generator
 * 
 * Generates test schedules within allowed date ranges for each course.
 * Tests are scheduled on Saturdays or during specific allowed periods.
 */
class TestScheduleGenerator extends BaseScheduleGenerator
{
    private array $testRanges = [];
    private array $groupsByStream = [];
    private array $streamsByMajor = [];
    
    // Test time constraints
    private const TEST_START_HOUR = 8;
    private const TEST_END_HOUR = 20;
    
    /**
     * Generate test schedule variants
     */
    public function generate(int $year, string $semester): array
    {
        $this->logger->log(Logger::SCHEDULE_GENERATION_STARTED, [
            'type' => 'TEST',
            'year' => $year,
            'semester' => $semester
        ]);
        
        try {
            // Load data
            $this->loadCommonData($year, $semester);
            $this->loadTestData($year, $semester);
            
            // Delete old non-selected variants
            $this->deleteOldVariants(ScheduleVariant::TYPE_TEST, $year, $semester);
            
            // Generate items to schedule
            $items = $this->generateScheduleItems();
            
            if (empty($items)) {
                return [];
            }
            
            // Generate available slots based on test ranges
            $availableSlots = $this->generateTestSlots();
            
            // Run GA
            $solutions = $this->runGA($items, $availableSlots);
            
            // Save variants
            $variants = [];
            $names = ['A', 'B', 'C'];
            
            foreach ($solutions as $i => $solution) {
                $variant = $this->saveVariant(
                    ScheduleVariant::TYPE_TEST,
                    $year,
                    $semester,
                    'Тестове - Вариант ' . $names[$i],
                    $solution['fitness'],
                    $solution['chromosome']
                );
                $variants[] = $variant;
            }
            
            $this->logger->log(Logger::SCHEDULE_GENERATION_COMPLETED, [
                'type' => 'TEST',
                'variants_count' => count($variants)
            ]);
            
            return $variants;
            
        } catch (\Exception $e) {
            $this->logger->log(Logger::SCHEDULE_GENERATION_FAILED, [
                'type' => 'TEST',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Load test-specific data
     */
    private function loadTestData(int $year, string $semester): void
    {
        // Load course instances with their test ranges
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
        
        // Load test ranges
        $ranges = $this->db->fetchAll(
            "SELECT tr.* FROM test_range tr
             JOIN course_instance ci ON tr.course_instance_id = ci.id
             WHERE ci.academic_year = :year AND ci.semester = :semester",
            ['year' => $year, 'semester' => $semester]
        );
        
        foreach ($ranges as $range) {
            $this->testRanges[$range['course_instance_id']][] = $range;
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
     * Generate items to schedule (tests)
     */
    private function generateScheduleItems(): array
    {
        $items = [];
        
        foreach ($this->courseInstances as $instance) {
            $majorId = $instance['major_id'];
            $streams = $this->streamsByMajor[$majorId] ?? [];
            
            // Check if instance has test ranges defined
            if (empty($this->testRanges[$instance['id']])) {
                continue;
            }
            
            if ($instance['is_elective']) {
                // One test for all enrolled students
                $items[] = [
                    'instance_id' => $instance['id'],
                    'stream_id' => null,
                    'group_id' => null,
                    'major_id' => null,
                    'is_elective' => true,
                    'duration' => 2, // 2 hours default for tests
                    'test_ranges' => $this->testRanges[$instance['id']] ?? []
                ];
            } else {
                // One test per stream (all groups take it together or separately)
                foreach ($streams as $stream) {
                    $items[] = [
                        'instance_id' => $instance['id'],
                        'stream_id' => $stream['id'],
                        'group_id' => null,
                        'major_id' => $majorId,
                        'is_elective' => false,
                        'duration' => 2,
                        'test_ranges' => $this->testRanges[$instance['id']] ?? []
                    ];
                }
            }
        }
        
        return $items;
    }
    
    /**
     * Generate available test slots based on test ranges
     */
    private function generateTestSlots(): array
    {
        $slots = [];
        
        // Collect all unique dates from test ranges
        $allDates = [];
        foreach ($this->testRanges as $ranges) {
            foreach ($ranges as $range) {
                $start = new \DateTime($range['start_date']);
                $end = new \DateTime($range['end_date']);
                
                $current = clone $start;
                while ($current <= $end) {
                    $allDates[$current->format('Y-m-d')] = true;
                    $current->modify('+1 day');
                }
            }
        }
        
        // Generate time slots for each date
        foreach (array_keys($allDates) as $date) {
            for ($hour = self::TEST_START_HOUR; $hour < self::TEST_END_HOUR; $hour += 2) {
                $slots[] = [
                    'date' => $date,
                    'start' => sprintf('%02d:00:00', $hour),
                    'end' => sprintf('%02d:00:00', $hour + 2)
                ];
            }
        }
        
        return $slots;
    }
    
    /**
     * Create a random chromosome
     */
    protected function createRandomChromosome(array $items, array $availableSlots): array
    {
        $chromosome = [];
        
        foreach ($items as $index => $item) {
            // Filter slots to only those within this item's test ranges
            $validSlots = $this->filterSlotsForItem($availableSlots, $item);
            
            if (empty($validSlots)) {
                // Fallback: use any slot
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
        }
        
        return $chromosome;
    }
    
    /**
     * Filter slots to those within item's test ranges
     */
    private function filterSlotsForItem(array $slots, array $item): array
    {
        $testRanges = $item['test_ranges'] ?? [];
        
        if (empty($testRanges)) {
            return $slots;
        }
        
        return array_filter($slots, function($slot) use ($testRanges) {
            $slotDate = $slot['date'];
            
            foreach ($testRanges as $range) {
                if ($slotDate >= $range['start_date'] && $slotDate <= $range['end_date']) {
                    return true;
                }
            }
            
            return false;
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
        $groupUsage = []; // [date][group_id][] = time range
        $streamUsage = []; // [date][stream_id][] = time range
        
        foreach ($chromosome as $gene) {
            $item = $gene['item'];
            $date = $gene['date'];
            $start = $gene['start_time'];
            $end = $gene['end_time'];
            $roomId = $gene['room_id'];
            
            $timeRange = ['start' => $start, 'end' => $end, 'gene' => $gene];
            
            // Check if within allowed test range
            $withinRange = false;
            foreach ($item['test_ranges'] as $range) {
                if ($date >= $range['start_date'] && $date <= $range['end_date']) {
                    $withinRange = true;
                    break;
                }
            }
            if (!$withinRange && !empty($item['test_ranges'])) {
                $penalty += $this->hardConstraintPenalty['time_range_violation'];
            }
            
            // Check room conflicts
            $roomUsage[$date][$roomId] = $roomUsage[$date][$roomId] ?? [];
            foreach ($roomUsage[$date][$roomId] as $existing) {
                if ($this->timesOverlap($start, $end, $existing['start'], $existing['end'])) {
                    $penalty += $this->hardConstraintPenalty['room_conflict'];
                }
            }
            $roomUsage[$date][$roomId][] = $timeRange;
            
            // Check stream conflicts (students from same stream can't have overlapping tests)
            if (!$item['is_elective'] && !empty($item['stream_id'])) {
                $streamId = $item['stream_id'];
                $streamUsage[$date][$streamId] = $streamUsage[$date][$streamId] ?? [];
                
                foreach ($streamUsage[$date][$streamId] as $existing) {
                    if ($this->timesOverlap($start, $end, $existing['start'], $existing['end'])) {
                        $penalty += $this->hardConstraintPenalty['group_conflict'];
                    }
                }
                $streamUsage[$date][$streamId][] = $timeRange;
                
                // Also add to all groups in stream
                $groups = $this->groupsByStream[$streamId] ?? [];
                foreach ($groups as $group) {
                    $groupUsage[$date][$group['id']] = $groupUsage[$date][$group['id']] ?? [];
                    $groupUsage[$date][$group['id']][] = $timeRange;
                }
            }
            
            // Check elective conflicts (soft penalty for potential overlaps)
            if ($item['is_elective']) {
                // Soft penalty for overlapping with mandatory stream schedules
                foreach ($streamUsage[$date] ?? [] as $ranges) {
                    foreach ($ranges as $existing) {
                        if ($this->timesOverlap($start, $end, $existing['start'], $existing['end'])) {
                            $penalty += $this->softConstraintPenalty['elective_conflict'];
                        }
                    }
                }
            }
            
            // Prefer not to have more than one test per day for a group
            if (!$item['is_elective'] && !empty($item['stream_id'])) {
                $groups = $this->groupsByStream[$item['stream_id']] ?? [];
                foreach ($groups as $group) {
                    if (count($groupUsage[$date][$group['id']] ?? []) > 1) {
                        $penalty += 20; // Soft penalty for multiple tests same day
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
        foreach ($chromosome as $index => &$gene) {
            if (mt_rand() / mt_getrandmax() < $this->mutationRate) {
                $item = $gene['item'];
                
                // Filter to valid slots
                $validSlots = $this->filterSlotsForItem($availableSlots, $item);
                if (empty($validSlots)) {
                    $validSlots = $availableSlots;
                }
                
                // Randomly change date/time or room
                if (mt_rand(0, 1) === 0) {
                    // Change time slot
                    $slotIndex = array_rand($validSlots);
                    $slot = $validSlots[$slotIndex];
                    
                    $gene['date'] = $slot['date'];
                    $gene['start_time'] = $slot['start'];
                    $gene['end_time'] = $slot['end'];
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
        $maxIterations = 50;
        $iteration = 0;
        
        while ($iteration < $maxIterations) {
            $violations = $this->findViolations($chromosome, $availableSlots);
            
            if (empty($violations)) {
                break;
            }
            
            // Fix first violation
            $violation = $violations[0];
            $index = $violation['index'];
            $item = $chromosome[$index]['item'];
            
            // Try different slots
            $validSlots = $this->filterSlotsForItem($availableSlots, $item);
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
     * Find hard constraint violations
     */
    private function findViolations(array $chromosome, array $availableSlots): array
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
     * Check if gene at index has hard constraint violation
     */
    private function hasViolationAt(array $chromosome, int $targetIndex): bool
    {
        $targetGene = $chromosome[$targetIndex];
        $item = $targetGene['item'];
        
        // Check if within test range
        $withinRange = false;
        foreach ($item['test_ranges'] as $range) {
            if ($targetGene['date'] >= $range['start_date'] && 
                $targetGene['date'] <= $range['end_date']) {
                $withinRange = true;
                break;
            }
        }
        if (!$withinRange && !empty($item['test_ranges'])) {
            return true;
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
            
            // Stream conflict (same stream can't have overlapping tests)
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
            
            $this->db->insert('test_schedule', [
                'course_instance_id' => $item['instance_id'],
                'test_date' => $gene['date'],
                'start_time' => $gene['start_time'],
                'end_time' => $gene['end_time'],
                'room_id' => $gene['room_id'],
                'stream_id' => $item['stream_id'],
                'variant_id' => $variantId
            ]);
        }
    }
}
