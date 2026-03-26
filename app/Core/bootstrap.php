<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$publicRoot = $projectRoot;
$legacyPublicRoot = $projectRoot . '/public';

if (!defined('APP_ROOT')) {
    define('APP_ROOT', $projectRoot);
}

if (!defined('PUBLIC_ROOT')) {
    define('PUBLIC_ROOT', $publicRoot);
}

if (!defined('LEGACY_PUBLIC_ROOT')) {
    define('LEGACY_PUBLIC_ROOT', $legacyPublicRoot);
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/../' . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

$envFiles = [
    APP_ROOT . '/.env',
    APP_ROOT . '/.env.example',
];

foreach ($envFiles as $envFile) {
    if (!file_exists($envFile)) {
        continue;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"");

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
}

if (!defined('APP_ENV')) {
    define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
}

if (!defined('APP_DEBUG')) {
    $debugValue = strtolower((string)($_ENV['APP_DEBUG'] ?? '0'));
    define('APP_DEBUG', in_array($debugValue, ['1', 'true', 'on', 'yes'], true));
}

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

if (!defined('APP_URL')) {
    $detectedHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $detectedScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $defaultAppUrl = $detectedScheme . '://' . $detectedHost;
    define('APP_URL', rtrim($_ENV['APP_URL'] ?? $defaultAppUrl, '/'));
}

if (!defined('APP_BASE_PATH')) {
    $appPath = trim((string)parse_url(APP_URL, PHP_URL_PATH), '/');
    define('APP_BASE_PATH', $appPath === '' ? '' : '/' . $appPath);
}

if (!defined('ASSETS_PATH')) {
    $assetsRoot = is_dir(PUBLIC_ROOT . '/assets') ? PUBLIC_ROOT . '/assets' : LEGACY_PUBLIC_ROOT . '/assets';
    define('ASSETS_PATH', $assetsRoot);
}

if (!defined('UPLOADS_PATH')) {
    $uploadsRoot = is_dir(PUBLIC_ROOT . '/uploads') ? PUBLIC_ROOT . '/uploads' : LEGACY_PUBLIC_ROOT . '/uploads';
    define('UPLOADS_PATH', $uploadsRoot);
}

require_once __DIR__ . '/../Helpers/helpers.php';
