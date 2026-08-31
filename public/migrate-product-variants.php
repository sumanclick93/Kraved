<?php

declare(strict_types=1);

/**
 * One-click migration: product_images + product_variants tables.
 * Open: /public/migrate-product-variants.php
 * Click Run, then DELETE this file after success.
 */

$configFile = dirname(__DIR__) . '/config/database.local.php';
$fallback = dirname(__DIR__) . '/config/database.php';
$schemaFile = dirname(__DIR__) . '/database/alter_product_images_variants.sql';

$error = '';
$success = '';
$log = [];
$hasImages = false;
$hasVariants = false;
$imageCount = 0;
$variantCount = 0;

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
        if (!is_file($schemaFile)) {
            throw new RuntimeException('Missing alter file: ' . $schemaFile);
        }
        $sql = file_get_contents($schemaFile);
        if ($sql === false) {
            throw new RuntimeException('Cannot read alter file');
        }

        $sql = preg_replace('#/\*.*?\*/#s', '', $sql) ?? $sql;
        foreach (explode(';', $sql) as $chunk) {
            $lines = preg_split("/\r\n|\n|\r/", $chunk) ?: [];
            $kept = [];
            foreach ($lines as $line) {
                if (preg_match('/^\s*--/', $line)) {
                    continue;
                }
                $kept[] = $line;
            }
            $chunk = trim(implode("\n", $kept));
            if ($chunk === '') {
                continue;
            }
            $pdo->exec($chunk);
            $log[] = substr($chunk, 0, 100) . (strlen($chunk) > 100 ? '…' : '');
        }
        $success = 'product_images + product_variants created. Delete migrate-product-variants.php now.';
    }

    $hasImages = (bool) $pdo->query("SHOW TABLES LIKE 'product_images'")->fetchColumn();
    $hasVariants = (bool) $pdo->query("SHOW TABLES LIKE 'product_variants'")->fetchColumn();
    $imageCount = $hasImages
        ? (int) $pdo->query('SELECT COUNT(*) FROM product_images')->fetchColumn()
        : 0;
    $variantCount = $hasVariants
        ? (int) $pdo->query('SELECT COUNT(*) FROM product_variants')->fetchColumn()
        : 0;
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kraved Product Variants Migrate</title>
  <style>
    body{font-family:system-ui,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;background:#fff8f3;color:#2a1710}
    .card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 10px 30px rgba(61,35,24,.08)}
    .ok{color:#2f9e5f}.err{color:#d45454}
    button,.btn{background:#c47a35;color:#fff;border:0;padding:10px 18px;border-radius:999px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block}
    pre{background:#f7e8dc;padding:12px;border-radius:10px;overflow:auto;font-size:12px}
  </style>
</head>
<body>
  <div class="card">
    <h1>Product images &amp; variants migration</h1>
    <p>Creates <code>product_images</code> and <code>product_variants</code>, and backfills existing product cover images.</p>
    <?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="ok"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <p>
      Status:<br>
      product_images — <?= $hasImages ? "ready ($imageCount rows)" : 'missing' ?><br>
      product_variants — <?= $hasVariants ? "ready ($variantCount rows)" : 'missing' ?>
    </p>
    <?php if (!$hasImages || !$hasVariants): ?>
      <form method="post"><button type="submit">Run migration</button></form>
    <?php else: ?>
      <p class="ok">Tables exist. You can delete this migrate file.</p>
      <a class="btn" href="index.php">Open storefront</a>
    <?php endif; ?>
    <?php if ($log): ?><h3>Log</h3><pre><?= htmlspecialchars(implode("\n", $log)) ?></pre><?php endif; ?>
  </div>
</body>
</html>
