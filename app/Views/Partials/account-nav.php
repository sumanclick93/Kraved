<?php
use App\Core\Helpers;

$accountPage = $accountPage ?? '';
$tabs = [
    [
        'key'   => 'profile',
        'href'  => Helpers::baseUrl('account'),
        'label' => 'Profile',
        'icon'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="8" r="3.4"/><path d="M5 19c1.5-3.4 4-5 7-5s5.5 1.6 7 5"/></svg>',
    ],
    [
        'key'   => 'orders',
        'href'  => Helpers::baseUrl('orders'),
        'label' => 'My orders',
        'icon'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M7 4h10v16l-5-2-5 2V4Z"/><path d="M9 8h6M9 12h6"/></svg>',
    ],
    [
        'key'   => 'wishlist',
        'href'  => Helpers::baseUrl('wishlist'),
        'label' => 'Wishlist',
        'icon'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 20s-7-4.6-9.2-8.2C1 9.4 2.2 6 5.5 6c1.8 0 3.1 1 3.8 2.2C10.1 7 11.4 6 13.2 6c3.3 0 4.5 3.4 2.7 5.8C13.7 15.4 12 20 12 20Z"/></svg>',
    ],
];
?>
<nav class="account-nav" aria-label="Account">
  <?php foreach ($tabs as $tab):
      $active = $accountPage === $tab['key'];
  ?>
    <a class="<?= $active ? 'active' : '' ?>" href="<?= $tab['href'] ?>" <?= $active ? 'aria-current="page"' : '' ?>>
      <span class="account-nav-icon" aria-hidden="true"><?= $tab['icon'] ?></span>
      <?= Helpers::e($tab['label']) ?>
    </a>
  <?php endforeach; ?>
  <a class="account-nav-logout" href="<?= Helpers::baseUrl('logout') ?>">
    <span class="account-nav-icon" aria-hidden="true">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M10 7V5a2 2 0 0 1 2-2h7v18h-7a2 2 0 0 1-2-2v-2"/><path d="M4 12h11M7 9l-3 3 3 3"/></svg>
    </span>
    Log out
  </a>
</nav>
