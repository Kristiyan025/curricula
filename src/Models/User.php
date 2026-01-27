<?php
namespace App\Models;

/**
 * User Model
 */
class User extends Model
{
    protected static string $table = 'user';
    
    /**
     * Find user by faculty number
     */
    public static function findByFacultyNumber(string $facultyNumber): ?self
    {
        return self::findWhere('faculty_number', $facultyNumber);
    }
    
    /**
     * Find user by email
     */
    public static function findByEmail(string $email): ?self
    {
        return self::findWhere('email', $email);
    }
    
    /**
     * Get user roles
     */
    public function getRoles(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT role FROM user_role WHERE user_id = :user_id",
            ['user_id' => $this->id]
        );
        
        return array_column($rows, 'role');
    }
    
    /**
     * Check if user has a role
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('ADMIN');
    }
    
    /**
     * Check if user is lecturer
     */
    public function isLecturer(): bool
    {
        return $this->hasRole('LECTURER');
    }
    
    /**
     * Check if user is student
     */
    public function isStudent(): bool
    {
        return $this->hasRole('STUDENT');
    }
    
    /**
     * Check if user is assistant
     */
    public function isAssistant(): bool
    {
        return $this->hasRole('ASSISTANT');
    }
    
    /**
     * Add a role to the user
     */
    public function addRole(string $role): bool
    {
        if ($this->hasRole($role)) {
            return true;
        }
        
        $this->db->insert('user_role', [
            'user_id' => $this->id,
            'role' => $role
        ]);
        
        return true;
    }
    
    /**
     * Remove a role from the user
     */
    public function removeRole(string $role): bool
    {
        $this->db->delete('user_role', 'user_id = :user_id AND role = :role', [
            'user_id' => $this->id,
            'role' => $role
        ]);
        
        return true;
    }
    
    /**
     * Set user roles (replace all)
     */
    public function setRoles(array $roles): void
    {
        // Delete existing roles
        $this->db->delete('user_role', 'user_id = :user_id', ['user_id' => $this->id]);
        
        // Add new roles
        foreach ($roles as $role) {
            $this->db->insert('user_role', [
                'user_id' => $this->id,
                'role' => $role
            ]);
        }
    }
    
    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password_hash);
    }
    
    /**
     * Set password (hashes it)
     */
    public function setPassword(string $password): void
    {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Get student data if user is a student
     */
    public function getStudentData(): ?array
    {
        if (!$this->isStudent()) {
            return null;
        }
        
        return $this->db->fetchOne(
            "SELECT s.*, m.name_bg as major_name, ms.name_bg as stream_name, g.name_bg as group_name
             FROM student s
             JOIN major m ON s.major_id = m.id
             JOIN major_stream ms ON s.stream_id = ms.id
             JOIN student_group g ON s.group_id = g.id
             WHERE s.user_id = :user_id",
            ['user_id' => $this->id]
        );
    }
    
    /**
     * Get course instances where user is a lecturer
     */
    public function getLecturerCourseInstances(): array
    {
        return $this->db->fetchAll(
            "SELECT ci.*, c.name_bg as course_name, c.code as course_code
             FROM course_instance ci
             JOIN course c ON ci.course_id = c.id
             JOIN course_lecturer cl ON ci.id = cl.course_instance_id
             WHERE cl.user_id = :user_id",
            ['user_id' => $this->id]
        );
    }
    
    /**
     * Get course instances where user is an assistant
     */
    public function getAssistantCourseInstances(): array
    {
        return $this->db->fetchAll(
            "SELECT ci.*, c.name_bg as course_name, c.code as course_code
             FROM course_instance ci
             JOIN course c ON ci.course_id = c.id
             JOIN course_assistant ca ON ci.id = ca.course_instance_id
             WHERE ca.user_id = :user_id",
            ['user_id' => $this->id]
        );
    }
    
    /**
     * Get user preferences (soft constraints)
     */
    public function getPreferences(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM user_preference WHERE user_id = :user_id ORDER BY priority DESC",
            ['user_id' => $this->id]
        );
    }
    
    /**
     * Set user preference
     */
    public function setPreference(string $type, int $priority = 5): void
    {
        $existing = $this->db->fetchOne(
            "SELECT id FROM user_preference WHERE user_id = :user_id AND preference_type = :type",
            ['user_id' => $this->id, 'type' => $type]
        );
        
        if ($existing) {
            $this->db->update('user_preference', 
                ['priority' => $priority], 
                'id = :id', 
                ['id' => $existing['id']]
            );
        } else {
            $this->db->insert('user_preference', [
                'user_id' => $this->id,
                'preference_type' => $type,
                'priority' => $priority
            ]);
        }
    }
    
    /**
     * Get all users with a specific role
     */
    public static function findByRole(string $role): array
    {
        $db = \App\Core\Application::getInstance()->getDb();
        
        $rows = $db->fetchAll(
            "SELECT u.* FROM user u
             JOIN user_role ur ON u.id = ur.user_id
             WHERE ur.role = :role",
            ['role' => $role]
        );
        
        return array_map(fn($row) => new self($row), $rows);
    }
}
