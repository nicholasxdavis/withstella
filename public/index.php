<?php
// Public entry point - redirect to main index.html
if (file_exists(__DIR__ . '/../index.html')) {
    readfile(__DIR__ . '/../index.html');
} else {
    http_response_code(200);
    echo "Stella is running. Files: " . implode(', ', scandir(__DIR__ . '/..'));
}



