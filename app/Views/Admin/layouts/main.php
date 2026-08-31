<?php
use App\Core\AuthMiddleware;
use App\Core\Helpers;
$user = AuthMiddleware::admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= Helpers::e(Helpers::csrfToken()) ?>">
  <title><?= Helpers::e($title ?? 'Admin') ?> — Kraved Admin</title>
  <link rel="icon" type="image/png" href="<?= Helpers::logo() ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= Helpers::asset('css/admin.css') ?>" rel="stylesheet">
</head>
<body class="admin-body">
<nav class="navbar admin-topnav">
  <div class="container-fluid px-3">
    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-sm btn-outline-dark d-md-none me-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminMobileNav" aria-controls="adminMobileNav" aria-label="Open Admin Menu">
        <span class="fw-bold">☰ Menu</span>
      </button>
      <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="<?= Helpers::baseUrl('admin') ?>">
        <img class="admin-logo" src="<?= Helpers::logo() ?>" alt="Kraved" width="40" height="40">
        <span class="admin-brand-text d-none d-sm-inline">Admin</span>
      </a>
    </div>
    <div class="d-flex align-items-center gap-2 small ms-auto">
      <?php if (!empty($user['name'])): ?>
        <span class="admin-user d-none d-md-inline opacity-75 me-1"><?= Helpers::e($user['name']) ?></span>
      <?php endif; ?>
      <a class="btn btn-sm btn-outline-secondary" href="<?= Helpers::baseUrl() ?>" target="_blank">View Store</a>
      <a class="btn btn-sm btn-warning" href="<?= Helpers::baseUrl('admin/logout') ?>">Logout</a>
    </div>
  </div>
</nav>

<!-- Mobile Offcanvas Navigation Drawer -->
<div class="offcanvas offcanvas-start bg-dark text-white d-md-none" tabindex="-1" id="adminMobileNav" aria-labelledby="adminMobileNavLabel" style="max-width:280px;background:#2a1710 !important">
  <div class="offcanvas-header border-bottom border-secondary border-opacity-25">
    <div class="d-flex align-items-center gap-2">
      <img src="<?= Helpers::logo() ?>" alt="Kraved" width="36" height="36" class="rounded-circle">
      <h5 class="offcanvas-title text-white h6 mb-0" id="adminMobileNavLabel">Kraved Admin</h5>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-3">
    <ul class="nav flex-column gap-1 admin-mobile-nav-list">
      <li><a class="nav-link text-white-50 py-2" href="<?= Helpers::baseUrl('admin') ?>">Dashboard</a></li>
      <li><a class="nav-link text-white-50 py-2" href="<?= Helpers::baseUrl('admin/orders') ?>">Orders</a></li>
      <li><a class="nav-link text-white-50 py-2" href="<?= Helpers::baseUrl('admin/products') ?>">Products</a></li>
      <li><a class="nav-link text-white-50 py-2" href="<?= Helpers::baseUrl('admin/offers') ?>">Offers &amp; promos</a></li>
      <li><a class="nav-link text-white-50 py-2" href="<?= Helpers::baseUrl('admin/categories') ?>">Categories</a></li>
      <li><a class="nav-link text-white-50 py-2" href="<?= Helpers::baseUrl('admin/addons') ?>">Add-ons</a></li>
      <li><a class="nav-link text-white-50 py-2" href="<?= Helpers::baseUrl('admin/delivery-zones') ?>">Delivery Zones</a></li>
      <li><a class="nav-link text-white-50 py-2" href="<?= Helpers::baseUrl('admin/cms') ?>">Homepage CMS</a></li>
      <li><a class="nav-link text-white-50 py-2" href="<?= Helpers::baseUrl('admin/settings') ?>">Store Settings</a></li>
    </ul>
  </div>
</div>

<div class="d-flex">
  <aside class="admin-sidebar p-3 d-none d-md-block">
    <div class="admin-side-brand mb-3">
      <img src="<?= Helpers::logo() ?>" alt="Kraved" width="72" height="72">
    </div>
    <ul class="nav flex-column gap-1">
      <li><a class="nav-link" href="<?= Helpers::baseUrl('admin') ?>">Dashboard</a></li>
      <li><a class="nav-link" href="<?= Helpers::baseUrl('admin/orders') ?>">Orders</a></li>
      <li><a class="nav-link" href="<?= Helpers::baseUrl('admin/products') ?>">Products</a></li>
      <li><a class="nav-link" href="<?= Helpers::baseUrl('admin/offers') ?>">Offers &amp; promos</a></li>
      <li><a class="nav-link" href="<?= Helpers::baseUrl('admin/categories') ?>">Categories</a></li>
      <li><a class="nav-link" href="<?= Helpers::baseUrl('admin/addons') ?>">Add-ons</a></li>
      <li><a class="nav-link" href="<?= Helpers::baseUrl('admin/delivery-zones') ?>">Delivery Zones</a></li>
      <li><a class="nav-link" href="<?= Helpers::baseUrl('admin/cms') ?>">Homepage CMS</a></li>
      <li><a class="nav-link" href="<?= Helpers::baseUrl('admin/settings') ?>">Store Settings</a></li>
    </ul>
  </aside>
  <main class="admin-main flex-grow-1 p-4">
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= Helpers::e($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= Helpers::e($error) ?></div>
    <?php endif; ?>
    <?= $content ?>
  </main>
</div>
<script>
  window.KRAVED = {
    baseUrl: <?= json_encode(Helpers::baseUrl()) ?>,
    csrf: <?= json_encode(Helpers::csrfToken()) ?>
  };
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= Helpers::asset('js/admin.js') ?>"></script>
</body>
</html>
