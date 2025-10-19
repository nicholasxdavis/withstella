<?php
/**
 * Pending Invite Model
 * Handles pending invite management with plain PHP/PDO
 */

class PendingInvite {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new pending invite
     */
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO pending_invites (invited_by, email, token, role, expires_at, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['invited_by'],
            $data['email'],
            $data['token'],
            $data['role'] ?? 'Team Member',
            $data['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+7 days'))
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Get all pending invites for a user
     */
    public function getByInviter($inviter_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM pending_invites 
            WHERE invited_by = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$inviter_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a pending invite by token
     */
    public function getByToken($token) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM pending_invites 
            WHERE token = ? AND expires_at > NOW()
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get a pending invite by email
     */
    public function getByEmail($email, $inviter_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM pending_invites 
            WHERE email = ? AND invited_by = ? AND expires_at > NOW()
        ");
        $stmt->execute([$email, $inviter_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Delete a pending invite
     */
    public function delete($id, $inviter_id) {
        $stmt = $this->pdo->prepare("DELETE FROM pending_invites WHERE id = ? AND invited_by = ?");
        return $stmt->execute([$id, $inviter_id]);
    }

    /**
     * Delete expired invites
     */
    public function deleteExpired() {
        $stmt = $this->pdo->prepare("DELETE FROM pending_invites WHERE expires_at < NOW()");
        return $stmt->execute();
    }
}