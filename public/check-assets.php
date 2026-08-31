<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

use App\Core\Helpers;

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

$dir = __DIR__ . '/assets/images';
echo "KRAVED_ASSETS_CHECK\n";
echo 'public_base=' . Helpers::detectedPublicBase() . "\n";
echo 'asset_base=' . Helpers::detectedAssetBase() . "\n";
echo 'hero_url=' . Helpers::asset('images/hero-dessert.png') . "\n";
echo 'images_dir=' . $dir . "\n";
echo 'exists=' . (is_dir($dir) ? 'yes' : 'NO') . "\n\n";

$needed = [
    'hero-dessert.png',
    'product-lava-cake.png',
    'product-biscoff-cheesecake.png',
    'product-cookies-cream.png',
    'product-choc-waffle.png',
];

foreach ($needed as $file) {
    $path = $dir . '/' . $file;
    $ok = is_file($path);
    echo ($ok ? '[OK] ' : '[MISSING] ') . $file;
    if ($ok) {
        echo ' (' . filesize($path) . ' bytes)';
    }
    echo "\n";
}
