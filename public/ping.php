<?php

declare(strict_types=1);

/**
 * Deploy check — open this URL in the browser:
 *   https://YOUR-DOMAIN/Kraved/public/ping.php
 * If you see KRAVED_OK, you are hitting the right app folder.
 */
header('Content-Type: text/plain; charset=utf-8');
header('X-Kraved-App: 1');

echo "KRAVED_OK\n";
echo 'time=' . date('c') . "\n";
echo 'php=' . PHP_VERSION . "\n";
echo 'docroot=' . ($_SERVER['DOCUMENT_ROOT'] ?? '') . "\n";
echo 'script=' . ($_SERVER['SCRIPT_NAME'] ?? '') . "\n";
echo 'uri=' . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
echo 'cwd=' . __DIR__ . "\n";
