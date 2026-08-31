<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<string, callable|array>> */
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable|array $handler): void
    {
        $path = $this->normalize($path);
        $this->routes[$method][$path] = $handler;
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim(str_replace('\\', '/', $path), '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public function dispatch(string $method, string $uri): void
    {
        $original = $this->normalize(parse_url($uri, PHP_URL_PATH) ?: '/');
        $candidates = $this->pathCandidates($original);

        $handler = null;
        $params = [];
        $matchedPath = $original;

        foreach ($candidates as $path) {
            [$handler, $params] = $this->match($method, $path);
            if ($handler !== null) {
                $matchedPath = $path;
                break;
            }
        }

        if ($handler === null) {
            $this->notFound($method, $original, $candidates);
            return;
        }

        if (is_array($handler)) {
            [$class, $action] = $handler;
            $controller = new $class();
            $controller->$action(...array_values($params));
            return;
        }

        $handler(...array_values($params));
    }

    /**
     * Build path variants so /Kraved, /Kraved/public, /public all resolve.
     *
     * @return list<string>
     */
    private function pathCandidates(string $path): array
    {
        $paths = [$path];

        // Strip known prefixes
        foreach (['/Kraved/public', '/kraved/public', '/Kraved', '/kraved', '/public'] as $prefix) {
            if ($path === $prefix) {
                $paths[] = '/';
            } elseif (str_starts_with($path, $prefix . '/')) {
                $paths[] = $this->normalize(substr($path, strlen($prefix)));
            }
        }

        // Progressively drop leading segments until '/'
        $parts = array_values(array_filter(explode('/', trim($path, '/'))));
        while ($parts !== []) {
            array_shift($parts);
            $paths[] = $parts === [] ? '/' : '/' . implode('/', $parts);
        }

        // Unique, preserve order
        $unique = [];
        foreach ($paths as $p) {
            $p = $this->normalize($p);
            if (!in_array($p, $unique, true)) {
                $unique[] = $p;
            }
        }
        return $unique;
    }

    /** @return array{0: callable|array|null, 1: array<string, string>} */
    private function match(string $method, string $path): array
    {
        if (isset($this->routes[$method][$path])) {
            return [$this->routes[$method][$path], []];
        }

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '([^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                preg_match_all('#\{([a-zA-Z_]+)\}#', $route, $keys);
                $params = array_combine($keys[1], $matches) ?: [];
                return [$handler, $params];
            }
        }

        return [null, []];
    }

    /** @param list<string> $tried */
    private function notFound(string $method, string $original, array $tried): void
    {
        http_response_code(404);
        $debug = (bool) (Helpers::config('debug') ?? false);
        $home = htmlspecialchars(Helpers::baseUrl(), ENT_QUOTES, 'UTF-8');

        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>404 · Kraved</title>';
        echo '<style>body{font-family:system-ui;background:#120b08;color:#f5e6d3;display:grid;place-items:center;min-height:100vh;margin:0;text-align:center;padding:1rem}';
        echo 'a.btn{display:inline-block;margin-top:1rem;padding:.75rem 1.25rem;border-radius:999px;background:#d2a679;color:#1a100c;text-decoration:none;font-weight:700}';
        echo '.dbg{margin-top:2rem;padding:1rem;border:1px dashed #d2a679;text-align:left;font:12px/1.5 ui-monospace,monospace;max-width:560px;word-break:break-all}</style></head><body>';
        echo '<div><p style="letter-spacing:.12em;text-transform:uppercase;color:#d2a679;margin:0">Kraved</p>';
        echo '<h1 style="font-size:4rem;margin:.2rem 0;color:#d2a679">404</h1>';
        echo '<p>Page not found.</p>';
        echo '<a class="btn" href="' . $home . '">Return Home</a>';

        if ($debug) {
            echo '<div class="dbg"><strong>Kraved Routing Debug</strong><br>';
            echo 'App marker: KRAVED_ROUTER_V2<br>';
            echo 'Method: ' . htmlspecialchars($method, ENT_QUOTES, 'UTF-8') . '<br>';
            echo 'Request URI: ' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>';
            echo 'Script Name: ' . htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>';
            echo 'Document Root: ' . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? '', ENT_QUOTES, 'UTF-8') . '<br>';
            echo 'Original Path: ' . htmlspecialchars($original, ENT_QUOTES, 'UTF-8') . '<br>';
            echo 'Tried: ' . htmlspecialchars(implode(' → ', $tried), ENT_QUOTES, 'UTF-8') . '<br>';
            echo '</div>';
        }
        echo '</div></body></html>';
    }
}
