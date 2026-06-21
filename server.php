<?php

/**
 * Laravel - PHP Built-in Server Router
 *
 * This file allows running the application locally using PHP's
 * built-in web server WITHOUT needing Apache or Nginx.
 *
 * Usage:
 *   cd /path/to/project
 *   php -S localhost:8000 server.php
 *
 * Then visit: http://localhost:8000
 *
 * Static assets (CSS, JS, images) are served directly for speed.
 * All other requests are routed through Laravel's index.php.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Serve static files directly if they exist
$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    // Return false to let PHP serve the file statically
    return false;
}

// Otherwise, bootstrap Laravel
require __DIR__ . '/index.php';
