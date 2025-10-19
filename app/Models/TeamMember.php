<?php
/**
 * Team Member Model
 * Handles team member management with plain PHP/PDO
 */

class TeamMember {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new team member
     */
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO team_members (workspace_owner_id, name, email, role, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['workspace_owner_id'],
            $data['name'],
            $data['email'],
            $data['role']
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Get all team members for a workspace owner
     */
    public function getByOwner($owner_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM team_members 
            WHERE workspace_owner_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$owner_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single team member by ID
     */
    public function getById($id, $owner_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM team_members 
            WHERE id = ? AND workspace_owner_id = ?
        ");
        $stmt->execute([$id, $owner_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update a team member
     */
    public function update($id, $owner_id, $data) {
        $fields = [];
        $values = [];

        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $values[] = $data['name'];
        }
        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $values[] = $data['email'];
        }
        if (isset($data['role'])) {
            $fields[] = 'role = ?';
            $values[] = $data['role'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = NOW()';
        $values[] = $id;
        $values[] = $owner_id;

        $sql = "UPDATE team_members SET " . implode(', ', $fields) . " WHERE id = ? AND workspace_owner_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete a team member
     */
    public function delete($id, $owner_id) {
        $stmt = $this->pdo->prepare("DELETE FROM team_members WHERE id = ? AND workspace_owner_id = ?");
        return $stmt->execute([$id, $owner_id]);
    }
}