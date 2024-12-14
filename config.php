<?php
define('STORAGE_DIR', __DIR__ . '/storage');
define('DB_PATH', __DIR__ . '/storage/paste.db');
define('MAX_PASTE_SIZE', 10 * 1024 * 1024); // 10MB
define('RATE_LIMIT_WINDOW', 60); // Window in seconds
define('RATE_LIMIT_MAX', 30); // Maximum requests per window
define('DEFAULT_EXPIRY', 7 * 24 * 3600); // 7 days

// Supported file extensions and their MIME types
define('SUPPORTED_EXTENSIONS', [
    'txt' => 'text/plain',
    'md' => 'text/markdown',
    'js' => 'text/javascript',
    'css' => 'text/css',
    'html' => 'text/html',
    'json' => 'application/json',
    'xml' => 'application/xml',
    'php' => 'text/plain'
]);
