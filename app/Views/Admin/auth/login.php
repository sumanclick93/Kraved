<?php use App\Core\Helpers; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= Helpers::e(Helpers::csrfToken()) ?>">
  <title>Admin Login — Kraved</title>
  <link rel="icon" type="image/png" href="<?= Helpers::logo() ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= Helpers::asset('css/admin.css') ?>" rel="stylesheet">
</head>
<body class="admin-login-page d-flex align-items-center justify-content-center min-vh-100">
  <div class="card shadow login-card p-4 text-center">
    <img class="login-logo mx-auto mb-3" src="<?= Helpers::logo() ?>" alt="Kraved" width="96" height="96">
    <p class="text-muted mb-4">Admin Panel</p>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger text-start"><?= Helpers::e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= Helpers::formAction('admin/login') ?>" class="text-start">
      <?= Helpers::csrfField() ?>
      <div class="mb-3">
        <label class="form-label" for="admin-email">Email</label>
        <input id="admin-email" type="email" name="email" class="form-control" required maxlength="120" autocomplete="username" value="<?= Helpers::e($oldEmail ?? 'admin@kraved.local') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="admin-password">Password</label>
        <input id="admin-password" type="password" name="password" class="form-control" required autocomplete="current-password">
      </div>
      <button class="btn btn-warning w-100" type="submit">Sign in</button>
    </form>
  </div>
</body>
</html>
