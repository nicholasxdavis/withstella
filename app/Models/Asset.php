<?php
/**
 * Asset Model
 * Handles asset management with plain PHP/PDO
 */

class Asset {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new asset
     */
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO assets (user_id, brand_kit_id, name, description, type, file_path, file_size, mime_type, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['user_id'],
            $data['brand_kit_id'] ?? null,
            $data['name'],
            $data['description'] ?? null,
            $data['type'] ?? 'file',
            $data['file_path'],
            $data['file_size'] ?? 0,
            $data['mime_type'] ?? 'application/octet-stream'
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Get all assets for a user
     */
    public function getByUser($user_id, $limit = 100) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM assets 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single asset by ID
     */
    public function getById($id, $user_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM assets 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update an asset
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
        if (isset($data['type'])) {
            $fields[] = 'type = ?';
            $values[] = $data['type'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = NOW()';
        $values[] = $id;
        $values[] = $user_id;

        $sql = "UPDATE assets SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete an asset
     */
    public function delete($id, $user_id) {
        $stmt = $this->pdo->prepare("DELETE FROM assets WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $user_id]);
    }

    /**
     * Get assets by brand kit
     */
    public function getByBrandKit($brand_kit_id, $user_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM assets 
            WHERE brand_kit_id = ? AND user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$brand_kit_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}