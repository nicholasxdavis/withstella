<?php

namespace App\Services;

/**
 * Nextcloud Storage Service
 * 
 * Handles file operations with Nextcloud using WebDAV protocol
 */
class NextcloudStorage
{
    private $config;
    private $webdavUrl;
    private $username;
    private $password;
    private $baseFolder;
    
    /**
     * Constructor
     * 
     * @param string|null $username Nextcloud username (if null, uses admin from config)
     * @param string|null $password Nextcloud password (if null, uses admin from config)
     */
    public function __construct($username = null, $password = null)
    {
        $configPath = dirname(__DIR__, 2) . '/config/nextcloud.php';
        $this->config = require $configPath;
        
        // Use provided credentials or fall back to admin credentials from config
        $this->username = $username ?? $this->config['username'];
        $this->password = $password ?? $this->config['password'];
        $this->baseFolder = rtrim($this->config['base_folder'], '/');
        
        // Validate credentials
        if (empty($this->username) || empty($this->password)) {
            throw new \Exception('Nextcloud credentials not configured. Set NEXTCLOUD_USERNAME and NEXTCLOUD_PASSWORD environment variables.');
        }
        
        // Build WebDAV URL (encode username to avoid malformed URLs)
        $baseUrl = rtrim($this->config['url'], '/');
        $webdavPath = trim($this->config['webdav_path'], '/');
        $encodedUsername = rawurlencode($this->username);
        $this->webdavUrl = $baseUrl . '/' . $webdavPath . '/' . $encodedUsername;
        
        // Ensure base folder exists
        $this->ensureFolder($this->baseFolder);
    }
    
