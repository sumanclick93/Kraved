<?php

declare(strict_types=1);

/**
 * Applies Colne / Kraved Desserts brand copy to an existing database.
 * Open: /public/migrate-brand.php?run=1
 * DELETE this file after success.
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

use App\Models\Setting;
use App\Models\SiteSection;

$configFile = dirname(__DIR__) . '/config/database.local.php';
$fallback = dirname(__DIR__) . '/config/database.php';

$error = '';
$success = '';
$log = [];

try {
    $cfg = require (is_file($configFile) ? $configFile : $fallback);
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $cfg['host'],
        $cfg['port'] ?? '3306',
        $cfg['dbname']
    );
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $shouldRun = $_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['run']);

    if ($shouldRun) {
        $settings = [
            'store_name' => 'Kraved',
            'store_tagline' => 'Taste the sweetness in every bite.',
            'collection_address' => 'Unit 2, Old Biscuit Factory, Dockray Street, Colne, BB8 9HT',
            'contact_email' => 'hello@kraveddesserts.com',
            'contact_phone' => '01282 943888',
            'contact_hours' => 'Mon–Fri 5pm to 11pm · Sat & Sun 2pm to 11pm',
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value]);
            $log[] = 'settings.' . $key;
        }

        $defaults = SiteSection::defaults();
        $keys = ['hero', 'about', 'trust_bar', 'footer'];
        $insert = $pdo->prepare(
            'INSERT INTO site_sections (section_key, label, content_json, is_visible, display_order)
             VALUES (?, ?, ?, 1, ?)
             ON DUPLICATE KEY UPDATE content_json = VALUES(content_json), updated_at = NOW()'
        );

        $order = ['hero' => 20, 'about' => 60, 'trust_bar' => 70, 'footer' => 90];
        foreach ($keys as $key) {
            $section = $defaults[$key] ?? null;
            if (!$section) {
                continue;
            }
            $content = $section['content'];
            if ($key === 'footer') {
                $row = $pdo->query("SELECT content_json FROM site_sections WHERE section_key = 'footer'")->fetch(PDO::FETCH_ASSOC);
                $existing = $row ? json_decode((string) $row['content_json'], true) : [];
                if (is_array($existing) && !empty($existing['social'])) {
                    $content['social'] = $existing['social'];
                }
            }
            $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $insert->execute([$key, $section['label'], $json, $order[$key] ?? 0]);
            $log[] = 'site_sections.' . $key;
        }

        Setting::clearCache();
        SiteSection::clearCache();
        $success = 'Brand copy, contact details and hours have been applied. Delete migrate-brand.php now.';
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kraved Brand Update</title>
  <style>
    body{font-family:system-ui,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;background:#f6efe4;color:#2c1810}
    .card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 10px 30px rgba(44,24,16,.08)}
    .ok{color:#2f9e5f}.err{color:#d45454}
    button,.btn{background:#3d2418;color:#fff;border:0;padding:10px 18px;border-radius:999px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block}
    pre{background:#efe4d4;padding:12px;border-radius:10px;overflow:auto;font-size:12px}
  </style>
</head>
<body>
  <div class="card">
    <h1>Kraved Desserts brand update</h1>
    <p>Updates store settings and homepage copy to match the Colne brochure (address, hours, About Us, services).</p>
    <?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="ok"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <?php if (!$success): ?>
      <form method="post"><button type="submit">Apply brand update</button></form>
    <?php else: ?>
      <a class="btn" href="/">View storefront</a>
    <?php endif; ?>
    <?php if ($log): ?><h3>Updated</h3><pre><?= htmlspecialchars(implode("\n", $log)) ?></pre><?php endif; ?>
  </div>
</body>
</html>
