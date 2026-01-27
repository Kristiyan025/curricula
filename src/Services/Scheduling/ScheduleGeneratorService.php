<?php
namespace App\Services\Scheduling;

use App\Models\ScheduleVariant;
use App\Utils\Logger;

/**
 * Schedule Generator Service
 * 
 * Main entry point for schedule generation.
 * Coordinates generation of all schedule types.
 */
class ScheduleGeneratorService
{
    private WeeklyScheduleGenerator $weeklyGenerator;
    private TestScheduleGenerator $testGenerator;
    private ExamScheduleGenerator $examGenerator;
    
    public function __construct()
    {
        $this->weeklyGenerator = new WeeklyScheduleGenerator();
        $this->testGenerator = new TestScheduleGenerator();
        $this->examGenerator = new ExamScheduleGenerator();
    }
    
    /**
     * Generate weekly schedule variants
     */
    public function generateWeeklySchedule(int $year, string $semester): array
    {
        return $this->weeklyGenerator->generate($year, $semester);
    }
    
    /**
     * Generate test schedule variants
     */
    public function generateTestSchedule(int $year, string $semester): array
    {
        return $this->testGenerator->generate($year, $semester);
    }
    
    /**
     * Generate exam schedule variants
     */
    public function generateExamSchedule(int $year, string $semester, string $sessionType = 'REGULAR'): array
    {
        return $this->examGenerator->generate($year, $semester, $sessionType);
    }
    
    /**
     * Generate all schedule types for a semester
     */
    public function generateAllSchedules(int $year, string $semester): array
    {
        $results = [
            'weekly' => [],
            'test' => [],
            'exam_regular' => [],
            'exam_liquidation' => []
        ];
        
        // Generate in sequence
        $results['weekly'] = $this->generateWeeklySchedule($year, $semester);
        $results['test'] = $this->generateTestSchedule($year, $semester);
        $results['exam_regular'] = $this->generateExamSchedule($year, $semester, 'REGULAR');
        $results['exam_liquidation'] = $this->generateExamSchedule($year, $semester, 'LIQUIDATION');
        
        return $results;
    }
    
    /**
     * Get available variants for selection
     */
    public function getVariants(string $type, int $year, string $semester, ?string $sessionType = null): array
    {
        return ScheduleVariant::getVariants($type, $year, $semester, $sessionType);
    }
    
    /**
     * Select a variant to be active
     */
    public function selectVariant(int $variantId): bool
    {
        /** @var ScheduleVariant|null $variant */
        $variant = ScheduleVariant::find($variantId);
        
        if (!$variant) {
            return false;
        }
        
        $variant->selectVariant();
        return true;
    }
    
    /**
     * Get the currently selected variant for a schedule type
     */
    public function getSelectedVariant(string $type, int $year, string $semester, ?string $sessionType = null): ?ScheduleVariant
    {
        $variants = ScheduleVariant::getVariants($type, $year, $semester, $sessionType);
        
        foreach ($variants as $variant) {
            if ($variant->isSelected()) {
                return $variant;
            }
        }
        
        return null;
    }
    
    /**
     * Delete a variant (only non-selected variants can be deleted)
     */
    public function deleteVariant(int $variantId): bool
    {
        $variant = ScheduleVariant::find($variantId);
        
        if (!$variant) {
            return false;
        }
        
        if ($variant->isSelected()) {
            return false;
        }
        
        return $variant->delete();
    }
    
    /**
     * Get generation status/history
     */
    public function getGenerationHistory(int $limit = 10): array
    {
        $db = \App\Core\Application::getInstance()->getDb();
        
        return $db->fetchAll(
            "SELECT sv.*, 
                    (SELECT COUNT(*) FROM weekly_slot WHERE variant_id = sv.id) as weekly_count,
                    (SELECT COUNT(*) FROM test_schedule WHERE variant_id = sv.id) as test_count,
                    (SELECT COUNT(*) FROM exam_schedule WHERE variant_id = sv.id) as exam_count
             FROM schedule_variant sv
             ORDER BY sv.created_at DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
}
