<?php
use App\Core\AuthMiddleware;
use App\Core\Helpers;
use App\Models\SiteSection;
use App\Models\Wishlist;

$user = AuthMiddleware::user();
$f = $fulfillment ?? ['type' => 'collection', 'postcode' => '', 'time_slot' => 'ASAP'];
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isHome = str_ends_with(rtrim($currentPath, '/'), '') || preg_match('#/(public)?/?$#', $currentPath);
$isOrders = (bool) preg_match('#/orders?(?:/|$)#', $currentPath);
$isAccount = (bool) preg_match('#/(account|wishlist)(?:/|$)#', $currentPath);
$nav = ($cms['nav']['content'] ?? null) ?: SiteSection::content('nav');
$navLinks = $nav['links'] ?? [];
$showNav = !isset($cms['nav']) || !empty($cms['nav']['is_visible']);
$wishlistCount = $user ? count(Wishlist::currentProductIds()) : 0;
?>
<header class="site-nav">
  <div class="container site-nav-inner">
    <a class="brand-logo" href="<?= Helpers::baseUrl() ?>" aria-label="<?= Helpers::e((string) Helpers::setting('store_name', 'Kraved')) ?> Home">
      <img
        class="brand-logo-img"
        src="<?= Helpers::logo() ?>"
        alt="<?= Helpers::e((string) Helpers::setting('store_name', 'Kraved')) ?>"
        width="148"
        height="148"
      >
    </a>

    <button class="nav-toggle d-lg-none" type="button" id="nav-toggle" aria-label="Open menu">
      <span></span><span></span><span></span>
    </button>

    <?php if ($showNav): ?>
    <nav class="main-nav" id="main-nav">
      <?php foreach ($navLinks as $link):
        $href = SiteSection::resolveHref($link['href'] ?? '/');
        $active = ($isHome && (($link['href'] ?? '') === '/' || ($link['href'] ?? '') === ''));
      ?>
        <a href="<?= Helpers::e($href) ?>" class="<?= $active ? 'active' : '' ?>"><?= Helpers::e($link['label'] ?? '') ?></a>
      <?php endforeach; ?>
    </nav>
    <?php else: ?>
    <nav class="main-nav" id="main-nav"></nav>
    <?php endif; ?>

    <div class="nav-actions">
      <?php if ($user): ?>
        <a class="nav-orders-link <?= $isOrders ? 'active' : '' ?>" href="<?= Helpers::baseUrl('orders') ?>">Orders</a>
        <a class="nav-icon-btn" href="<?= Helpers::baseUrl('wishlist') ?>" aria-label="Wishlist" title="Wishlist">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20s-7-4.6-9.2-8.2C1 9.4 2.2 6 5.5 6c1.8 0 3.1 1 3.8 2.2C10.1 7 11.4 6 13.2 6c3.3 0 4.5 3.4 2.7 5.8C13.7 15.4 12 20 12 20Z"/></svg>
          <span class="cart-badge" id="wish-badge"><?= (int) $wishlistCount ?></span>
        </a>
        <div class="dropdown">
          <button
            class="nav-icon-btn <?= $isAccount ? 'is-active' : '' ?>"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="Account menu"
            title="<?= Helpers::e($user['name']) ?>"
          >
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path d="M5 19c1.5-3.5 4-5 7-5s5.5 1.5 7 5"/></svg>
          </button>
          <ul class="dropdown-menu dropdown-menu-end account-menu">
            <li class="account-menu-name"><?= Helpers::e($user['name']) ?></li>
            <li><a class="dropdown-item" href="<?= Helpers::baseUrl('account') ?>">Profile</a></li>
            <li><a class="dropdown-item" href="<?= Helpers::baseUrl('orders') ?>">My orders</a></li>
            <li><a class="dropdown-item" href="<?= Helpers::baseUrl('wishlist') ?>">Wishlist</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= Helpers::baseUrl('logout') ?>">Log out</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a class="nav-icon-btn" href="<?= Helpers::baseUrl('login') ?>" title="Account">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path d="M5 19c1.5-3.5 4-5 7-5s5.5 1.5 7 5"/></svg>
        </a>
      <?php endif; ?>
      <button type="button" class="nav-icon-btn cart-btn" id="open-cart" aria-label="Open basket">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 7h12l-1.2 11.2a2 2 0 0 1-2 1.8H9.2a2 2 0 0 1-2-1.8L6 7Z"/><path d="M9 7V5.5A3 3 0 0 1 12 2.5 3 3 0 0 1 15 5.5V7"/></svg>
        <span class="cart-badge" id="cart-badge"><?= (int)($cartCount ?? 0) ?></span>
      </button>
    </div>
  </div>
</header>
