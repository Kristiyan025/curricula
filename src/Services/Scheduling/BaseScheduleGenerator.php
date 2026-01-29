<?php
namespace App\Services\Scheduling;

use App\Core\Application;
use App\Models\AcademicSettings;
use App\Models\ScheduleVariant;
use App\Utils\Logger;

/**
 * Base Schedule Generator
 * 
 * Implements hybrid GA/CSP algorithm for schedule generation.
 * Uses Genetic Algorithm for global optimization and CSP for constraint satisfaction.
 */
abstract class BaseScheduleGenerator
{
    protected $db;
    protected $logger;
    protected $config;
    
    // GA parameters
    protected int $populationSize = 50;
    protected int $maxGenerations = 500;
    protected float $mutationRate = 0.1;
    protected float $crossoverRate = 0.8;
    protected int $elitismCount = 5;
    
    // Time limit (seconds)
    protected int $maxTime = 1200; // 20 minutes
    
    // Penalty weights for fitness function
    protected array $hardConstraintPenalty = [
        'room_conflict' => 1000,
        'lecturer_conflict' => 1000,
        'group_conflict' => 1000,
        'time_range_violation' => 1000,
    ];
    
    protected array $softConstraintPenalty = [
        'preference_morning' => 10,
        'preference_afternoon' => 10,
        'preference_whiteboard' => 5,
        'preference_no_blackboard' => 5,
        'elective_conflict' => 50,
    ];
    
    // Data caches
    protected array $rooms = [];
    protected array $courseInstances = [];
    protected array $groups = [];
    protected array $lecturerPreferences = [];
    protected array $assistantPreferences = [];
    
    public function __construct()
    {
        $app = Application::getInstance();
        $this->db = $app->getDb();
        $this->logger = $app->getLogger();
        $this->config = $app->getConfig('schedule');
        
        // Override defaults with config
        if ($this->config) {
            $this->populationSize = $this->config['population_size'] ?? $this->populationSize;
            $this->maxGenerations = $this->config['generations'] ?? $this->maxGenerations;
            $this->mutationRate = $this->config['mutation_rate'] ?? $this->mutationRate;
            $this->crossoverRate = $this->config['crossover_rate'] ?? $this->crossoverRate;
            $this->maxTime = $this->config['max_generation_time'] ?? $this->maxTime;
        }
    }
    
    /**
     * Generate schedule variants
     * Returns array of variants with their fitness scores
     */
    abstract public function generate(int $year, string $semester): array;
    
    /**
     * Load common data
     */
    protected function loadCommonData(int $year, string $semester): void
    {
        // Load rooms
        $this->rooms = $this->db->fetchAll("SELECT * FROM room ORDER BY number");
        
        // Load user preferences
        $this->loadPreferences();
    }
    
    /**
     * Load user preferences
     */
    protected function loadPreferences(): void
    {
        $prefs = $this->db->fetchAll(
            "SELECT up.*, ur.role 
             FROM user_preference up
             JOIN user_role ur ON up.user_id = ur.user_id
             WHERE ur.role IN ('LECTURER', 'ASSISTANT')"
        );
        
        foreach ($prefs as $pref) {
            if ($pref['role'] === 'LECTURER') {
                $this->lecturerPreferences[$pref['user_id']][] = $pref;
            } else {
                $this->assistantPreferences[$pref['user_id']][] = $pref;
            }
        }
    }
    
    /**
     * Initialize random population
     */
    protected function initializePopulation(array $items, array $availableSlots): array
    {
        $population = [];
        
        for ($i = 0; $i < $this->populationSize; $i++) {
            $chromosome = $this->createRandomChromosome($items, $availableSlots);
            $population[] = [
                'chromosome' => $chromosome,
                'fitness' => 0
            ];
        }
        
        return $population;
    }
    
    /**
     * Create a random chromosome (schedule assignment)
     */
    abstract protected function createRandomChromosome(array $items, array $availableSlots): array;
    
    /**
     * Calculate fitness for a chromosome
     * Higher fitness = better solution
     */
    protected function calculateFitness(array $chromosome): float
    {
        $penalty = $this->calculatePenalty($chromosome);
        
        // Fitness is inverse of penalty
        // Using formula: fitness = 1 / (1 + penalty)
        return 1.0 / (1.0 + $penalty);
    }
    
