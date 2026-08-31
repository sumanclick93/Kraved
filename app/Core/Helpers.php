<?php

declare(strict_types=1);

namespace App\Core;

final class Helpers
{
    private static ?array $appConfig = null;

    public static function config(string $key = null, mixed $default = null): mixed
    {
        if (self::$appConfig === null) {
            self::$appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        }
        if ($key === null) {
            return self::$appConfig;
        }
        return self::$appConfig[$key] ?? $default;
    }

    public static function baseUrl(string $path = ''): string
    {
        $base = self::detectedPublicBase();
        $path = ltrim($path, '/');
        return $path === '' ? $base : $base . '/' . $path;
    }

    /**
     * Path-only form action so login/checkout never jump http↔https or / ↔ /public.
     */
    public static function formAction(string $path = ''): string
    {
        $basePath = self::webBasePath();
        $path = ltrim($path, '/');
        $joined = rtrim($basePath, '/') . ($path === '' ? '' : '/' . $path);
        return $joined === '' ? '/' : $joined;
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        if (strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https') {
            return true;
        }
        $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwarded !== '' && str_contains($forwarded, 'https')) {
            return true;
        }
        if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') {
            return true;
        }
        if ((string) ($_SERVER['HTTP_X_FORWARDED_PORT'] ?? '') === '443') {
            return true;
        }
        $cf = (string) ($_SERVER['HTTP_CF_VISITOR'] ?? '');
        return $cf !== '' && str_contains(strtolower($cf), 'https');
    }

    public static function requestPath(): string
    {
        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uriPath = '/' . trim(str_replace('\\', '/', $uriPath), '/');
        return $uriPath === '/' ? '/' : rtrim($uriPath, '/');
    }

    /**
     * Session cookie path: the app folder, never /public.
     * /public cookies are rejected on /login and cause "Invalid CSRF token."
     */
    public static function cookiePath(): string
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
        if ($dir === '' || $dir === '/' || $dir === '\\' || $dir === '.') {
            return '/';
        }
        if (str_ends_with($dir, '/public')) {
            $parent = substr($dir, 0, -7);
            return ($parent === '' || $parent === '/') ? '/' : $parent;
        }
        return $dir;
    }

    /**
     * URL prefix matching how the visitor reached the site.
     * Internal rewrites to public/index.php must not force /public into links.
     */
    public static function webBasePath(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $uri = self::requestPath();
        if (preg_match('#^((?:/[^/]+)*)/public(?:/|$)#', $uri, $m)) {
            $cached = (($m[1] ?? '') === '' ? '' : $m[1]) . '/public';
            return $cached;
        }

        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
        if ($dir === '/' || $dir === '\\' || $dir === '.') {
            $dir = '';
        }
        if ($dir !== '' && str_ends_with($dir, '/public')) {
            $dir = substr($dir, 0, -7);
        }
        $cached = ($dir === '/' || $dir === '\\') ? '' : $dir;
        return $cached;
    }

    /**
     * Resolve the public web base from the current request so assets work
     * whether the site is opened as /Kraved or /Kraved/public.
     */
    public static function detectedPublicBase(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host !== '') {
            $cached = (self::isHttps() ? 'https' : 'http') . '://' . $host . self::webBasePath();
            return $cached;
        }

        $cached = rtrim((string) self::config('url'), '/');
        return $cached;
    }

    public static function zoneChoiceLabel(array $zone): string
    {
        $prefix = strtoupper((string) ($zone['postcode_prefix'] ?? ''));
        $min = self::money((float) ($zone['min_order_amount'] ?? 0));
        return $prefix . ' · min ' . $min;
    }

    /**
     * CSS/JS/images live in public/assets. When public_html is the project root
     * (not the public/ folder), page URLs stay at / but files are under /public.
     */
    public static function detectedAssetBase(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $base = self::detectedPublicBase();
        $docRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        if ($docRoot === '') {
            $cached = $base;
            return $cached;
        }

        $webPath = rtrim(str_replace('\\', '/', (string) (parse_url($base, PHP_URL_PATH) ?: '')), '/');
        $fsBase = $docRoot . $webPath;

        if (!str_ends_with($webPath, '/public')
            && is_dir($fsBase . '/public/assets')
            && !is_dir($fsBase . '/assets')
        ) {
            $cached = $base . '/public';
            return $cached;
        }

        $cached = $base;
        return $cached;
    }

    public static function asset(string $path): string
    {
        $rel = ltrim($path, '/');
        $url = self::detectedAssetBase() . '/assets/' . $rel;
        $file = dirname(__DIR__, 2) . '/public/assets/' . $rel;
        if (is_file($file)) {
            $url .= '?v=' . filemtime($file);
        }
        return $url;
    }

    public static function upload(string $path): string
    {
        return self::detectedAssetBase() . '/uploads/' . ltrim($path, '/');
    }

    public static function logo(): string
    {
        $custom = self::setting('site_logo', null);
        if ($custom && is_string($custom) && trim($custom) !== '') {
            return self::upload(trim($custom));
        }
        return self::asset('images/logo-kraved.png');
    }

    public static function discountedPrice(float $price, string $type, float $value): float
    {
        if ($type === 'percent') {
            return round(max(0, $price * (1 - ($value / 100))), 2);
        }
        return round(max(0, $price - $value), 2);
    }

    public static function redirect(string $path): never
    {
        $url = str_starts_with($path, 'http') ? $path : self::baseUrl(ltrim($path, '/'));
        header('Location: ' . $url);
        exit;
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function setting(string $key, mixed $default = null): mixed
    {
        try {
            $val = \App\Models\Setting::get($key, null);
            if ($val !== null && $val !== '') {
                return $val;
            }
        } catch (\Throwable) {
            // settings table may not exist yet
        }
        return self::config($key, $default);
    }

    public static function money(float|string|null $amount): string
    {
        $sym = (string) self::setting('currency_symbol', self::config('currency_symbol', '£'));
        return $sym . number_format((float) $amount, 2);
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-') ?: 'item';
    }

    public static function csrfToken(): string
    {
        if (!Session::has('_csrf')) {
            Session::set('_csrf', bin2hex(random_bytes(32)));
        }
        return (string) Session::get('_csrf');
    }

    public static function csrfField(): string
    {
        $t = self::e(self::csrfToken());
        return '<input type="hidden" name="_csrf" value="' . $t . '">'
            . '<input type="hidden" name="csrf_token" value="' . $t . '">';
    }

    public static function verifyCsrf(?string $token): bool
    {
        $session = Session::get('_csrf');
        return is_string($token) && is_string($session) && hash_equals($session, $token);
    }

    public static function requestRaw(): string
    {
        static $raw = null;
        static $read = false;
        if ($read) {
            return $raw ?? '';
        }
        $read = true;
        $raw = file_get_contents('php://input') ?: '';
        return $raw;
    }

    public static function requestJson(): array
    {
        static $cached = null;
        static $decoded = false;
        if ($decoded) {
            return $cached ?? [];
        }
        $decoded = true;
        $parsed = json_decode(self::requestRaw(), true);
        $cached = is_array($parsed) ? $parsed : [];
        return $cached;
    }

    public static function requestCsrfToken(): ?string
    {
        $json = self::requestJson();
        $candidates = [
            $_POST['_csrf'] ?? null,
            $_POST['csrf_token'] ?? null,
            $json['_csrf'] ?? null,
            $json['csrf_token'] ?? null,
            $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null,
            $_SERVER['HTTP_X_CSRFTOKEN'] ?? null,
        ];
        foreach ($candidates as $token) {
            if (is_string($token) && $token !== '') {
                return $token;
            }
        }
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $key => $value) {
                if (strtolower((string) $key) === 'x-csrf-token' && is_string($value) && $value !== '') {
                    return $value;
                }
            }
        }
        return null;
    }

    public static function wantsJson(): bool
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $xhr = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
        return str_contains($accept, 'application/json')
            || $xhr === 'xmlhttprequest'
            || str_contains($contentType, 'application/json');
    }

    public static function requireCsrf(): void
    {
        $token = self::requestCsrfToken();
        if (self::verifyCsrf($token)) {
            return;
        }

        $message = 'Your session expired. Please refresh the page and try again.';
        if (self::wantsJson()) {
            self::json(['error' => $message], 419);
        }

        Session::flash('error', $message);
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $refHost = strtolower((string) (parse_url($referer, PHP_URL_HOST) ?: ''));
        if ($referer !== '' && $refHost !== '' && $refHost === $host) {
            header('Location: ' . $referer, true, 303);
            exit;
        }

        $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        header('Location: ' . ($path !== '' ? $path : '/'), true, 303);
        exit;
    }

    public static function json(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{"ok":false}';
        exit;
    }

    public static function view(string $path, array $data = [], ?string $layout = null): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $path) . '.php';
        if (!is_file($viewFile)) {
            http_response_code(500);
            exit('View not found: ' . $path);
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout) {
            $layoutFile = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $layout) . '.php';
            require $layoutFile;
            return;
        }

        echo $content;
    }

    public static function orderNumber(): string
    {
        $prefix = (string) self::setting('order_prefix', self::config('order_prefix', 'KRV'));
        return $prefix . strtoupper(bin2hex(random_bytes(3))) . date('His');
    }

    public static function postcodePrefix(string $postcode): string
    {
        $pc = strtoupper(preg_replace('/\s+/', '', $postcode) ?? '');
        if (preg_match('/^([A-Z]{1,2}\d{1,2}[A-Z]?)/', $pc, $m)) {
            // Prefer longer known prefixes later; return outward code without inward
            $outward = preg_replace('/\d[A-Z]{2}$/', '', $pc) ?? $pc;
            // Extract letter+digit prefix (e.g. E1, SW1, EC1, W1)
            if (preg_match('/^([A-Z]{1,2}\d{1,2})/', $outward, $m2)) {
                return $m2[1];
            }
            return $m[1];
        }
        return substr($pc, 0, 3);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'received'         => 'Order Received',
            'baking'           => 'In the Oven',
            'ready'            => 'Ready for Pickup',
            'out_for_delivery' => 'Out for Delivery',
            'completed'        => 'Completed',
            'cancelled'        => 'Cancelled',
            default            => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public static function paymentMethodLabel(string $method): string
    {
        return match ($method) {
            'card', 'stripe' => 'Card (Stripe)',
            default => 'Cash on delivery / collection',
        };
    }

    public static function paymentModeLabel(string $method): string
    {
        return match ($method) {
            'card', 'stripe' => 'Card',
            default => 'Cash',
        };
    }

    public static function paymentStatusLabel(string $status): string
    {
        return match ($status) {
            'paid' => 'Paid',
            'cod' => 'Pay on arrival',
            'failed' => 'Payment failed',
            default => 'Awaiting payment',
        };
    }

    public static function orderStatusFlow(string $fulfillmentType): array
    {
        return $fulfillmentType === 'delivery'
            ? ['received', 'baking', 'ready', 'out_for_delivery', 'completed']
            : ['received', 'baking', 'ready', 'completed'];
    }

    public static function orderStatusSteps(array $order): array
    {
        $flow = self::orderStatusFlow((string) ($order['fulfillment_type'] ?? ''));
        $current = (string) ($order['order_status'] ?? 'received');

        if ($current === 'cancelled') {
            return array_map(static fn (string $s): array => [
                'key'   => $s,
                'label' => self::statusLabel($s),
                'state' => 'cancelled',
            ], $flow);
        }

        $rank = [
            'received'         => 0,
            'baking'           => 1,
            'ready'            => 2,
            'out_for_delivery' => 3,
            'completed'        => 4,
        ];
        $currentRank = $rank[$current] ?? 0;
        $idx = 0;
        foreach ($flow as $i => $s) {
            if (($rank[$s] ?? 0) <= $currentRank) {
                $idx = $i;
            }
        }

        $steps = [];
        foreach ($flow as $i => $s) {
            $state = 'upcoming';
            if ($current === 'completed') {
                $state = 'done';
            } elseif ($i < $idx) {
                $state = 'done';
            } elseif ($i === $idx) {
                $state = 'current';
            }
            $steps[] = [
                'key'   => $s,
                'label' => self::statusLabel($s),
                'state' => $state,
            ];
        }
        return $steps;
    }
}
