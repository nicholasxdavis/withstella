<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nextcloud Connection Settings
    |--------------------------------------------------------------------------
    |
    | Configure your Nextcloud instance connection details here.
    | The application will use WebDAV API to store and retrieve files.
    |
    */

    'url' => getenv('NEXTCLOUD_URL') ?: 'https://cloud.blacnova.net',
    
    'username' => getenv('NEXTCLOUD_USERNAME') ?: 'admin',
    
    'password' => getenv('NEXTCLOUD_PASSWORD') ?: '',
    
    // WebDAV endpoint (usually /remote.php/dav/files/{username}/)
    'webdav_path' => getenv('NEXTCLOUD_WEBDAV_PATH') ?: '/remote.php/dav/files',
    
    // Base folder in Nextcloud where files will be stored
    'base_folder' => getenv('NEXTCLOUD_BASE_FOLDER') ?: '/Stella',
    
    /*
    |--------------------------------------------------------------------------
    | Docker Socket Proxy Settings
    |--------------------------------------------------------------------------
    */
    
    'docker_proxy' => [
        'enabled' => getenv('NEXTCLOUD_DOCKER_ENABLED') ?: false,
        'protocol' => getenv('NEXTCLOUD_DOCKER_PROTOCOL') ?: 'http',
        'host' => getenv('NEXTCLOUD_DOCKER_HOST') ?: 'docker-socket-proxy:2375',
        'network' => getenv('NEXTCLOUD_DOCKER_NETWORK') ?: 'host',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    */
    
    // Set to 'local' or 'nextcloud'
    'storage_driver' => getenv('STORAGE_DRIVER') ?: 'local',
    
    // Timeout for HTTP requests (in seconds)
    'timeout' => 30,
    
    // Enable public sharing for uploaded files
    // Set to false to avoid password requirements - use image proxy instead
    'enable_public_sharing' => false,
    
    // Share permissions (1=read, 15=read+write+create+delete)
    'share_permissions' => 1,
    
    // Auto-generate share passwords if public sharing is enabled
    'share_password_length' => 12,
];

