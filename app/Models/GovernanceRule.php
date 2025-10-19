<?php
/**
 * Governance Rule Model
 * Handles brand content governance rules
 */

class GovernanceRule {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new governance rule
     */
    public function create($user_id, $data) {
        $stmt = $this->pdo->prepare(" 
            INSERT INTO governance_rules (user_id, name, description, rule_type, rule_value, config, severity, enabled)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $data['name'],
            $data['description'] ?? null,
            $data['rule_type'],
            $data['rule_value'],
            isset($data['config']) ? (is_array($data['config']) ? json_encode($data['config']) : $data['config']) : null,
            $data['severity'] ?? 'warning',
            $data['enabled'] ?? true
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Get all rules for a user
     */
    public function getByUser($user_id, $enabled_only = false) {
        $sql = "SELECT * FROM governance_rules WHERE user_id = ?";
        if ($enabled_only) {
            $sql .= " AND enabled = 1";
        }
        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single rule by ID
     */
    public function getById($id, $user_id) {
        $stmt = $this->pdo->prepare(" 
            SELECT * FROM governance_rules 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update a governance rule
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
        if (isset($data['rule_type'])) {
            $fields[] = 'rule_type = ?';
            $values[] = $data['rule_type'];
        }
        if (isset($data['rule_value'])) {
            $fields[] = 'rule_value = ?';
            $values[] = $data['rule_value'];
        }
        if (isset($data['severity'])) {
            $fields[] = 'severity = ?';
            $values[] = $data['severity'];
        }
        if (isset($data['enabled'])) {
            $fields[] = 'enabled = ?';
            $values[] = $data['enabled'];
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $values[] = $user_id;

        $sql = "UPDATE governance_rules SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete a governance rule
     */
    public function delete($id, $user_id) {
        $stmt = $this->pdo->prepare("DELETE FROM governance_rules WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $user_id]);
    }

    /**
     * Validate content against user's governance rules
     */
    public function validateContent($user_id, $content) {
        $rules = $this->getByUser($user_id, true);
        $violations = [];

        foreach ($rules as $rule) {
            $violation = $this->checkRule($rule, $content);
            if ($violation) {
                $violations[] = [
                    'rule_id' => $rule['id'],
                    'rule_name' => $rule['name'],
                    'rule_type' => $rule['rule_type'],
                    'severity' => $rule['severity'],
                    'message' => $violation
                ];
            }
        }

        return [
            'valid' => empty($violations),
            'violations' => $violations
        ];
    }

    /**
     * Check a single rule against content
     */
    private function checkRule($rule, $content) {
        switch ($rule['rule_type']) {
            case 'forbidden_words':
                return $this->checkForbiddenWords($rule, $content);
            
            case 'required_words':
                return $this->checkRequiredWords($rule, $content);
            
            case 'tone':
                return $this->checkTone($rule, $content);
            
            case 'max_length':
                return $this->checkMaxLength($rule, $content);
            
            case 'min_length':
                return $this->checkMinLength($rule, $content);
            
            case 'regex_pattern':
                return $this->checkRegex($rule, $content);
            
            default:
                return null;
        }
    }

    private function checkForbiddenWords($rule, $content) {
        $words = json_decode($rule['rule_value'], true);
        if (!is_array($words)) {
            $words = array_map('trim', explode(',', $rule['rule_value']));
        }

        $content_lower = strtolower($content);
        $found = [];

        foreach ($words as $word) {
            $word_lower = strtolower(trim($word));
            if (stripos($content_lower, $word_lower) !== false) {
                $found[] = $word;
            }
        }

        if (!empty($found)) {
            return "Forbidden words found: " . implode(', ', $found);
        }

        return null;
    }

    private function checkRequiredWords($rule, $content) {
        $words = json_decode($rule['rule_value'], true);
        if (!is_array($words)) {
            $words = array_map('trim', explode(',', $rule['rule_value']));
        }

        $content_lower = strtolower($content);
        $missing = [];

        foreach ($words as $word) {
            $word_lower = strtolower(trim($word));
            if (stripos($content_lower, $word_lower) === false) {
                $missing[] = $word;
            }
        }

        if (!empty($missing)) {
            return "Missing required words: " . implode(', ', $missing);
        }

        return null;
    }

    private function checkTone($rule, $content) {
        $tone_keywords = json_decode($rule['rule_value'], true);
        
        // Check for negative tone indicators if tone is "positive"
        if (isset($tone_keywords['type']) && $tone_keywords['type'] === 'positive') {
            $negative_words = $tone_keywords['avoid'] ?? [];
            $content_lower = strtolower($content);
            $found = [];

            foreach ($negative_words as $word) {
                if (stripos($content_lower, strtolower($word)) !== false) {
                    $found[] = $word;
                }
            }

            if (!empty($found)) {
                return "Negative tone detected. Words found: " . implode(', ', $found);
            }
        }

        return null;
    }

    private function checkMaxLength($rule, $content) {
        $max = intval($rule['rule_value']);
        $length = strlen($content);

        if ($length > $max) {
            return "Content too long: {$length} characters (max: {$max})";
        }

        return null;
    }

    private function checkMinLength($rule, $content) {
        $min = intval($rule['rule_value']);
        $length = strlen($content);

        if ($length < $min) {
            return "Content too short: {$length} characters (min: {$min})";
        }

        return null;
    }

    private function checkRegex($rule, $content) {
        $pattern = $rule['rule_value'];
        
        if (@preg_match($pattern, $content) === false) {
            return "Invalid regex pattern";
        }

        if (!preg_match($pattern, $content)) {
            return "Content does not match required pattern";
        }

        return null;
    }
}

