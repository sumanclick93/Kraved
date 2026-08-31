<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Controller
{
    protected function db(): PDO
    {
        return Database::getInstance();
    }

    protected function view(string $path, array $data = [], ?string $layout = null): void
    {
        Helpers::view($path, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        Helpers::redirect($path);
    }

    protected function json(mixed $data, int $code = 200): never
    {
        Helpers::json($data, $code);
    }
}
