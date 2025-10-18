<?php
/**
 * API Key Model
 * Manages API keys for authentication
 */

class ApiKey {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Generate a secure API key
     */
    private function generateKey() {
        return 'sk_' . bin2hex(random_bytes(32));
    }

    /**
     * Create a new API key
     */
    public function create($user_id, $name) {
        $api_key = $this->generateKey();
        
        $stmt = $this->pdo->prepare("
            INSERT INTO api_keys (user_id, name, api_key)
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([$user_id, $name, $api_key]);
        
        return [
            'id' => $this->pdo->lastInsertId(),
            'api_key' => $api_key,
            'name' => $name
        ];
    }

    /**
     * Get all API keys for a user (without revealing full key)
     */
    public function getByUser($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT id, name, 
                   CONCAT(SUBSTRING(api_key, 1, 12), '...', SUBSTRING(api_key, -4)) as masked_key,
                   last_used_at, created_at
            FROM api_keys 
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validate an API key and return user info
     */
    public function validate($api_key) {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.email, u.full_name, ak.id as key_id
            FROM api_keys ak
            JOIN users u ON ak.user_id = u.id
            WHERE ak.api_key = ?
        ");
        $stmt->execute([$api_key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Update last_used_at
            $updateStmt = $this->pdo->prepare("
                UPDATE api_keys SET last_used_at = NOW() WHERE id = ?
            ");
            $updateStmt->execute([$result['key_id']]);
        }
        
        return $result;
    }

    /**
     * Delete an API key
     */
    public function delete($id, $user_id) {
        $stmt = $this->pdo->prepare("
            DELETE FROM api_keys WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$id, $user_id]);
    }
}


