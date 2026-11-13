<?php
/**
 * PHP built-in server router
 * Handles URL rewriting the same way Apache .htaccess does
 */

$root = __DIR__;
// Always run from project root so relative require() paths work
chdir($root);
// Add project root to include_path so any require("inc/bd.php") works from any subdirectory
set_include_path(get_include_path() . PATH_SEPARATOR . $root);
// Suppress PHP 8 notices/warnings from legacy code — only game logic errors matter
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files directly
if ($uri !== '/' && file_exists($root . $uri)) {
    $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
    $mime = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
        'eot'  => 'application/vnd.ms-fontobject',
        'webp' => 'image/webp',
        'html' => 'text/html',
        'txt'  => 'text/plain',
        'json' => 'application/json',
    ];
    if (isset($mime[$ext])) {
        header('Content-Type: ' . $mime[$ext]);
        readfile($root . $uri);
        return true;
    }
    return false; // Let PHP handle .php files
}

// Try $uri.php
if (file_exists($root . $uri . '.php')) {
    chdir(dirname($root . $uri . '.php'));
    require $root . $uri . '.php';
    return true;
}

// Strip leading slash and try as PHP file
$path = ltrim($uri, '/');
if ($path === '' || $path === 'index') {
    require $root . '/index.php';
    return true;
}

// Admin panel routing — /admin and /admin/* → admin/index.php or admin/*.php
if ($path === 'admin' || $path === 'admin/') {
    chdir($root . '/admin');
    require $root . '/admin/index.php';
    return true;
}
if (strpos($path, 'admin/') === 0) {
    $adminFile = $root . '/' . $path . '.php';
    if (file_exists($adminFile)) {
        chdir($root . '/admin');
        require $adminFile;
        return true;
    }
    chdir($root . '/admin');
    require $root . '/admin/index.php';
    return true;
}

if (file_exists($root . '/' . $path . '.php')) {
    require $root . '/' . $path . '.php';
    return true;
}

// 404 fallback
if (file_exists($root . '/404.php')) {
    http_response_code(404);
    require $root . '/404.php';
} else {
    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
}
