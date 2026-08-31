<?php use App\Core\Helpers; $s = $settings ?? []; ?>
<h1 class="h3 mb-2">Store Settings</h1>
<p class="text-muted mb-4">These values drive currency, free delivery, order numbers, and contact details across the site.</p>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= Helpers::e($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?= Helpers::e($success) ?></div>
<?php endif; ?>

<form method="post" action="<?= Helpers::baseUrl('admin/settings') ?>" enctype="multipart/form-data" class="bg-white rounded shadow-sm p-4" style="max-width:860px">
  <?= Helpers::csrfField() ?>

  <h2 class="h5 mb-1">Website Logo</h2>
  <p class="text-muted small mb-3">Upload a custom logo to update the storefront header, footer, invoices, login, register, and admin logo across the site.</p>
  <div class="row align-items-center g-3 mb-4 p-3 bg-light rounded border">
    <div class="col-auto">
      <img src="<?= Helpers::logo() ?>" alt="Website Logo" class="rounded-circle shadow-sm" style="width:72px;height:72px;object-fit:cover;background:#2c1810;padding:4px">
    </div>
    <div class="col">
      <label class="form-label fw-semibold">Upload new logo (PNG, JPG, WEBP, SVG, GIF)</label>
      <input type="file" name="site_logo" class="form-control" accept="image/*">
      <?php if (!empty($s['site_logo'])): ?>
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="reset_logo" id="reset_logo" value="1">
          <label class="form-check-label text-danger small fw-semibold" for="reset_logo">
            Reset logo to default Kraved logo
          </label>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <hr class="my-4">

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Store name</label>
      <input name="store_name" class="form-control" value="<?= Helpers::e($s['store_name'] ?? 'Kraved') ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Tagline</label>
      <input name="store_tagline" class="form-control" value="<?= Helpers::e($s['store_tagline'] ?? '') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Currency symbol</label>
      <input name="currency_symbol" class="form-control" value="<?= Helpers::e($s['currency_symbol'] ?? '£') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Free delivery from</label>
      <input type="number" step="0.01" name="free_delivery_threshold" class="form-control" value="<?= Helpers::e($s['free_delivery_threshold'] ?? '25') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">ASAP prep (mins)</label>
      <input type="number" name="asap_prep_mins" class="form-control" value="<?= Helpers::e($s['asap_prep_mins'] ?? '35') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Order prefix</label>
      <input name="order_prefix" class="form-control" value="<?= Helpers::e($s['order_prefix'] ?? 'KRV') ?>">
    </div>
    <div class="col-md-8">
      <label class="form-label">Collection address</label>
      <input name="collection_address" class="form-control" value="<?= Helpers::e($s['collection_address'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Contact email</label>
      <input type="email" name="contact_email" class="form-control" value="<?= Helpers::e($s['contact_email'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Contact phone</label>
      <input name="contact_phone" class="form-control" value="<?= Helpers::e($s['contact_phone'] ?? '') ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Opening hours</label>
      <input name="contact_hours" class="form-control" value="<?= Helpers::e($s['contact_hours'] ?? '') ?>">
    </div>
  </div>

  <hr class="my-4">
  <h2 class="h5 mb-1">Checkout extras popup</h2>
  <p class="text-muted small mb-3">When a customer goes to checkout, show a popup with two category buttons (for example Drinks and Desserts). Clicking a button opens that category’s products so they can add more before placing the order.</p>
  <?php $cats = $categories ?? []; $cat1 = (int) ($s['checkout_upsell_cat_1'] ?? 0); $cat2 = (int) ($s['checkout_upsell_cat_2'] ?? 0); ?>
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="checkout_upsell_enabled" id="upsell-on" value="1" <?= (($s['checkout_upsell_enabled'] ?? '1') !== '0') ? 'checked' : '' ?>>
    <label class="form-check-label" for="upsell-on">Show extras popup at checkout</label>
  </div>
  <div class="row g-3">
    <div class="col-12">
      <label class="form-label">Popup title</label>
      <input name="checkout_upsell_title" class="form-control" value="<?= Helpers::e($s['checkout_upsell_title'] ?? 'Want drinks or dessert too?') ?>" placeholder="Want drinks or dessert too?">
    </div>
    <div class="col-12">
      <label class="form-label">Popup message</label>
      <input name="checkout_upsell_subtitle" class="form-control" value="<?= Helpers::e($s['checkout_upsell_subtitle'] ?? 'Add a little extra before you place your order.') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">First category</label>
      <select name="checkout_upsell_cat_1" class="form-select">
        <option value="0">— None —</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $cat1 === (int) $c['id'] ? 'selected' : '' ?>><?= Helpers::e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input name="checkout_upsell_label_1" class="form-control mt-2" placeholder="Button label (optional, e.g. Add drinks)" value="<?= Helpers::e($s['checkout_upsell_label_1'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Second category</label>
      <select name="checkout_upsell_cat_2" class="form-select">
        <option value="0">— None —</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $cat2 === (int) $c['id'] ? 'selected' : '' ?>><?= Helpers::e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input name="checkout_upsell_label_2" class="form-control mt-2" placeholder="Button label (optional, e.g. Add dessert)" value="<?= Helpers::e($s['checkout_upsell_label_2'] ?? '') ?>">
    </div>
  </div>

  <hr class="my-4">
  <h2 class="h5 mb-1">Stripe payments</h2>
  <p class="text-muted small mb-3">Customers can pay by card at checkout. Use <strong>Sandbox</strong> with Stripe test keys while you try it, then switch to <strong>Live</strong> with live keys when you are ready to take real payments. Leave a secret field blank to keep the key already saved.</p>
  <?php
    $stripeMode = (($s['stripe_mode'] ?? 'sandbox') === 'live') ? 'live' : 'sandbox';
    $webhookUrl = Helpers::baseUrl('webhooks/stripe');
  ?>
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="stripe_enabled" id="stripe-on" value="1" <?= (($s['stripe_enabled'] ?? '0') === '1') ? 'checked' : '' ?>>
    <label class="form-check-label" for="stripe-on">Enable Stripe card payments at checkout</label>
  </div>
  <div class="mb-3">
    <span class="form-label d-block">Environment</span>
    <div class="btn-group" role="group" aria-label="Stripe environment">
      <input type="radio" class="btn-check" name="stripe_mode" id="stripe-mode-sandbox" value="sandbox" <?= $stripeMode === 'sandbox' ? 'checked' : '' ?>>
      <label class="btn btn-outline-secondary" for="stripe-mode-sandbox">Sandbox</label>
      <input type="radio" class="btn-check" name="stripe_mode" id="stripe-mode-live" value="live" <?= $stripeMode === 'live' ? 'checked' : '' ?>>
      <label class="btn btn-outline-danger" for="stripe-mode-live">Live</label>
    </div>
    <div class="small text-muted mt-2">Sandbox uses <code>pk_test_</code> / <code>sk_test_</code>. Live uses <code>pk_live_</code> / <code>sk_live_</code>.</div>
  </div>
  <div class="row g-3">
    <div class="col-12"><h3 class="h6 mb-0">Sandbox keys</h3></div>
    <div class="col-md-6">
      <label class="form-label">Sandbox publishable key</label>
      <input name="stripe_sandbox_publishable_key" class="form-control" autocomplete="off" placeholder="pk_test_…">
      <?php if (!empty($s['stripe_sandbox_publishable_key'])): ?>
        <div class="form-text text-success">Saved <?= Helpers::e(\App\Services\StripeGateway::maskKey((string) $s['stripe_sandbox_publishable_key'])) ?></div>
      <?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Sandbox secret key</label>
      <input type="password" name="stripe_sandbox_secret_key" class="form-control" autocomplete="new-password" placeholder="sk_test_…">
      <?php if (!empty($s['stripe_sandbox_secret_key'])): ?>
        <div class="form-text text-success">Saved <?= Helpers::e(\App\Services\StripeGateway::maskKey((string) $s['stripe_sandbox_secret_key'])) ?></div>
      <?php endif; ?>
    </div>
    <div class="col-12">
      <label class="form-label">Sandbox webhook secret (optional)</label>
      <input type="password" name="stripe_sandbox_webhook_secret" class="form-control" autocomplete="new-password" placeholder="whsec_…">
      <?php if (!empty($s['stripe_sandbox_webhook_secret'])): ?>
        <div class="form-text text-success">Saved <?= Helpers::e(\App\Services\StripeGateway::maskKey((string) $s['stripe_sandbox_webhook_secret'])) ?></div>
      <?php endif; ?>
    </div>
    <div class="col-12"><h3 class="h6 mb-0 mt-2">Live keys</h3></div>
    <div class="col-md-6">
      <label class="form-label">Live publishable key</label>
      <input name="stripe_live_publishable_key" class="form-control" autocomplete="off" placeholder="pk_live_…">
      <?php if (!empty($s['stripe_live_publishable_key'])): ?>
        <div class="form-text text-success">Saved <?= Helpers::e(\App\Services\StripeGateway::maskKey((string) $s['stripe_live_publishable_key'])) ?></div>
      <?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Live secret key</label>
      <input type="password" name="stripe_live_secret_key" class="form-control" autocomplete="new-password" placeholder="sk_live_…">
      <?php if (!empty($s['stripe_live_secret_key'])): ?>
        <div class="form-text text-success">Saved <?= Helpers::e(\App\Services\StripeGateway::maskKey((string) $s['stripe_live_secret_key'])) ?></div>
      <?php endif; ?>
    </div>
    <div class="col-12">
      <label class="form-label">Live webhook secret (optional)</label>
      <input type="password" name="stripe_live_webhook_secret" class="form-control" autocomplete="new-password" placeholder="whsec_…">
      <?php if (!empty($s['stripe_live_webhook_secret'])): ?>
        <div class="form-text text-success">Saved <?= Helpers::e(\App\Services\StripeGateway::maskKey((string) $s['stripe_live_webhook_secret'])) ?></div>
      <?php endif; ?>
    </div>
    <div class="col-12">
      <label class="form-label">Webhook URL</label>
      <input class="form-control" readonly value="<?= Helpers::e($webhookUrl) ?>" onclick="this.select()">
      <div class="form-text">In Stripe Dashboard → Developers → Webhooks, add this URL and listen for <code>checkout.session.completed</code>. Paste the signing secret above for the matching environment.</div>
    </div>
  </div>
  <div class="mt-4 d-flex gap-2">
    <button class="btn btn-warning">Save settings</button>
    <a class="btn btn-outline-secondary" href="<?= Helpers::baseUrl('admin/cms') ?>">Homepage CMS</a>
  </div>
</form>
