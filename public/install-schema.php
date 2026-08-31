<?php

declare(strict_types=1);

/**
 * One-click table installer for shared hosting.
 * Opens and can auto-run: /Kraved/public/install-schema.php?run=1
 * DELETE this file after success.
 */

$configFile = dirname(__DIR__) . '/config/database.local.php';
$fallback = dirname(__DIR__) . '/config/database.php';
$schemaFile = dirname(__DIR__) . '/database/install.sql';
if (!is_file($schemaFile)) {
    $schemaFile = dirname(__DIR__) . '/database/schema.sql';
}

$error = '';
$success = '';
$log = [];
$tables = [];
$dbName = '';

function kraved_cfg(string $local, string $fallback): array
{
    return require (is_file($local) ? $local : $fallback);
}

function kraved_statements(string $sql): array
{
    // Never allow switching databases on shared hosting
    $sql = preg_replace('/CREATE\s+DATABASE\b.*?;/is', '', $sql) ?? $sql;
    $sql = preg_replace('/^\s*USE\s+[`\w]+;\s*/im', '', $sql) ?? $sql;
    $sql = preg_replace('#/\*.*?\*/#s', '', $sql) ?? $sql;

    $out = [];
    foreach (explode(';', $sql) as $chunk) {
        $chunk = trim($chunk);
        // Drop full-line SQL comments
        $lines = preg_split("/\r\n|\n|\r/", $chunk) ?: [];
        $kept = [];
        foreach ($lines as $line) {
            if (preg_match('/^\s*--/', $line)) {
                continue;
            }
            $kept[] = $line;
        }
        $chunk = trim(implode("\n", $kept));
        if ($chunk !== '') {
            $out[] = $chunk;
        }
    }
    return $out;
}

try {
    $cfg = kraved_cfg($configFile, $fallback);
    $dbName = (string) $cfg['dbname'];
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $cfg['host'],
        $cfg['port'] ?? '3306',
        $dbName
    );
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Stay on the configured database
    $pdo->exec('USE `' . str_replace('`', '``', $dbName) . '`');

    $shouldRun = $_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['run']);

    if ($shouldRun) {
        if (!is_file($schemaFile)) {
            throw new RuntimeException('Missing schema file: ' . $schemaFile);
        }
        $sql = file_get_contents($schemaFile);
        if ($sql === false) {
            throw new RuntimeException('Cannot read schema file');
        }

        $statements = kraved_statements($sql);
        $ok = 0;
        foreach ($statements as $i => $statement) {
            try {
                // Re-assert DB before each statement
                $pdo->exec('USE `' . str_replace('`', '``', $dbName) . '`');
                $pdo->exec($statement);
                $ok++;
                $log[] = 'OK #' . ($i + 1) . ': ' . substr(preg_replace('/\s+/', ' ', $statement) ?? '', 0, 60) . '…';
            } catch (Throwable $stmtErr) {
                throw new RuntimeException(
                    'Failed on statement #' . ($i + 1) . ': ' . $stmtErr->getMessage()
                    . "\n\nSQL:\n" . substr($statement, 0, 400),
                    0,
                    $stmtErr
                );
            }
        }
        $success = "Imported into `{$dbName}` — {$ok} statements OK.";
    }

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$hasCategories = in_array('categories', $tables, true);
$hasProducts = in_array('products', $tables, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Install Kraved Tables</title>
  <style>
    body{font-family:system-ui,sans-serif;background:#120b08;color:#f5e6d3;margin:0;min-height:100vh;display:grid;place-items:center;padding:1.5rem}
    .card{width:min(640px,100%);background:#1a100c;border:1px solid rgba(245,230,211,.12);border-radius:16px;padding:1.5rem}
    h1{margin:0 0 .5rem;color:#d2a679}
    p,.meta{color:#c4a890;font-size:.92rem;line-height:1.45}
    .ok{background:#153a1f;color:#b0f0c0;padding:.75rem;border-radius:10px;margin:.75rem 0;white-space:pre-wrap}
    .err{background:#3a1515;color:#f0b0b0;padding:.75rem;border-radius:10px;margin:.75rem 0;white-space:pre-wrap;font-size:.85rem}
    .btn{display:inline-block;margin:.35rem .35rem 0 0;background:#d2a679;color:#1a100c;border:0;border-radius:999px;padding:.75rem 1.2rem;font-weight:700;cursor:pointer;text-decoration:none}
    code{color:#d2a679}
    .log{max-height:180px;overflow:auto;font:12px/1.4 ui-monospace,monospace;background:#120b08;padding:.75rem;border-radius:8px}
  </style>
</head>
<body>
  <div class="card">
    <h1>Install Kraved Tables</h1>
    <p class="meta">Target database: <code><?= htmlspecialchars($dbName ?: '(unknown)', ENT_QUOTES, 'UTF-8') ?></code></p>
    <p>This creates tables inside <strong>your</strong> cPanel database (not a separate <code>kraved</code> DB).</p>

    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($success): ?><div class="ok"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <p><strong>Tables:</strong> <?= $tables ? htmlspecialchars(implode(', ', $tables), ENT_QUOTES, 'UTF-8') : '(none yet)' ?></p>

    <?php if ($log): ?>
      <div class="log"><?php foreach ($log as $line): ?><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?><br><?php endforeach; ?></div>
    <?php endif; ?>

    <?php if ($hasCategories && $hasProducts): ?>
      <a class="btn" href="./">Open Storefront</a>
      <p class="meta" style="margin-top:1rem">Delete <code>install-schema.php</code> and <code>setup.php</code> now.</p>
    <?php else: ?>
      <form method="post">
        <button class="btn" type="submit">Create tables + sample products</button>
      </form>
      <p class="meta">Or open: <code>install-schema.php?run=1</code></p>
    <?php endif; ?>
  </div>
</body>
</html>