    /**
     * Calculate total penalty for a chromosome
     */
    abstract protected function calculatePenalty(array $chromosome): float;
    
    /**
     * Perform selection (tournament selection)
     */
    protected function selection(array $population): array
    {
        $tournamentSize = 3;
        $selected = [];
        
        for ($i = 0; $i < $this->populationSize - $this->elitismCount; $i++) {
            $tournament = [];
            
            for ($j = 0; $j < $tournamentSize; $j++) {
                $tournament[] = $population[array_rand($population)];
            }
            
            // Select best from tournament
            usort($tournament, fn($a, $b) => $b['fitness'] <=> $a['fitness']);
            $selected[] = $tournament[0];
        }
        
        return $selected;
    }
    
    /**
     * Perform crossover (uniform crossover)
     */
    protected function crossover(array $parent1, array $parent2): array
    {
        if (mt_rand() / mt_getrandmax() > $this->crossoverRate) {
            return [$parent1, $parent2];
        }
        
        $child1 = [];
        $child2 = [];
        
        foreach ($parent1 as $key => $gene) {
            if (mt_rand(0, 1) === 0) {
                $child1[$key] = $gene;
                $child2[$key] = $parent2[$key] ?? $gene;
            } else {
                $child1[$key] = $parent2[$key] ?? $gene;
                $child2[$key] = $gene;
            }
        }
        
        return [$child1, $child2];
    }
    
    /**
     * Perform mutation
     */
    abstract protected function mutate(array $chromosome, array $availableSlots): array;
    
    /**
     * Apply local repair using CSP techniques
     * Fixes hard constraint violations
     */
    abstract protected function localRepair(array $chromosome, array $availableSlots): array;
    
    /**
     * Run the genetic algorithm
     */
    protected function runGA(array $items, array $availableSlots): array
    {
        $startTime = time();
        
        // Initialize population
        $population = $this->initializePopulation($items, $availableSlots);
        
        // Calculate initial fitness
        foreach ($population as &$individual) {
            $individual['fitness'] = $this->calculateFitness($individual['chromosome']);
        }
        unset($individual);
        
        $generation = 0;
        $bestSolutions = [];
        
        while ($generation < $this->maxGenerations) {
            // Check time limit
            if (time() - $startTime > $this->maxTime) {
                $this->logger->log('SCHEDULE_GENERATION_TIMEOUT', [
                    'generation' => $generation,
                    'elapsed' => time() - $startTime
                ]);
                break;
            }
            
            // Sort by fitness (descending)
            usort($population, fn($a, $b) => $b['fitness'] <=> $a['fitness']);
            
            // Track best solutions
            if (count($bestSolutions) < 3 || $population[0]['fitness'] > min(array_column($bestSolutions, 'fitness'))) {
                $bestSolutions[] = $population[0];
                usort($bestSolutions, fn($a, $b) => $b['fitness'] <=> $a['fitness']);
                $bestSolutions = array_slice($bestSolutions, 0, 3);
            }
            
            // Elitism - keep best individuals
            $newPopulation = array_slice($population, 0, $this->elitismCount);
            
            // Selection
            $selected = $this->selection($population);
            
            // Crossover and mutation
            for ($i = 0; $i < count($selected) - 1; $i += 2) {
                list($child1, $child2) = $this->crossover(
                    $selected[$i]['chromosome'],
                    $selected[$i + 1]['chromosome']
                );
                
                // Mutation
                $child1 = $this->mutate($child1, $availableSlots);
                $child2 = $this->mutate($child2, $availableSlots);
                
                // Local repair (CSP)
                $child1 = $this->localRepair($child1, $availableSlots);
                $child2 = $this->localRepair($child2, $availableSlots);
                
                $newPopulation[] = [
                    'chromosome' => $child1,
                    'fitness' => $this->calculateFitness($child1)
                ];
                
                $newPopulation[] = [
                    'chromosome' => $child2,
                    'fitness' => $this->calculateFitness($child2)
                ];
            }
            
            // Ensure population size
            while (count($newPopulation) < $this->populationSize) {
                $random = $this->createRandomChromosome($items, $availableSlots);
                $random = $this->localRepair($random, $availableSlots);
                $newPopulation[] = [
                    'chromosome' => $random,
                    'fitness' => $this->calculateFitness($random)
                ];
            }
            
            $population = array_slice($newPopulation, 0, $this->populationSize);
            $generation++;
        }
        
        // Final sort and return top 3
        usort($bestSolutions, fn($a, $b) => $b['fitness'] <=> $a['fitness']);
        
        return array_slice($bestSolutions, 0, 3);
    }
    
