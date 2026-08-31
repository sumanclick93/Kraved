<?php

declare(strict_types=1);

/**
 * Autoload first — otherwise Helpers/Session fatals with HTTP 500.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = dirname(__DIR__) . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Core\Helpers;
use App\Core\Session;

$config = Helpers::config();
date_default_timezone_set($config['timezone'] ?? 'Europe/London');

Session::start($config['session_name'] ?? 'kraved_session');

/**
 * Resolve the app-relative path for subdirectory installs
 * (e.g. /Kraved, /Kraved/public) or domain root.
 */
function kraved_resolve_path(array $config): string
{
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $uriPath = '/' . trim(str_replace('\\', '/', $uriPath), '/');
    if ($uriPath !== '/') {
        $uriPath = rtrim($uriPath, '/');
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $scriptDir = rtrim(dirname($scriptName), '/');

    $candidates = [];

    if ($scriptDir !== '' && $scriptDir !== '/' && $scriptDir !== '.') {
        $candidates[] = $scriptDir;
        if (str_ends_with($scriptDir, '/public')) {
            $candidates[] = substr($scriptDir, 0, -7) ?: '';
        }
    }

    $configured = parse_url((string) ($config['url'] ?? ''), PHP_URL_PATH) ?: '';
    $configured = rtrim(str_replace('\\', '/', $configured), '/');
    if ($configured !== '' && $configured !== '/') {
        $candidates[] = $configured;
        if (str_ends_with($configured, '/public')) {
            $candidates[] = substr($configured, 0, -7) ?: '';
        }
    }

    $candidates[] = '/Kraved/public';
    $candidates[] = '/Kraved';
    $candidates[] = '/kraved/public';
    $candidates[] = '/kraved';

    usort($candidates, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

    $path = $uriPath;
    foreach ($candidates as $base) {
        $base = '/' . trim($base, '/');
        if ($base === '/' || $base === '') {
            continue;
        }
        if ($path === $base) {
            return '/';
        }
        if (str_starts_with($path, $base . '/')) {
            $path = substr($path, strlen($base)) ?: '/';
            break;
        }
    }

    if ($path === '/public') {
        return '/';
    }
    if (str_starts_with($path, '/public/')) {
        $path = substr($path, 7) ?: '/';
    }

    $path = '/' . trim($path, '/');
    return $path === '/' ? '/' : rtrim($path, '/');
}

// Surface errors while launching (shared hosting often hides them)
if (!empty($config['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

try {
    // If DB is empty, send user to the installer instead of a raw 500
    try {
        $pdo = \App\Core\Database::getInstance();
        $hasCategories = (bool) $pdo->query("SHOW TABLES LIKE 'categories'")->fetchColumn();
        if (!$hasCategories) {
            $install = rtrim((string) ($config['url'] ?? ''), '/') . '/install-schema.php?run=1';
            header('Location: ' . $install);
            exit;
        }
        \App\Core\Schema::ensure();
    } catch (Throwable $dbBoot) {
        // fall through to normal error handling below if connection itself fails
        throw $dbBoot;
    }

    $router = require dirname(__DIR__) . '/app/routes.php';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = kraved_resolve_path($config);
    $router->dispatch($method, $path);
} catch (Throwable $e) {
    if (headers_sent()) {
        echo '<!-- Kraved error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ' -->';
        return;
    }
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Kraved Error</title>';
    echo '<style>body{font-family:system-ui;background:#120b08;color:#f5e6d3;padding:2rem;max-width:720px;margin:0 auto}';
    echo 'code,pre{background:#1a100c;padding:.75rem;border-radius:8px;display:block;overflow:auto;white-space:pre-wrap}</style></head><body>';
    echo '<h1>Kraved — Server Error</h1>';
    if (!empty($config['debug'])) {
        echo '<p><strong>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</strong></p>';
        echo '<pre>' . htmlspecialchars($e->getFile() . ':' . $e->getLine(), ENT_QUOTES, 'UTF-8') . "\n\n";
        echo htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        echo '<p>Something went wrong. Check config/database.php and enable debug.</p>';
    }
    echo '</body></html>';
}
