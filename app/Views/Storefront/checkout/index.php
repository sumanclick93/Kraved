<?php use App\Core\Helpers; $c = $cart; $f = $fulfillment; ?>
<section class="section pt-4" data-page="checkout">
  <div class="container" style="max-width:720px">
    <h1 class="section-title">Checkout</h1>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= Helpers::e($error) ?></div>
    <?php endif; ?>

    <div class="checkout-summary mb-4" id="checkout-summary">
      <?php foreach ($c['items'] as $item): ?>
        <div class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25">
          <div>
            <strong><?= (int)$item['quantity'] ?>× <?= Helpers::e($item['name']) ?></strong>
            <?php if (!empty($item['variant_label']) && !str_contains($item['name'], '(' . $item['variant_label'] . ')')): ?>
              <div class="small opacity-75"><?= Helpers::e($item['variant_label']) ?></div>
            <?php endif; ?>
            <?php foreach ($item['addons'] ?? [] as $a): ?>
              <div class="small opacity-75">+ <?= Helpers::e($a['name']) ?></div>
            <?php endforeach; ?>
            <?php foreach ($item['box_picks'] ?? [] as $b): ?>
              <div class="small opacity-75">• <?= Helpers::e($b['name']) ?></div>
            <?php endforeach; ?>
          </div>
          <span><?= Helpers::money($item['line_total']) ?></span>
        </div>
      <?php endforeach; ?>
      <div class="d-flex justify-content-between mt-2"><span>Subtotal</span><span><?= Helpers::money($c['subtotal']) ?></span></div>
      <?php if (!empty($c['discount'])): ?>
        <div class="d-flex justify-content-between text-success"><span>Promo <?= Helpers::e($c['promo']['code'] ?? '') ?></span><span>−<?= Helpers::money($c['discount']) ?></span></div>
      <?php endif; ?>
      <div class="d-flex justify-content-between"><span>Delivery</span><span><?= Helpers::money($c['delivery_fee']) ?></span></div>
      <div class="d-flex justify-content-between fw-bold mt-1"><span>Total</span><span><?= Helpers::money($c['total']) ?></span></div>
    </div>

    <form method="post" action="<?= Helpers::formAction('checkout') ?>" class="checkout-form" id="checkout-form">
      <?= Helpers::csrfField() ?>
      <input type="hidden" name="delivery_time_slot" value="<?= Helpers::e($f['time_slot'] ?? 'ASAP') ?>">

      <h2 class="h5 mt-4">Your details</h2>
      <div class="mb-3">
        <label class="form-label" for="customer_name">Name</label>
        <input id="customer_name" name="customer_name" class="form-control" required minlength="2" maxlength="80" autocomplete="name" value="<?= Helpers::e($user['name'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="customer_email">Email</label>
        <input id="customer_email" type="email" name="customer_email" class="form-control" required maxlength="120" autocomplete="email" value="<?= Helpers::e($user['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="customer_phone">Phone</label>
        <input id="customer_phone" name="customer_phone" class="form-control" required minlength="10" maxlength="20" autocomplete="tel" value="<?= Helpers::e($user['phone'] ?? '') ?>">
      </div>

      <?php if (($f['type'] ?? '') === 'delivery'): ?>
        <h2 class="h5 mt-4">Delivery address</h2>
        <p class="small opacity-75">Delivery charge for <?= Helpers::e($f['postcode'] ?: 'your area') ?> is added below.</p>
        <div class="mb-3">
          <label class="form-label" for="delivery_address">Address</label>
          <input id="delivery_address" name="delivery_address" class="form-control" required minlength="5" maxlength="250" autocomplete="street-address" value="<?= Helpers::e($user['address'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label" for="postcode">Postcode</label>
          <input id="postcode" name="postcode" class="form-control text-uppercase" required minlength="3" maxlength="8" autocomplete="postal-code" placeholder="e.g. BB11 1AA" value="<?= Helpers::e($f['postcode'] ?? ($user['postcode'] ?? '')) ?>">
        </div>
      <?php else: ?>
        <p class="small opacity-75">Collection from <?= Helpers::e((string) Helpers::setting('collection_address', 'Unit 2, Old Biscuit Factory, Dockray Street, Colne, BB8 9HT')) ?> · <?= Helpers::e($f['time_slot'] ?? 'ASAP') ?></p>
      <?php endif; ?>

      <h2 class="h5 mt-4">Payment</h2>
      <div class="form-check mb-2">
        <input class="form-check-input" type="radio" name="payment_method" id="pay-cod" value="cod" checked>
        <label class="form-check-label" for="pay-cod">Cash on Delivery / Collection</label>
      </div>
      <?php if (!empty($stripeEnabled)): ?>
      <div class="form-check mb-3">
        <input class="form-check-input" type="radio" name="payment_method" id="pay-card" value="card">
        <label class="form-check-label" for="pay-card">Pay by card (Stripe<?= ($stripeMode ?? '') === 'sandbox' ? ' · test mode' : '' ?>)</label>
      </div>
      <p class="small opacity-75 mt-n2 mb-3" id="pay-card-hint" hidden>You’ll be redirected to Stripe’s secure checkout. Your order is only placed after Stripe confirms the payment.</p>
      <?php endif; ?>

      <?php if (!$user): ?>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" name="create_account" id="create_account" value="1">
          <label class="form-check-label" for="create_account">Create an account</label>
        </div>
        <div class="mb-3" id="reg-pass" style="display:none">
          <input type="password" name="password" class="form-control" placeholder="Password (min 6)" minlength="6">
        </div>
      <?php endif; ?>

      <h2 class="h5 mt-4">Promo code</h2>
      <div class="mb-3">
        <input name="promo_code" class="form-control text-uppercase" placeholder="Optional code" value="<?= Helpers::e($c['promo']['code'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Order notes</label>
        <textarea name="notes" class="form-control" rows="2" placeholder="Allergies, doorbell notes…"></textarea>
      </div>

      <button class="btn btn-accent btn-lg w-100" type="submit" id="place-order-btn" <?= empty($canPlace) ? 'disabled' : '' ?>>Place Order · <?= Helpers::money($c['total']) ?></button>
    </form>
  </div>
