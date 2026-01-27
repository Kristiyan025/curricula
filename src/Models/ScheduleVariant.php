<?php
namespace App\Models;

/**
 * ScheduleVariant Model (Вариант на разписание)
 */
class ScheduleVariant extends Model
{
    protected static string $table = 'schedule_variant';
    
    const TYPE_WEEKLY = 'WEEKLY';
    const TYPE_TEST = 'TEST';
    const TYPE_EXAM = 'EXAM';
    
    /**
     * Check if this variant is selected
     */
    public function isSelected(): bool
    {
        return (bool) $this->is_selected;
    }
    
    /**
     * Mark this variant as the selected variant
     */
    public function selectVariant(): void
    {
        // First, deselect all other variants of the same type
        $sessionType = $this->session_type;
        if ($sessionType === null) {
            $this->db->execute(
                "UPDATE schedule_variant 
                 SET is_selected = 0 
                 WHERE type = :type 
                   AND semester = :semester 
                   AND academic_year = :year
                   AND session_type IS NULL",
                [
                    'type' => $this->type,
                    'semester' => $this->semester,
                    'year' => $this->academic_year
                ]
            );
        } else {
            $this->db->execute(
                "UPDATE schedule_variant 
                 SET is_selected = 0 
                 WHERE type = :type 
                   AND semester = :semester 
                   AND academic_year = :year
                   AND session_type = :session",
                [
                    'type' => $this->type,
                    'semester' => $this->semester,
                    'year' => $this->academic_year,
                    'session' => $sessionType
                ]
            );
        }
        
        // Mark this one as selected
        $this->is_selected = 1;
        $this->selected_at = date('Y-m-d H:i:s');
        $this->save();
        
        // Move schedule items from variant to selected (set variant_id to NULL)
        $this->promoteScheduleItems();
    }
    
    /**
     * Promote schedule items from variant to selected
     */
    private function promoteScheduleItems(): void
    {
        switch ($this->type) {
            case self::TYPE_WEEKLY:
                $this->db->execute(
                    "UPDATE weekly_slot SET variant_id = NULL WHERE variant_id = :variant_id",
                    ['variant_id' => $this->id]
                );
                break;
            case self::TYPE_TEST:
                $this->db->execute(
                    "UPDATE test_schedule SET variant_id = NULL WHERE variant_id = :variant_id",
                    ['variant_id' => $this->id]
                );
                break;
            case self::TYPE_EXAM:
                $this->db->execute(
                    "UPDATE exam_schedule SET variant_id = NULL WHERE variant_id = :variant_id",
                    ['variant_id' => $this->id]
                );
                break;
        }
    }
    
    /**
     * Delete variant and its schedule items
     */
    public function delete(): bool
    {
        // Delete associated schedule items first
        switch ($this->type) {
            case self::TYPE_WEEKLY:
                $this->db->delete('weekly_slot', 'variant_id = :variant_id', ['variant_id' => $this->id]);
                break;
            case self::TYPE_TEST:
                $this->db->delete('test_schedule', 'variant_id = :variant_id', ['variant_id' => $this->id]);
                break;
            case self::TYPE_EXAM:
                $this->db->delete('exam_schedule', 'variant_id = :variant_id', ['variant_id' => $this->id]);
                break;
        }
        
        return parent::delete();
    }
    
    /**
     * Get variants by type and semester
     */
    public static function getVariants(string $type, int $year, string $semester, ?string $sessionType = null): array
    {
        $db = \App\Core\Application::getInstance()->getDb();
        
        $sql = "SELECT * FROM schedule_variant 
                WHERE type = :type 
                  AND academic_year = :year 
                  AND semester = :semester";
        
        $params = [
            'type' => $type,
            'year' => $year,
            'semester' => $semester
        ];
        
        if ($sessionType !== null) {
            $sql .= " AND session_type = :session";
            $params['session'] = $sessionType;
        } else if ($type === self::TYPE_WEEKLY || $type === self::TYPE_TEST) {
            $sql .= " AND session_type IS NULL";
        }
        
        $sql .= " ORDER BY name";
        
        $rows = $db->fetchAll($sql, $params);
        
        return array_map(fn($row) => new self($row), $rows);
    }
    
    /**
     * Get selected variant
     */
    public static function getSelected(string $type, int $year, string $semester, ?string $sessionType = null): ?self
    {
        $db = \App\Core\Application::getInstance()->getDb();
        
        $sql = "SELECT * FROM schedule_variant 
                WHERE type = :type 
                  AND academic_year = :year 
                  AND semester = :semester
                  AND is_selected = 1";
        
        $params = [
            'type' => $type,
            'year' => $year,
            'semester' => $semester
        ];
        
        if ($sessionType !== null) {
            $sql .= " AND session_type = :session";
            $params['session'] = $sessionType;
        } else {
            $sql .= " AND session_type IS NULL";
        }
        
        $row = $db->fetchOne($sql, $params);
        
        return $row ? new self($row) : null;
    }
    
    /**
     * Delete non-selected variants
     */
    public static function deleteNonSelected(string $type, int $year, string $semester, ?string $sessionType = null): void
    {
        $variants = self::getVariants($type, $year, $semester, $sessionType);
        
        foreach ($variants as $variant) {
            if (!$variant->isSelected()) {
                $variant->delete();
            }
        }
    }
    
    /**
     * Create a new variant
     */
    public static function createVariant(string $type, int $year, string $semester, string $name, ?string $sessionType = null, ?float $fitnessScore = null): self
    {
        $variant = new self([
            'type' => $type,
            'academic_year' => $year,
            'semester' => $semester,
            'session_type' => $sessionType,
            'name' => $name,
            'fitness_score' => $fitnessScore,
            'is_selected' => 0
        ]);
        
        $variant->save();
        
        return $variant;
    }
}
