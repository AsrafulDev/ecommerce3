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

// Serve static files from public/ directly (storage symlink, assets, etc.)
$publicFile = __DIR__ . '/public' . $uri;

// Also check project root for compatibility
$rootFile = __DIR__ . $uri;

$staticFile = null;
if (is_file($publicFile)) {
    $staticFile = $publicFile;
} elseif ($uri !== $publicFile && is_file($rootFile)) {
    $staticFile = $rootFile;
}

if ($staticFile && $uri !== '/') {
    // Set proper MIME type for common static assets
    $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));
    $mimes = [
        'css' => 'text/css', 'js' => 'application/javascript',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp', 'gif' => 'image/gif', 'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
        'ttf' => 'font/ttf', 'eot' => 'application/vnd.ms-fontobject',
        'map' => 'application/json', 'json' => 'application/json',
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
    }
    // Cache static assets for 1 year
    if (in_array($ext, ['png','jpg','jpeg','webp','gif','svg','ico','woff','woff2','ttf','eot','css','js'])) {
        header('Cache-Control: public, max-age=31536000, immutable');
    }
    readfile($staticFile);
    return true;
}

// Otherwise, bootstrap Laravel
require __DIR__ . '/index.php';
