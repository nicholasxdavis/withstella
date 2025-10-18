<?php
/**
 * Brand Kit Model
 * Handles brand kit management with plain PHP/PDO
 */

class BrandKit {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new brand kit
     */
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO brand_kits (user_id, name, description, logo_url, is_private, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['user_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['logo_url'] ?? null,
            $data['is_private'] ?? false
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Get all brand kits for a user
     */
    public function getByUser($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM brand_kits 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single brand kit by ID
     */
    public function getById($id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM brand_kits 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single brand kit by ID with user check
     */
    public function getByIdAndUser($id, $user_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM brand_kits 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update a brand kit
     */
    public function update($id, $user_id, $data) {
        $fields = [];
        $values = [];

        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $values[] = $data['name'];
        }
        if (isset($data['description'])) {
            $fields[] = 'description = ?';
            $values[] = $data['description'];
        }
        if (isset($data['logo_url'])) {
            $fields[] = 'logo_url = ?';
            $values[] = $data['logo_url'];
        }
        if (isset($data['is_private'])) {
            $fields[] = 'is_private = ?';
            $values[] = $data['is_private'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = NOW()';
        $values[] = $id;
        $values[] = $user_id;

        $sql = "UPDATE brand_kits SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete a brand kit
     */
    public function delete($id, $user_id) {
        $stmt = $this->pdo->prepare("DELETE FROM brand_kits WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $user_id]);
    }
}