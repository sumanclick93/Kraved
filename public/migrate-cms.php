<?php

declare(strict_types=1);

/**
 * One-click CMS migration for existing Kraved databases.
 * Open: /public/migrate-cms.php?run=1
 * DELETE this file after success.
 */

$configFile = dirname(__DIR__) . '/config/database.local.php';
$fallback = dirname(__DIR__) . '/config/database.php';
$schemaFile = dirname(__DIR__) . '/database/alter_cms.sql';

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
            $log[] = substr($chunk, 0, 80) . (strlen($chunk) > 80 ? '…' : '');
        }
        $success = 'CMS tables/settings applied successfully. Delete migrate-cms.php now.';
    }

    $hasSections = (bool) $pdo->query("SHOW TABLES LIKE 'site_sections'")->fetchColumn();
    $count = $hasSections
        ? (int) $pdo->query('SELECT COUNT(*) FROM site_sections')->fetchColumn()
        : 0;
} catch (Throwable $e) {
    $error = $e->getMessage();
    $hasSections = false;
    $count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kraved CMS Migrate</title>
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
    <h1>Homepage CMS migration</h1>
    <p>Creates <code>site_sections</code> and seeds editable homepage content.</p>
    <?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="ok"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <p>Status: <?= $hasSections ? "site_sections ready ($count rows)" : 'site_sections missing' ?></p>
    <?php if (!$success): ?>
      <form method="post"><button type="submit">Run migration</button></form>
    <?php else: ?>
      <a class="btn" href="index.php/admin/cms">Open CMS admin</a>
    <?php endif; ?>
    <?php if ($log): ?><h3>Log</h3><pre><?= htmlspecialchars(implode("\n", $log)) ?></pre><?php endif; ?>
  </div>
</body>
</html>
