<?php
use App\Core\Helpers;

$placed = strtotime((string) $order['created_at']);
$heading = match ($order['order_status']) {
    'cancelled' => 'Order cancelled',
    'completed' => 'Order complete',
    'received'  => 'Order confirmed',
    default     => 'Order details',
};
$payMethod = Helpers::paymentMethodLabel((string) ($order['payment_method'] ?? ''));
$payStatus = Helpers::paymentStatusLabel((string) ($order['payment_status'] ?? ''));
?>
<section class="section pt-4">
  <div class="container order-details-page">
    <p class="text-uppercase small letter-spaced opacity-75 mb-1"><?= Helpers::e($heading) ?></p>
    <div class="order-details-head">
      <div>
        <h1 class="section-title mb-1"><?= Helpers::e($order['order_number']) ?></h1>
        <p class="section-lead mb-0">
          Thanks <?= Helpers::e($order['customer_name']) ?> —
          <?= $order['order_status'] === 'cancelled' ? 'this one was cancelled.' : "we're on it." ?>
        </p>
      </div>
      <span class="order-status-pill <?= Helpers::e($order['order_status']) ?>">
        <?= Helpers::e(Helpers::statusLabel($order['order_status'])) ?>
      </span>
    </div>

    <div class="order-details-grid">
      <div>
        <div class="checkout-summary mb-4">
          <h2 class="h6 mb-3">Live status</h2>
          <div class="status-tracker mb-0" id="status-tracker" data-order="<?= Helpers::e($order['order_number']) ?>">
            <?php foreach ($steps as $step): ?>
              <div class="status-step <?= Helpers::e($step['state']) ?>" data-key="<?= Helpers::e($step['key']) ?>">
                <div class="status-dot"></div>
                <div class="status-label"><?= Helpers::e($step['label']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="checkout-summary">
          <h2 class="h6 mb-3">Items</h2>
          <?php foreach ($items as $it):
            $meta = json_decode($it['addons_json'] ?? '{}', true) ?: [];
          ?>
            <div class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25">
              <div>
                <?= (int) $it['quantity'] ?>× <?= Helpers::e($it['product_name']) ?>
                <?php if (!empty($meta['variant']['label'])): ?>
                  <div class="small opacity-75"><?= Helpers::e($meta['variant']['label']) ?></div>
                <?php endif; ?>
                <?php foreach ($meta['addons'] ?? [] as $a): ?>
                  <div class="small opacity-75">+ <?= Helpers::e($a['name']) ?></div>
                <?php endforeach; ?>
                <?php foreach ($meta['box_picks'] ?? [] as $b): ?>
                  <div class="small opacity-75">• <?= Helpers::e($b['name']) ?></div>
                <?php endforeach; ?>
              </div>
              <span><?= Helpers::money($it['total_item_price']) ?></span>
            </div>
          <?php endforeach; ?>
          <div class="d-flex justify-content-between mt-3"><span>Subtotal</span><span><?= Helpers::money($order['subtotal']) ?></span></div>
          <?php if ((float) ($order['discount_amount'] ?? 0) > 0): ?>
            <div class="d-flex justify-content-between"><span>Promo <?= Helpers::e($order['promo_code'] ?? '') ?></span><span>−<?= Helpers::money($order['discount_amount']) ?></span></div>
          <?php endif; ?>
          <div class="d-flex justify-content-between"><span>Delivery</span><span><?= Helpers::money($order['delivery_fee']) ?></span></div>
          <div class="d-flex justify-content-between fw-bold mt-1"><span>Total</span><span><?= Helpers::money($order['total_amount']) ?></span></div>
        </div>
      </div>

      <aside>
        <div class="checkout-summary mb-4">
          <h2 class="h6 mb-3">Fulfilment</h2>
          <p class="mb-1"><strong><?= Helpers::e(ucfirst((string) $order['fulfillment_type'])) ?></strong></p>
          <p class="mb-1"><?= Helpers::e($order['delivery_time_slot'] ?: 'ASAP') ?></p>
          <?php if ($placed): ?>
            <p class="small text-muted mb-2">Placed <?= date('d M Y · H:i', $placed) ?></p>
          <?php endif; ?>
          <?php if ($order['fulfillment_type'] === 'delivery'): ?>
            <p class="mb-0">
              <?= Helpers::e($order['delivery_address']) ?><br>
              <?= Helpers::e($order['postcode']) ?>
            </p>
          <?php else: ?>
            <p class="small mb-0 opacity-75">Collection from <?= Helpers::e((string) Helpers::setting('collection_address', 'Unit 2, Old Biscuit Factory, Dockray Street, Colne, BB8 9HT')) ?></p>
          <?php endif; ?>
        </div>

        <div class="checkout-summary mb-4">
          <h2 class="h6 mb-3">Contact &amp; payment</h2>
          <p class="mb-1"><?= Helpers::e($order['customer_name']) ?></p>
          <p class="mb-1"><?= Helpers::e($order['customer_email']) ?></p>
          <p class="mb-2"><?= Helpers::e($order['customer_phone']) ?></p>
          <p class="mb-0 small"><?= Helpers::e($payMethod) ?> · <?= Helpers::e($payStatus) ?></p>
          <?php if (!empty($order['notes'])): ?>
            <p class="mt-3 mb-0 small"><em><?= Helpers::e($order['notes']) ?></em></p>
          <?php endif; ?>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <a href="<?= Helpers::baseUrl('order/' . $order['order_number'] . '/invoice') ?>" class="btn btn-accent">Download invoice</a>
          <a href="<?= Helpers::baseUrl('orders') ?>" class="btn btn-ghost">My orders</a>
        </div>
      </aside>
    </div>
  </div>
</section>
