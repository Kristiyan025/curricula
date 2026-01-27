<?php
namespace App\Models;

/**
 * AcademicSettings Model (Настройки за учебна година)
 */
class AcademicSettings extends Model
{
    protected static string $table = 'academic_settings';
    
    // Phase constants
    const PHASE_WINTER_SEMESTER = 'WINTER_SEMESTER';
    const PHASE_WINTER_EXAM = 'WINTER_EXAM';
    const PHASE_SUMMER_SEMESTER = 'SUMMER_SEMESTER';
    const PHASE_SUMMER_EXAM = 'SUMMER_EXAM';
    const PHASE_RESIT = 'RESIT';
    
    /**
     * Get a setting value
     */
    public static function get(string $key, $default = null): ?string
    {
        $db = \App\Core\Application::getInstance()->getDb();
        
        $value = $db->fetchColumn(
            "SELECT setting_value FROM academic_settings WHERE setting_key = :key",
            ['key' => $key]
        );
        
        return $value !== false ? $value : $default;
    }
    
    /**
     * Set a setting value
     */
    public static function set(string $key, string $value): void
    {
        $db = \App\Core\Application::getInstance()->getDb();
        
        $existing = $db->fetchOne(
            "SELECT id FROM academic_settings WHERE setting_key = :key",
            ['key' => $key]
        );
        
        if ($existing) {
            $db->update('academic_settings', 
                ['setting_value' => $value], 
                'setting_key = :key',
                ['key' => $key]
            );
        } else {
            $db->insert('academic_settings', [
                'setting_key' => $key,
                'setting_value' => $value
            ]);
        }
    }
    
    /**
     * Get current academic phase
     */
    public static function getCurrentPhase(): string
    {
        return self::get('current_phase', self::PHASE_WINTER_SEMESTER);
    }
    
    /**
     * Set current academic phase
     */
    public static function setCurrentPhase(string $phase): void
    {
        self::set('current_phase', $phase);
    }
    
    /**
     * Get current academic year
     */
    public static function getCurrentYear(): int
    {
        return (int) self::get('academic_year', date('Y'));
    }
    
    /**
     * Set current academic year
     */
    public static function setCurrentYear(int $year): void
    {
        self::set('academic_year', (string) $year);
    }
    
    /**
     * Get current semester based on phase
     */
    public static function getCurrentSemester(): string
    {
        $phase = self::getCurrentPhase();
        
        if (in_array($phase, [self::PHASE_WINTER_SEMESTER, self::PHASE_WINTER_EXAM])) {
            return 'WINTER';
        }
        
        return 'SUMMER';
    }
    
    /**
     * Check if currently in exam session
     */
    public static function isExamSession(): bool
    {
        $phase = self::getCurrentPhase();
        return in_array($phase, [self::PHASE_WINTER_EXAM, self::PHASE_SUMMER_EXAM, self::PHASE_RESIT]);
    }
    
    /**
     * Get session type for exam schedules
     */
    public static function getSessionType(): ?string
    {
        $phase = self::getCurrentPhase();
        
        switch ($phase) {
            case self::PHASE_WINTER_EXAM:
                return 'WINTER_EXAM';
            case self::PHASE_SUMMER_EXAM:
                return 'SUMMER_EXAM';
            case self::PHASE_RESIT:
                return 'RESIT';
            default:
                return null;
        }
    }
    
    /**
     * Get phase dates
     */
    public static function getPhaseDates(string $phase, int $year): array
    {
        switch ($phase) {
            case self::PHASE_WINTER_SEMESTER:
                return [
                    'start' => "{$year}-10-01",
                    'end' => ($year + 1) . "-01-15"
                ];
            case self::PHASE_WINTER_EXAM:
                return [
                    'start' => ($year + 1) . "-01-16",
                    'end' => ($year + 1) . "-02-15"
                ];
            case self::PHASE_SUMMER_SEMESTER:
                return [
                    'start' => ($year + 1) . "-02-16",
                    'end' => ($year + 1) . "-06-15"
                ];
            case self::PHASE_SUMMER_EXAM:
                return [
                    'start' => ($year + 1) . "-06-16",
                    'end' => ($year + 1) . "-07-15"
                ];
            case self::PHASE_RESIT:
                return [
                    'start' => ($year + 1) . "-08-15",
                    'end' => ($year + 1) . "-09-15"
                ];
            default:
                return ['start' => null, 'end' => null];
        }
    }
    
    /**
     * Get all phases with labels
     */
    public static function getAllPhases(): array
    {
        return [
            self::PHASE_WINTER_SEMESTER => 'Зимен семестър',
            self::PHASE_WINTER_EXAM => 'Зимна изпитна сесия',
            self::PHASE_SUMMER_SEMESTER => 'Летен семестър',
            self::PHASE_SUMMER_EXAM => 'Лятна изпитна сесия',
            self::PHASE_RESIT => 'Поправителна сесия',
        ];
    }
    
    /**
     * Get phase label in Bulgarian
     */
    public static function getPhaseLabel(string $phase): string
    {
        $phases = self::getAllPhases();
        return $phases[$phase] ?? $phase;
    }
    
    /**
     * Get current settings as object
     */
    public static function getCurrentSettings(): ?object
    {
        $db = \App\Core\Application::getInstance()->getDb();
        
        $settings = $db->fetchAll("SELECT setting_key, setting_value FROM academic_settings");
        
        if (empty($settings)) {
            return (object) [
                'academic_year' => date('Y'),
                'current_phase' => self::PHASE_WINTER_SEMESTER,
                'semester' => 'WINTER'
            ];
        }
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }
        
        // Add computed properties
        $result['semester'] = self::getCurrentSemester();
        
        return (object) $result;
    }
}
