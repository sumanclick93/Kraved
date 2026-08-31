<?php
use App\Core\Helpers;

$orderCount = count($orders ?? []);
?>
<section class="section account-section">
  <div class="container account-page">
    <header class="account-hero">
      <p class="account-kicker">Account</p>
      <div class="account-hero-row">
        <div>
          <h1 class="section-title">My orders</h1>
          <p class="section-lead">Every collection and delivery, with live status and a downloadable invoice.</p>
        </div>
        <?php if ($orderCount > 0): ?>
          <p class="account-count"><?= $orderCount ?> <?= $orderCount === 1 ? 'order' : 'orders' ?></p>
        <?php endif; ?>
      </div>
    </header>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= Helpers::e($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= Helpers::e($success) ?></div>
    <?php endif; ?>

    <div class="account-layout">
      <?php require dirname(__DIR__, 2) . '/Partials/account-nav.php'; ?>

      <?php if (empty($orders)): ?>
        <div class="orders-empty">
          <div class="orders-empty-mark" aria-hidden="true">
            <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
              <path d="M6 7h12l-1.1 12.2a2 2 0 0 1-2 1.8H9.1a2 2 0 0 1-2-1.8L6 7Z"/>
              <path d="M9 7V5.4A3 3 0 0 1 12 2.5 3 3 0 0 1 15 5.4V7"/>
              <path d="M9.5 12h5"/>
            </svg>
          </div>
          <h2 class="orders-empty-title">Your pastry box is empty</h2>
          <p>When you order a cookie box, it will land here as a ticket you can track — with live status and an invoice.</p>
          <a class="btn btn-accent mt-3" href="<?= Helpers::baseUrl('#menu') ?>">Order something sweet</a>
        </div>
      <?php else: ?>
        <ul class="order-ticket-list">
          <?php foreach ($orders as $index => $o):
            $placed = strtotime((string) $o['created_at']);
            $count = (int) ($o['item_count'] ?? 0);
            $names = array_values(array_filter(explode('||', (string) ($o['item_names'] ?? ''))));
            $preview = array_slice($names, 0, 2);
            $extra = max(0, count($names) - count($preview));
            $isDelivery = ($o['fulfillment_type'] ?? '') === 'delivery';
            $slot = trim((string) ($o['delivery_time_slot'] ?? '')) ?: 'ASAP';
            $status = (string) $o['order_status'];
          ?>
            <li class="order-ticket" style="--ticket-delay: <?= min($index, 6) * 70 ?>ms">
              <a class="order-ticket-body" href="<?= Helpers::baseUrl('order/' . $o['order_number']) ?>">
                <div class="order-ticket-top">
                  <span class="order-status-pill <?= Helpers::e($status) ?>">
                    <?= Helpers::e(Helpers::statusLabel($status)) ?>
                  </span>
                  <span class="order-fulfill-chip">
                    <?php if ($isDelivery): ?>
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/><path d="M5 17H3V7l3-3h6l2 3h4v10h-2"/><path d="M9 4v3h5"/></svg>
                      Delivery
                    <?php else: ?>
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M6 7h12l-1 12H7L6 7Z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/></svg>
                      Collection
                    <?php endif; ?>
                  </span>
                </div>

                <p class="order-ticket-number"><?= Helpers::e($o['order_number']) ?></p>
                <p class="order-ticket-when">
                  <?= $placed ? date('j M Y · g:ia', $placed) : 'Placed recently' ?>
                </p>

                <?php if ($preview): ?>
                  <p class="order-ticket-items">
                    <?= Helpers::e(implode(' · ', $preview)) ?>
                    <?php if ($extra > 0): ?>
                      <span>+<?= $extra ?> more</span>
                    <?php endif; ?>
                  </p>
                <?php endif; ?>

                <div class="order-ticket-perf" aria-hidden="true"></div>

                <div class="order-ticket-foot">
                  <div class="order-ticket-facts">
                    <span><?= $count ?> item<?= $count === 1 ? '' : 's' ?></span>
                    <span><?= Helpers::e(strtoupper($slot) === 'ASAP' ? 'ASAP' : $slot) ?></span>
                  </div>
                  <strong class="order-ticket-total"><?= Helpers::money($o['total_amount']) ?></strong>
                </div>
              </a>

              <div class="order-ticket-actions">
                <a class="btn btn-accent" href="<?= Helpers::baseUrl('order/' . $o['order_number']) ?>">View details</a>
                <a class="btn btn-ghost" href="<?= Helpers::baseUrl('order/' . $o['order_number'] . '/invoice') ?>">Download invoice</a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</section>
