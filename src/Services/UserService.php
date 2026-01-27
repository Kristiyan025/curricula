<?php
namespace App\Services;

use App\Models\User;
use App\Core\Application;
use App\Utils\Logger;

/**
 * User Management Service
 */
class UserService
{
    private $db;
    private $logger;
    
    public function __construct()
    {
        $app = Application::getInstance();
        $this->db = $app->getDb();
        $this->logger = $app->getLogger();
    }
    
    /**
     * Get all users
     */
    public function getAllUsers(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT u.*, GROUP_CONCAT(ur.role) as roles
             FROM user u
             LEFT JOIN user_role ur ON u.id = ur.user_id
             GROUP BY u.id
             ORDER BY u.full_name"
        );
        
        return array_map(function($row) {
            $row['roles'] = $row['roles'] ? explode(',', $row['roles']) : [];
            return $row;
        }, $rows);
    }
    
    /**
     * Get user by ID with details
     */
    public function getUserById(int $id): ?array
    {
        $user = $this->db->fetchOne(
            "SELECT u.* FROM user u WHERE u.id = :id",
            ['id' => $id]
        );
        
        if (!$user) {
            return null;
        }
        
        // Get roles
        $user['roles'] = $this->db->fetchAll(
            "SELECT role FROM user_role WHERE user_id = :user_id",
            ['user_id' => $id]
        );
        $user['roles'] = array_column($user['roles'], 'role');
        
        // Get student data if applicable
        if (in_array('STUDENT', $user['roles'])) {
            $user['student'] = $this->db->fetchOne(
                "SELECT s.*, m.name_bg as major_name, ms.name_bg as stream_name, g.name_bg as group_name
                 FROM student s
                 JOIN major m ON s.major_id = m.id
                 JOIN major_stream ms ON s.stream_id = ms.id
                 JOIN student_group g ON s.group_id = g.id
                 WHERE s.user_id = :user_id",
                ['user_id' => $id]
            );
        }
        
        // Get preferences
        $user['preferences'] = $this->db->fetchAll(
            "SELECT * FROM user_preference WHERE user_id = :user_id",
            ['user_id' => $id]
        );
        
        return $user;
    }
    
    /**
     * Create a new user
     */
    public function createUser(array $data): array
    {
        // Validate required fields
        if (empty($data['faculty_number']) || empty($data['full_name']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Липсват задължителни полета'];
        }
        
        // Check if faculty number exists
        $existing = User::findByFacultyNumber($data['faculty_number']);
        if ($existing) {
            return ['success' => false, 'message' => 'Факултетен номер вече съществува'];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Create user
            $userId = $this->db->insert('user', [
                'faculty_number' => $data['faculty_number'],
                'full_name' => $data['full_name'],
                'email' => $data['email'] ?? null,
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'is_active' => $data['is_active'] ?? 1
            ]);
            
            // Add roles
            if (!empty($data['roles'])) {
                foreach ($data['roles'] as $role) {
                    $this->db->insert('user_role', [
                        'user_id' => $userId,
                        'role' => $role
                    ]);
                }
                
                // If student, add student data
                if (in_array('STUDENT', $data['roles']) && !empty($data['student'])) {
                    $this->db->insert('student', [
                        'user_id' => $userId,
                        'major_id' => $data['student']['major_id'],
                        'year' => $data['student']['year'],
                        'stream_id' => $data['student']['stream_id'],
                        'group_id' => $data['student']['group_id']
                    ]);
                }
            }
            
            $this->db->commit();
            
            $this->logger->log(Logger::USER_CREATED, [
                'user_id' => $userId,
                'faculty_number' => $data['faculty_number']
            ]);
            
            return ['success' => true, 'id' => $userId];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Грешка при създаване: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update a user
     */
    public function updateUser(int $id, array $data): array
    {
        $user = User::find($id);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Потребителят не е намерен'];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Update basic fields
            $updateData = [];
            
            if (isset($data['full_name'])) {
                $updateData['full_name'] = $data['full_name'];
            }
            if (isset($data['email'])) {
                $updateData['email'] = $data['email'];
            }
            if (isset($data['is_active'])) {
                $updateData['is_active'] = $data['is_active'];
            }
            if (!empty($data['password'])) {
                $updateData['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (!empty($updateData)) {
                $this->db->update('user', $updateData, 'id = :id', ['id' => $id]);
            }
            
            // Update roles if provided
            if (isset($data['roles'])) {
                $this->db->delete('user_role', 'user_id = :user_id', ['user_id' => $id]);
                
                foreach ($data['roles'] as $role) {
                    $this->db->insert('user_role', [
                        'user_id' => $id,
                        'role' => $role
                    ]);
                }
                
                // Handle student data
                if (in_array('STUDENT', $data['roles'])) {
                    if (!empty($data['student'])) {
                        $existingStudent = $this->db->fetchOne(
                            "SELECT user_id FROM student WHERE user_id = :user_id",
                            ['user_id' => $id]
                        );
                        
                        if ($existingStudent) {
                            $this->db->update('student', [
                                'major_id' => $data['student']['major_id'],
                                'year' => $data['student']['year'],
                                'stream_id' => $data['student']['stream_id'],
                                'group_id' => $data['student']['group_id']
                            ], 'user_id = :user_id', ['user_id' => $id]);
                        } else {
                            $this->db->insert('student', [
                                'user_id' => $id,
                                'major_id' => $data['student']['major_id'],
                                'year' => $data['student']['year'],
                                'stream_id' => $data['student']['stream_id'],
                                'group_id' => $data['student']['group_id']
                            ]);
                        }
                    }
                } else {
                    // Remove student data if no longer a student
                    $this->db->delete('student', 'user_id = :user_id', ['user_id' => $id]);
                }
            }
            
            $this->db->commit();
            
            $this->logger->log(Logger::USER_UPDATED, [
                'user_id' => $id
            ]);
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Грешка при обновяване: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete a user
     */
    public function deleteUser(int $id): array
    {
        $user = User::find($id);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Потребителят не е намерен'];
        }
        
        // Prevent deleting admin if it's the last one
        if ($user->isAdmin()) {
            $adminCount = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM user_role WHERE role = 'ADMIN'"
            );
            
            if ($adminCount <= 1) {
                return ['success' => false, 'message' => 'Не може да изтриете последния администратор'];
            }
        }
        
        try {
            $this->db->delete('user', 'id = :id', ['id' => $id]);
            
            $this->logger->log(Logger::USER_DELETED, [
                'user_id' => $id,
                'faculty_number' => $user->faculty_number
            ]);
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Грешка при изтриване: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get users by role
     */
    public function getUsersByRole(string $role): array
    {
        return $this->db->fetchAll(
            "SELECT u.* FROM user u
             JOIN user_role ur ON u.id = ur.user_id
             WHERE ur.role = :role
             ORDER BY u.full_name",
            ['role' => $role]
        );
    }
    
    /**
     * Set user preference
     */
    public function setPreference(int $userId, string $type, int $priority): bool
    {
        $existing = $this->db->fetchOne(
            "SELECT id FROM user_preference WHERE user_id = :user_id AND preference_type = :type",
            ['user_id' => $userId, 'type' => $type]
        );
        
        if ($existing) {
            $this->db->update('user_preference',
                ['priority' => $priority],
                'id = :id',
                ['id' => $existing['id']]
            );
        } else {
            $this->db->insert('user_preference', [
                'user_id' => $userId,
                'preference_type' => $type,
                'priority' => $priority
            ]);
        }
        
        return true;
    }
    
    /**
     * Remove user preference
     */
    public function removePreference(int $userId, string $type): bool
    {
        $this->db->delete('user_preference',
            'user_id = :user_id AND preference_type = :type',
            ['user_id' => $userId, 'type' => $type]
        );
        
        return true;
    }
    
    /**
     * Get all available user roles
     * @param int|null $userId If provided, returns roles for specific user (not implemented, for API compat)
     */
    public function getUserRoles(?int $userId = null): array
    {
        // If userId is provided, get that user's roles
        if ($userId !== null) {
            $roles = $this->db->fetchAll(
                "SELECT role FROM user_role WHERE user_id = :user_id",
                ['user_id' => $userId]
            );
            return array_column($roles, 'role');
        }
        
        // Otherwise return all available role types
        return [
            'ADMIN' => 'Администратор',
            'LECTURER' => 'Преподавател',
            'ASSISTANT' => 'Асистент',
            'STUDENT' => 'Студент'
        ];
    }
}