    /**
     * Upload a file to Nextcloud
     * 
     * @param string $localPath Local file path
     * @param string $remotePath Remote path in Nextcloud (relative to base folder)
     * @return array ['success' => bool, 'url' => string, 'share_url' => string|null]
     */
    public function uploadFile($localPath, $remotePath)
    {
        if (!file_exists($localPath)) {
            return ['success' => false, 'message' => 'Local file not found'];
        }
        
        // Clean the remote path
        $remotePath = trim($remotePath, '/');
        $fullPath = $this->baseFolder . '/' . $remotePath;
        
        // Ensure remote directory exists
        $remoteDir = dirname($fullPath);
        if ($remoteDir !== '.' && $remoteDir !== $this->baseFolder) {
            $this->ensureFolder($remoteDir);
        }
        
        // Build the URL with proper encoding
        $url = $this->webdavUrl . $this->encodePath($fullPath);
        
        $ch = curl_init($url);
        $fileHandle = fopen($localPath, 'r');
        
        curl_setopt_array($ch, [
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $fileHandle,
            CURLOPT_INFILESIZE => filesize($localPath),
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/octet-stream'],
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        fclose($fileHandle);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $result = [
                'success' => true,
                'url' => $this->getFileUrl($fullPath),
                'webdav_url' => $url,
                'path' => $fullPath
            ];
            
            // Create public share link if enabled
            if ($this->config['enable_public_sharing']) {
                $shareUrl = $this->createPublicShare($fullPath);
                if ($shareUrl) {
                    $result['share_url'] = $shareUrl;
                }
            }
            
            return $result;
        } else {
            return [
                'success' => false,
                'message' => 'Upload failed: ' . ($error ?: 'HTTP ' . $httpCode),
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Delete a file from Nextcloud
     * 
     * @param string $remotePath Remote path in Nextcloud
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteFile($remotePath)
    {
        $url = $this->webdavUrl . $this->encodePath($remotePath);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'File deleted'];
        } else {
            return ['success' => false, 'message' => 'Delete failed: HTTP ' . $httpCode];
        }
    }
    
    /**
     * Copy a file within Nextcloud using WebDAV COPY
     *
     * @param string $sourcePath Source remote path
     * @param string $destinationPath Destination remote path
     * @return array ['success' => bool, 'message' => string]
     */
    public function copyFile($sourcePath, $destinationPath)
    {
        $source = $this->webdavUrl . $this->encodePath($sourcePath);
        $destinationFull = $this->baseFolder . '/' . ltrim($destinationPath, '/');
        $destinationUrl = $this->webdavUrl . $this->encodePath($destinationFull);

        // Ensure destination folder exists
        $destDir = dirname($destinationFull);
        $this->ensureFolder($destDir);

        $ch = curl_init($source);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'COPY',
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                'Destination: ' . $destinationUrl
            ],
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'File copied'];
        }
        return ['success' => false, 'message' => 'Copy failed: ' . ($error ?: 'HTTP ' . $httpCode)];
    }

    /**
     * Check if a file exists in Nextcloud
     * 
     * @param string $remotePath Remote path
     * @return bool
     */
    public function fileExists($remotePath)
    {
        $url = $this->webdavUrl . $this->encodePath($remotePath);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PROPFIND',
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Depth: 0'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ($httpCode >= 200 && $httpCode < 300);
    }
    
    /**
     * Ensure a folder exists in Nextcloud
     * 
     * @param string $folderPath Folder path
     * @return bool
     */
    private function ensureFolder($folderPath)
    {
        // Create nested folders one by one to avoid MKCOL parent-missing errors
        $normalized = trim($folderPath, '/');
        if ($normalized === '') {
            return true;
        }
        $segments = explode('/', $normalized);
        $current = '';
        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }
            $current .= '/' . $segment;
            if ($this->fileExists($current)) {
                continue;
            }
            $url = $this->webdavUrl . $this->encodePath($current);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => 'MKCOL',
                CURLOPT_USERPWD => $this->username . ':' . $this->password,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (!($httpCode >= 200 && $httpCode < 300)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Create a public share link for a file
     * 
     * @param string $filePath File path in Nextcloud
     * @return string|null Share URL or null on failure
     */
    private function createPublicShare($filePath)
    {
        // Ensure path starts with /
        $filePath = '/' . ltrim($filePath, '/');
        $baseUrl = rtrim($this->config['url'], '/');
        $url = $baseUrl . '/ocs/v2.php/apps/files_sharing/api/v1/shares';
        
        $shareData = [
            'path' => $filePath,
            'shareType' => 3, // Public link
            'permissions' => $this->config['share_permissions'],
        ];
        
        // Add password if Nextcloud requires it
        // Generate a random password for the share
        if (isset($this->config['share_password_length'])) {
            $passwordLength = $this->config['share_password_length'];
            $sharePassword = bin2hex(random_bytes($passwordLength / 2));
            $shareData['password'] = $sharePassword;
        }
        
        $postData = http_build_query($shareData);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'OCS-APIRequest: true',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300 && $response) {
            $xml = @simplexml_load_string($response);
            if ($xml && isset($xml->data->url)) {
                return (string)$xml->data->url;
            }
        }
        
        return null;
    }
    
    /**
     * Get direct file URL
     * 
     * @param string $filePath File path
     * @return string
     */
    private function getFileUrl($filePath)
    {
        return $this->webdavUrl . $this->encodePath($filePath);
    }
    
    /**
     * Encode path for WebDAV URL
     * 
     * @param string $path
     * @return string
     */
    private function encodePath($path)
    {
        // Ensure path starts with /
        $path = '/' . ltrim($path, '/');
        
        // Encode each path segment separately
        $parts = explode('/', $path);
        $encodedParts = [];
        foreach ($parts as $part) {
            if ($part !== '') {
                $encodedParts[] = rawurlencode($part);
            }
        }
        
        return '/' . implode('/', $encodedParts);
    }
    
    /**
     * Get file metadata from Nextcloud including file ID
     * 
     * @param string $remotePath Remote file path
     * @return array|false File info with file_id, etag, etc. or false on failure
     */
    public function getFileInfo($remotePath)
    {
        $url = $this->webdavUrl . $this->encodePath($remotePath);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PROPFIND',
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Depth: 0',
                'Content-Type: application/xml'
            ],
            CURLOPT_POSTFIELDS => '<?xml version="1.0"?>
                <d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
                    <d:prop>
                        <oc:fileid/>
                        <d:getetag/>
                        <d:getcontenttype/>
                        <d:getcontentlength/>
                    </d:prop>
                </d:propfind>',
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300 && $response) {
            $xml = @simplexml_load_string($response);
            if ($xml) {
                // Register namespaces
                $xml->registerXPathNamespace('d', 'DAV:');
                $xml->registerXPathNamespace('oc', 'http://owncloud.org/ns');
                
                $fileId = $xml->xpath('//oc:fileid');
                $etag = $xml->xpath('//d:getetag');
                $contentType = $xml->xpath('//d:getcontenttype');
                $contentLength = $xml->xpath('//d:getcontentlength');
                
                return [
                    'file_id' => $fileId ? (string)$fileId[0] : null,
                    'etag' => $etag ? str_replace('"', '', (string)$etag[0]) : null,
                    'content_type' => $contentType ? (string)$contentType[0] : null,
                    'size' => $contentLength ? (int)$contentLength[0] : null
                ];
            }
        }
        
        return false;
    }
    
    /**
     * Get preview URL for a file
     * Uses Nextcloud's preview API
     * 
     * @param string $remotePath Remote file path or file ID
     * @param int $width Preview width
     * @param int $height Preview height
     * @return string|null Preview URL or null if not available
     */
    public function getPreviewUrlFromPath($remotePath, $width = 256, $height = 256)
    {
        // Get file info to get the file ID
        $fileInfo = $this->getFileInfo($remotePath);
        
        if ($fileInfo && $fileInfo['file_id']) {
            $baseUrl = rtrim($this->config['url'], '/');
            $etag = $fileInfo['etag'] ?? '';
            
            // Build preview URL
            return $baseUrl . '/core/preview?' . http_build_query([
                'fileId' => $fileInfo['file_id'],
                'x' => $width,
                'y' => $height,
                'a' => 'true',
                'etag' => $etag
            ]);
        }
        
        return null;
    }
    
    /**
     * Get public download URL from share link
     * 
     * @param string $shareUrl Share URL from Nextcloud
     * @return string Download URL
     */
    public function getDownloadUrl($shareUrl)
    {
        // Convert share URL to download URL
        // Nextcloud share URLs can be converted to direct download by appending /download
        if (strpos($shareUrl, '/s/') !== false) {
            return rtrim($shareUrl, '/') . '/download';
        }
        return $shareUrl;
    }
    
    /**
     * Get preview URL from share link
     * 
     * @param string $shareUrl Share URL from Nextcloud
     * @param int $width Preview width (default 256)
     * @param int $height Preview height (default 256)
     * @return string Preview URL
     */
    public function getPreviewUrl($shareUrl, $width = 256, $height = 256)
    {
        // For now, use the download URL as preview
        // Nextcloud's preview API requires authentication for direct access
        // The share link provides public access to the file
        return $this->getDownloadUrl($shareUrl);
    }
    
    /**
     * Test connection to Nextcloud
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection()
    {
        $ch = curl_init($this->webdavUrl);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PROPFIND',
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Depth: 0'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'message' => 'Connection successful',
                'webdav_url' => $this->webdavUrl
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . ($error ?: 'HTTP ' . $httpCode),
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Get storage driver from config
     * 
     * @return string 'local' or 'nextcloud'
     */
    public static function getStorageDriver()
    {
        $configPath = dirname(__DIR__, 2) . '/config/nextcloud.php';
        $config = require $configPath;
        return $config['storage_driver'];
    }
}

