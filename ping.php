<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
echo "KRAVED_ROOT_OK\n";
echo 'uri=' . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
echo 'script=' . ($_SERVER['SCRIPT_NAME'] ?? '') . "\n";
