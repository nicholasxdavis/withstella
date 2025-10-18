<?php
/**
 * User Model
 * Handles user management with plain PHP/PDO
 */

class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new user
     */
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (full_name, email, password, role, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['password'],
            $data['role'] ?? 'owner'
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM users 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update user
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];

        if (isset($data['full_name'])) {
            $fields[] = 'full_name = ?';
            $values[] = $data['full_name'];
        }
        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $values[] = $data['email'];
        }
        if (isset($data['password'])) {
            $fields[] = 'password = ?';
            $values[] = $data['password'];
        }
        if (isset($data['role'])) {
            $fields[] = 'role = ?';
            $values[] = $data['role'];
        }
        if (isset($data['nextcloud_username'])) {
            $fields[] = 'nextcloud_username = ?';
            $values[] = $data['nextcloud_username'];
        }
        if (isset($data['nextcloud_password'])) {
            $fields[] = 'nextcloud_password = ?';
            $values[] = $data['nextcloud_password'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = NOW()';
        $values[] = $id;

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete user
     */
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Verify password
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Hash password
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}