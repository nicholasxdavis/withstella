<?php
/**
 * Activity Model
 * Handles activity logging with plain PHP/PDO
 */

class Activity {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new activity log entry
     */
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO activities (user_id, icon, message, detail, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['user_id'],
            $data['icon'] ?? 'fas fa-info-circle',
            $data['message'],
            $data['detail'] ?? null
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Get all activities for a user
     */
    public function getByUser($user_id, $limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM activities 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get recent activities for a user
     */
    public function getRecentByUser($user_id, $limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM activities 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete old activities
     */
    public function deleteOld($days = 30) {
        $stmt = $this->pdo->prepare("
            DELETE FROM activities 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        return $stmt->execute([$days]);
    }
}