</section>
<script>
document.getElementById('create_account')?.addEventListener('change', function() {
  document.getElementById('reg-pass').style.display = this.checked ? '' : 'none';
});
const payCard = document.getElementById('pay-card');
const payCod = document.getElementById('pay-cod');
const payHint = document.getElementById('pay-card-hint');
const placeBtn = document.getElementById('place-order-btn');
const placeLabel = placeBtn?.textContent || '';
function syncPayUi() {
  const card = !!(payCard && payCard.checked);
  if (payHint) payHint.hidden = !card;
  if (placeBtn) placeBtn.textContent = card ? placeLabel.replace(/^Place Order/, 'Pay with card') : placeLabel;
}
payCard?.addEventListener('change', syncPayUi);
payCod?.addEventListener('change', syncPayUi);
document.getElementById('checkout-form')?.addEventListener('submit', function (e) {
  const phone = document.getElementById('customer_phone');
  const postcode = document.getElementById('postcode');
  const address = document.getElementById('delivery_address');
  const digits = (phone?.value || '').replace(/\D+/g, '');
  if (phone && (digits.length < 10 || digits.length > 15)) {
    e.preventDefault();
    phone.setCustomValidity('Please enter a valid phone number.');
    phone.reportValidity();
    return;
  }
  phone?.setCustomValidity('');
  if (address && address.value.trim().length < 5) {
    e.preventDefault();
    address.setCustomValidity('Please enter a full delivery address.');
    address.reportValidity();
    return;
  }
  address?.setCustomValidity('');
  if (postcode) {
    const compact = postcode.value.toUpperCase().replace(/\s+/g, '');
    const ok = /^[A-Z]{1,2}\d[A-Z\d]?\d[A-Z]{2}$/.test(compact)
      || /^[A-Z]{1,2}\d{1,2}[A-Z]?$/.test(compact);
    if (!ok) {
      e.preventDefault();
      postcode.setCustomValidity('Please enter a valid UK postcode.');
      postcode.reportValidity();
      return;
    }
    postcode.setCustomValidity('');
  }
  if (placeBtn) {
    placeBtn.disabled = true;
    placeBtn.textContent = payCard?.checked ? 'Redirecting to Stripe…' : 'Placing order…';
  }
});
['customer_phone', 'postcode', 'delivery_address'].forEach((id) => {
  document.getElementById(id)?.addEventListener('input', function () {
    this.setCustomValidity('');
  });
});
syncPayUi();
</script>
