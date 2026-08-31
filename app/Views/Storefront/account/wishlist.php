<?php use App\Core\Helpers; ?>
<section class="section account-section">
  <div class="container account-page">
    <header class="account-hero">
      <p class="account-kicker">Account</p>
      <h1 class="section-title">Wishlist</h1>
      <p class="section-lead">Bakes you saved for later — tap the heart on any product to add more.</p>
    </header>

    <div class="account-layout">
      <?php require dirname(__DIR__, 2) . '/Partials/account-nav.php'; ?>
      <div>
        <?php if (empty($products)): ?>
          <div class="orders-empty">
            <h2 class="orders-empty-title">Nothing saved yet</h2>
            <p>Browse the menu and tap the heart on a cookie you want to keep close.</p>
            <a class="btn btn-accent mt-3" href="<?= Helpers::baseUrl('#menu') ?>">Browse the menu</a>
          </div>
        <?php else: ?>
          <div class="product-grid">
            <?php foreach ($products as $p): ?>
              <?php require dirname(__DIR__, 2) . '/Partials/product-card.php'; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
