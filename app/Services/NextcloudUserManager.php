<?php

namespace App\Services;

/**
 * Nextcloud User Management
 * Creates and manages Nextcloud user accounts via OCS API
 */
class NextcloudUserManager
{
    private $config;
    private $adminUsername;
    private $adminPassword;
    private $baseUrl;
    
    public function __construct()
    {
        $configPath = dirname(__DIR__, 2) . '/config/nextcloud.php';
        $this->config = require $configPath;
        
        // Use admin credentials to create users
        $this->adminUsername = $this->config['username'];
        $this->adminPassword = $this->config['password'];
        $this->baseUrl = rtrim($this->config['url'], '/');
    }
    
    /**
     * Create a Nextcloud user account
     * 
     * @param string $username Nextcloud username (email or unique identifier)
     * @param string $password User's password
     * @param string $displayName User's display name
     * @return array ['success' => bool, 'message' => string, 'username' => string]
     */
    public function createUser($username, $password, $displayName = '')
    {
        // Sanitize username (Nextcloud usernames can't have @ or special chars)
        $nextcloudUsername = $this->sanitizeUsername($username);
        
        // OCS API endpoint for user creation
        $url = $this->baseUrl . '/ocs/v1.php/cloud/users';
        
        $postData = [
            'userid' => $nextcloudUsername,
            'password' => $password,
            'displayName' => $displayName ?: $nextcloudUsername
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_USERPWD => $this->adminUsername . ':' . $this->adminPassword,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'OCS-APIRequest: true',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'message' => 'Nextcloud API error: ' . $error
            ];
        }
        
        // Parse XML response
        $xml = @simplexml_load_string($response);
        
        if (!$xml) {
            return [
                'success' => false,
                'message' => 'Invalid response from Nextcloud API'
            ];
        }
        
        $statusCode = (int)$xml->meta->statuscode;
        $statusMessage = (string)$xml->meta->message;
        
        // Success codes: 100 (created), 102 (already exists)
        if ($statusCode === 100) {
            return [
                'success' => true,
                'message' => 'Nextcloud account created successfully',
                'username' => $nextcloudUsername
            ];
        } elseif ($statusCode === 102) {
            // User already exists - this is okay, we can use it
            return [
                'success' => true,
                'message' => 'Nextcloud account already exists',
                'username' => $nextcloudUsername
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Nextcloud user creation failed: ' . $statusMessage,
                'status_code' => $statusCode
            ];
        }
    }
    
    /**
     * Delete a Nextcloud user account
     * 
     * @param string $username Nextcloud username
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteUser($username)
    {
        $nextcloudUsername = $this->sanitizeUsername($username);
        $url = $this->baseUrl . '/ocs/v1.php/cloud/users/' . urlencode($nextcloudUsername);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_USERPWD => $this->adminUsername . ':' . $this->adminPassword,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['OCS-APIRequest: true'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $xml = @simplexml_load_string($response);
        if (!$xml) {
            return ['success' => false, 'message' => 'Invalid response'];
        }
        
        $statusCode = (int)$xml->meta->statuscode;
        
        if ($statusCode === 100) {
            return ['success' => true, 'message' => 'User deleted'];
        } else {
            return ['success' => false, 'message' => 'Delete failed'];
        }
    }
    
    /**
     * Check if a Nextcloud user exists
     * 
     * @param string $username Nextcloud username
     * @return bool
     */
    public function userExists($username)
    {
        $nextcloudUsername = $this->sanitizeUsername($username);
        $url = $this->baseUrl . '/ocs/v1.php/cloud/users/' . urlencode($nextcloudUsername);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_USERPWD => $this->adminUsername . ':' . $this->adminPassword,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['OCS-APIRequest: true'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $xml = @simplexml_load_string($response);
        if (!$xml) {
            return false;
        }
        
        $statusCode = (int)$xml->meta->statuscode;
        return $statusCode === 100;
    }
    
    /**
     * Sanitize username for Nextcloud
     * Removes special characters and converts email to valid username
     * 
     * @param string $username
     * @return string
     */
    private function sanitizeUsername($username)
    {
        // If it's an email, use the part before @
        if (strpos($username, '@') !== false) {
            $username = substr($username, 0, strpos($username, '@'));
        }
        
        // Remove special characters, keep only alphanumeric, dots, hyphens, underscores
        $username = preg_replace('/[^a-zA-Z0-9._-]/', '', $username);
        
        // Ensure it's lowercase
        $username = strtolower($username);
        
        // Add random suffix if username is too short
        if (strlen($username) < 3) {
            $username .= '_' . bin2hex(random_bytes(3));
        }
        
        return $username;
    }
    
    /**
     * Generate a secure random password for Nextcloud
     * 
     * @param int $length Password length
     * @return string
     */
    public static function generatePassword($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $max = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        
        return $password;
    }
}