    /**
     * Generate available time slots
     */
    protected function generateTimeSlots(int $startHour, int $endHour, array $days): array
    {
        $slots = [];
        
        foreach ($days as $day) {
            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $slots[] = [
                    'day' => $day,
                    'start' => sprintf('%02d:00:00', $hour),
                    'end' => sprintf('%02d:00:00', $hour + 1)
                ];
            }
        }
        
        return $slots;
    }
    
    /**
     * Check if two time ranges overlap
     */
    protected function timesOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        return $start1 < $end2 && $end1 > $start2;
    }
    
    /**
     * Get random room
     */
    protected function getRandomRoom(): array
    {
        return $this->rooms[array_rand($this->rooms)];
    }
    
    /**
     * Check if slot satisfies user preferences
     */
    protected function checkPreferences(int $userId, bool $isLecturer, int $hour, array $room): float
    {
        $penalty = 0;
        $prefs = $isLecturer 
            ? ($this->lecturerPreferences[$userId] ?? [])
            : ($this->assistantPreferences[$userId] ?? []);
        
        foreach ($prefs as $pref) {
            $weight = $pref['priority'] / 10; // Normalize priority
            
            switch ($pref['preference_type']) {
                case 'MORNING':
                    if ($hour >= 13) {
                        $penalty += $this->softConstraintPenalty['preference_morning'] * $weight;
                    }
                    break;
                case 'AFTERNOON':
                    if ($hour < 13) {
                        $penalty += $this->softConstraintPenalty['preference_afternoon'] * $weight;
                    }
                    break;
                case 'WHITE_BOARD':
                    if ($room['white_boards'] < 1) {
                        $penalty += $this->softConstraintPenalty['preference_whiteboard'] * $weight;
                    }
                    break;
                case 'NO_BLACK_BOARD':
                    if ($room['black_boards'] > 0) {
                        $penalty += $this->softConstraintPenalty['preference_no_blackboard'] * $weight;
                    }
                    break;
            }
        }
        
        // Lecturer preferences have higher weight
        if ($isLecturer) {
            $penalty *= 1.5;
        }
        
        return $penalty;
    }
    
    /**
     * Save a variant to database
     */
    protected function saveVariant(string $type, int $year, string $semester, string $name, 
                                    float $fitness, array $scheduleItems, ?string $sessionType = null): ScheduleVariant
    {
        $variant = ScheduleVariant::createVariant($type, $year, $semester, $name, $sessionType, $fitness);
        
        // Save schedule items with variant_id
        $this->saveScheduleItems($variant->id, $scheduleItems, $type);
        
        return $variant;
    }
    
    /**
     * Save schedule items to appropriate table
     */
    abstract protected function saveScheduleItems(int $variantId, array $items, string $type): void;
    
    /**
     * Delete old variants before generating new ones
     */
    protected function deleteOldVariants(string $type, int $year, string $semester, ?string $sessionType = null): void
    {
        $variants = ScheduleVariant::getVariants($type, $year, $semester, $sessionType);
        
        foreach ($variants as $variant) {
            if (!$variant->isSelected()) {
                $variant->delete();
            }
        }
        
        // Also clean up orphaned items (variant_id = NULL) from previously selected variants
        $this->cleanupOrphanedItems($type);
    }
    
    /**
     * Clean up orphaned schedule items (items with variant_id = NULL)
     * These are left over from previously selected variants
     */
    protected function cleanupOrphanedItems(string $type): void
    {
        switch ($type) {
            case ScheduleVariant::TYPE_WEEKLY:
                $this->db->execute("DELETE FROM weekly_slot WHERE variant_id IS NULL");
                break;
            case ScheduleVariant::TYPE_TEST:
                $this->db->execute("DELETE FROM test_schedule WHERE variant_id IS NULL");
                break;
            case ScheduleVariant::TYPE_EXAM:
                $this->db->execute("DELETE FROM exam_schedule WHERE variant_id IS NULL");
                break;
        }
    }
}
