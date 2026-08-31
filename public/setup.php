<?php

declare(strict_types=1);

/**
 * One-time database setup for shared hosting.
 * Open: https://wiresforusdt.com/Kraved/public/setup.php
 * Delete this file after setup succeeds.
 */

$lockFile = dirname(__DIR__) . '/config/database.local.php';
$schemaFile = dirname(__DIR__) . '/database/schema.sql';
$done = is_file($lockFile);
$error = '';
$success = '';
$imported = false;

$host = $_POST['host'] ?? 'localhost';
$port = $_POST['port'] ?? '3306';
$dbname = $_POST['dbname'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$runSchema = isset($_POST['run_schema']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$done) {
    $host = trim((string) $host);
    $port = trim((string) $port);
    $dbname = trim((string) $dbname);
    $username = trim((string) $username);
    $password = (string) $password;

    if ($dbname === '' || $username === '') {
        $error = 'Database name and username are required.';
    } else {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Create DB if missing (user needs CREATE privilege — often already created in cPanel)
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $dbname) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo->exec('USE `' . str_replace('`', '``', $dbname) . '`');

            if ($runSchema && is_file($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                if ($sql === false) {
                    throw new RuntimeException('Could not read schema.sql');
                }
                // Strip CREATE DATABASE / USE so we stay on the chosen DB
                $sql = preg_replace('/^CREATE DATABASE.*?;\s*/mi', '', $sql) ?? $sql;
                $sql = preg_replace('/^USE\s+`?[^;`]+`?\s*;\s*/mi', '', $sql) ?? $sql;

                // Remove block comments and split on semicolons
                $sql = preg_replace('#/\*.*?\*/#s', '', $sql) ?? $sql;
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $statement) {
                    if ($statement === '' || str_starts_with($statement, '--')) {
                        continue;
                    }
                    // Skip pure comment-only chunks
                    $plain = trim(preg_replace('/^--.*$/m', '', $statement) ?? $statement);
                    if ($plain === '') {
                        continue;
                    }
                    $pdo->exec($statement);
                }
                $imported = true;
            }

            $export = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export([
                'host'     => $host,
                'port'     => $port,
                'dbname'   => $dbname,
                'username' => $username,
                'password' => $password,
                'charset'  => 'utf8mb4',
            ], true) . ";\n";

            if (file_put_contents($lockFile, $export) === false) {
                throw new RuntimeException('Could not write config/database.local.php — check folder permissions.');
            }

            $done = true;
            $success = $imported
                ? 'Connected and imported schema.sql successfully.'
                : 'Connected and saved credentials. Import database/schema.sql in phpMyAdmin if tables are missing.';
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, '1045') || str_contains($msg, 'Access denied')) {
                $error = $msg . "\n\n"
                    . "cPanel fix (required):\n"
                    . "1. MySQL® Databases → confirm user wirejybl_kraved exists\n"
                    . "2. Change password for that user to exactly: wirejybl_kraved\n"
                    . "3. Under “Add User To Database”, add wirejybl_kraved → wirejybl_kraved\n"
                    . "4. Tick ALL PRIVILEGES → Make Changes\n"
                    . "5. Wait 10 seconds, then try Save & Connect again\n"
                    . "Also try Host: 127.0.0.1 instead of localhost";
            } else {
                $error = $msg;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kraved Setup</title>
  <style>
    body { font-family: system-ui, sans-serif; background: #120b08; color: #f5e6d3; margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; }
    .card { width: min(480px, 100%); background: #1a100c; border: 1px solid rgba(245,230,211,.12); border-radius: 16px; padding: 1.5rem; }
    h1 { margin: 0 0 .35rem; font-size: 1.5rem; color: #d2a679; }
    p { color: #c4a890; font-size: .92rem; line-height: 1.45; }
    label { display: block; font-size: .8rem; margin: .75rem 0 .3rem; color: #c4a890; }
    input[type=text], input[type=password] { width: 100%; box-sizing: border-box; padding: .7rem .8rem; border-radius: 10px; border: 1px solid rgba(245,230,211,.15); background: #120b08; color: #f5e6d3; }
    .row { display: grid; grid-template-columns: 1fr 100px; gap: .6rem; }
    .check { display: flex; gap: .5rem; align-items: center; margin: 1rem 0; font-size: .9rem; }
    button, .btn { display: inline-block; margin-top: .5rem; background: #d2a679; color: #1a100c; border: 0; border-radius: 999px; padding: .75rem 1.2rem; font-weight: 700; cursor: pointer; text-decoration: none; }
    .err { background: #3a1515; color: #f0b0b0; padding: .75rem; border-radius: 10px; margin: .75rem 0; font-size: .88rem; white-space: pre-wrap; }
    .ok { background: #153a1f; color: #b0f0c0; padding: .75rem; border-radius: 10px; margin: .75rem 0; font-size: .88rem; }
    ol { padding-left: 1.2rem; color: #c4a890; font-size: .88rem; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Kraved Database Setup</h1>
    <p>Enter the MySQL details from cPanel → <strong>MySQL® Databases</strong>.</p>

    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($success): ?><div class="ok"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <?php if ($done): ?>
      <p>Config saved to <code>config/database.local.php</code>.</p>
      <p>If the storefront says tables are missing, import schema here:</p>
      <a class="btn" href="install-schema.php">Install Database Tables</a>
      <p style="margin-top:1rem"><a class="btn" href="./" style="background:transparent;border:1px solid #d2a679;color:#d2a679">Open Storefront</a></p>
      <p style="margin-top:1rem;font-size:.8rem">For security, delete <code>public/setup.php</code> and <code>public/install-schema.php</code> after confirming the site works.</p>
    <?php else: ?>
      <ol>
        <li>In cPanel create a database (e.g. <code>wirejybl_kraved</code>)</li>
        <li>Create a MySQL user and add it to that database with ALL PRIVILEGES</li>
        <li>Paste the full names below (cPanel usually prefixes with <code>wirejybl_</code>)</li>
      </ol>
      <form method="post">
        <label>Host</label>
        <input type="text" name="host" value="<?= htmlspecialchars($host, ENT_QUOTES, 'UTF-8') ?>" required>
        <div class="row">
          <div>
            <label>Database name</label>
            <input type="text" name="dbname" value="<?= htmlspecialchars($dbname, ENT_QUOTES, 'UTF-8') ?>" placeholder="wirejybl_kraved" required>
          </div>
          <div>
            <label>Port</label>
            <input type="text" name="port" value="<?= htmlspecialchars($port, ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>
        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" placeholder="wirejybl_kraved" required>
        <label>Password</label>
        <input type="password" name="password" value="<?= htmlspecialchars($password, ENT_QUOTES, 'UTF-8') ?>">
        <label class="check">
          <input type="checkbox" name="run_schema" value="1" checked>
          Import schema + sample products now
        </label>
        <button type="submit">Save &amp; Connect</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
