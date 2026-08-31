<?php
use App\Core\Helpers;

$dt = static function (?string $v): string {
    if (!$v) {
        return '';
    }
    $t = strtotime($v);
    return $t ? date('Y-m-d\TH:i', $t) : '';
};
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Offers &amp; promo codes</h1>
    <p class="text-muted mb-0">Product sale prices apply automatically. Promo codes are entered at checkout.</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-xl-6">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h6">New product offer</h2>
        <form method="post" action="<?= Helpers::baseUrl('admin/offers/products') ?>">
          <?= Helpers::csrfField() ?>
          <select name="product_id" class="form-select mb-2" required>
            <option value="">Choose product…</option>
            <?php foreach ($products as $p): ?>
              <option value="<?= (int) $p['id'] ?>"><?= Helpers::e($p['title']) ?> · <?= Helpers::money($p['base_price']) ?></option>
            <?php endforeach; ?>
          </select>
          <input name="title" class="form-control mb-2" placeholder="Label (e.g. Weekend special)" value="Special offer">
          <div class="row g-2 mb-2">
            <div class="col-6">
              <select name="discount_type" class="form-select">
                <option value="percent">Percent off</option>
                <option value="fixed">£ amount off</option>
              </select>
            </div>
            <div class="col-6">
              <input type="number" step="0.01" min="0" name="discount_value" class="form-control" placeholder="Value" required>
            </div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col"><input type="datetime-local" name="starts_at" class="form-control" title="Starts"></div>
            <div class="col"><input type="datetime-local" name="ends_at" class="form-control" title="Ends"></div>
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="status" id="offer-status" checked>
            <label class="form-check-label" for="offer-status">Active</label>
          </div>
          <button class="btn btn-warning">Create product offer</button>
        </form>
      </div>
    </div>

    <?php foreach ($offers as $o): ?>
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <form method="post" action="<?= Helpers::baseUrl('admin/offers/products/' . $o['id'] . '/update') ?>" class="row g-2 align-items-end">
            <?= Helpers::csrfField() ?>
            <div class="col-md-6">
              <label class="form-label small mb-0">Product</label>
              <select name="product_id" class="form-select form-select-sm">
                <?php foreach ($products as $p): ?>
                  <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) $o['product_id'] ? 'selected' : '' ?>><?= Helpers::e($p['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small mb-0">Title</label>
              <input name="title" class="form-control form-control-sm" value="<?= Helpers::e($o['title']) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label small mb-0">Type</label>
              <select name="discount_type" class="form-select form-select-sm">
                <option value="percent" <?= $o['discount_type'] === 'percent' ? 'selected' : '' ?>>%</option>
                <option value="fixed" <?= $o['discount_type'] === 'fixed' ? 'selected' : '' ?>>£</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small mb-0">Value</label>
              <input type="number" step="0.01" name="discount_value" class="form-control form-control-sm" value="<?= Helpers::e((string) $o['discount_value']) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label small mb-0">Starts</label>
              <input type="datetime-local" name="starts_at" class="form-control form-control-sm" value="<?= $dt($o['starts_at'] ?? null) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label small mb-0">Ends</label>
              <input type="datetime-local" name="ends_at" class="form-control form-control-sm" value="<?= $dt($o['ends_at'] ?? null) ?>">
            </div>
            <div class="col-md-3">
              <div class="form-check mt-1">
                <input class="form-check-input" type="checkbox" name="status" id="os-<?= (int) $o['id'] ?>" <?= (int) $o['status'] ? 'checked' : '' ?>>
                <label class="form-check-label small" for="os-<?= (int) $o['id'] ?>">Active</label>
              </div>
            </div>
            <div class="col-md-9 text-end">
              <button class="btn btn-sm btn-warning">Save</button>
            </div>
          </form>
          <form method="post" action="<?= Helpers::baseUrl('admin/offers/products/' . $o['id'] . '/delete') ?>" class="text-end mt-2" onsubmit="return confirm('Delete this offer?')">
            <?= Helpers::csrfField() ?>
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$offers): ?>
      <p class="text-muted">No product offers yet. A sale price will show on the menu and apply in the basket automatically.</p>
    <?php endif; ?>
  </div>

  <div class="col-xl-6">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h6">New promo code</h2>
        <form method="post" action="<?= Helpers::baseUrl('admin/offers/promos') ?>">
          <?= Helpers::csrfField() ?>
          <div class="row g-2 mb-2">
            <div class="col-5"><input name="code" class="form-control text-uppercase" placeholder="CODE" required></div>
            <div class="col-7"><input name="title" class="form-control" placeholder="Title (e.g. Launch 10%)" required></div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6">
              <select name="discount_type" class="form-select">
                <option value="percent">Percent off</option>
                <option value="fixed">£ amount off</option>
              </select>
            </div>
            <div class="col-6">
              <input type="number" step="0.01" min="0" name="discount_value" class="form-control" placeholder="Value" required>
            </div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col"><input type="number" step="0.01" min="0" name="min_order" class="form-control" placeholder="Min order £" value="0"></div>
            <div class="col"><input type="number" min="1" name="max_uses" class="form-control" placeholder="Max uses (blank = ∞)"></div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col"><input type="datetime-local" name="starts_at" class="form-control"></div>
            <div class="col"><input type="datetime-local" name="ends_at" class="form-control"></div>
          </div>
          <label class="form-label small">Limit to products (leave empty = whole basket)</label>
          <div class="multi-check-list mb-2">
            <?php foreach ($products as $p): ?>
              <label class="multi-check-item">
                <input type="checkbox" name="product_ids[]" value="<?= (int) $p['id'] ?>">
                <span><?= Helpers::e($p['title']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <p class="small text-muted mb-2">Tick every product this code should apply to. Leave all unticked to discount the whole basket.</p>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="status" id="promo-status" checked>
            <label class="form-check-label" for="promo-status">Active</label>
          </div>
          <button class="btn btn-warning">Create promo code</button>
        </form>
      </div>
    </div>

    <?php foreach ($promos as $pr): ?>
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <form method="post" action="<?= Helpers::baseUrl('admin/offers/promos/' . $pr['id'] . '/update') ?>">
            <?= Helpers::csrfField() ?>
            <div class="row g-2 mb-2">
              <div class="col-4"><input name="code" class="form-control form-control-sm text-uppercase" value="<?= Helpers::e($pr['code']) ?>" required></div>
              <div class="col-8"><input name="title" class="form-control form-control-sm" value="<?= Helpers::e($pr['title']) ?>"></div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-4">
                <select name="discount_type" class="form-select form-select-sm">
                  <option value="percent" <?= $pr['discount_type'] === 'percent' ? 'selected' : '' ?>>%</option>
                  <option value="fixed" <?= $pr['discount_type'] === 'fixed' ? 'selected' : '' ?>>£</option>
                </select>
              </div>
              <div class="col-4"><input type="number" step="0.01" name="discount_value" class="form-control form-control-sm" value="<?= Helpers::e((string) $pr['discount_value']) ?>"></div>
              <div class="col-4"><input type="number" step="0.01" name="min_order" class="form-control form-control-sm" value="<?= Helpers::e((string) $pr['min_order']) ?>" placeholder="Min £"></div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col"><input type="number" name="max_uses" class="form-control form-control-sm" value="<?= $pr['max_uses'] !== null ? (int) $pr['max_uses'] : '' ?>" placeholder="Max uses"></div>
              <div class="col"><input type="datetime-local" name="starts_at" class="form-control form-control-sm" value="<?= $dt($pr['starts_at'] ?? null) ?>"></div>
              <div class="col"><input type="datetime-local" name="ends_at" class="form-control form-control-sm" value="<?= $dt($pr['ends_at'] ?? null) ?>"></div>
            </div>
            <label class="form-label small">Limit to products</label>
            <div class="multi-check-list mb-2">
              <?php foreach ($products as $p): ?>
                <label class="multi-check-item">
                  <input type="checkbox" name="product_ids[]" value="<?= (int) $p['id'] ?>" <?= in_array((int) $p['id'], $pr['product_ids'] ?? [], true) ? 'checked' : '' ?>>
                  <span><?= Helpers::e($p['title']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <p class="small text-muted mb-2">Used <?= (int) $pr['used_count'] ?><?= $pr['max_uses'] !== null ? ' / ' . (int) $pr['max_uses'] : '' ?> times</p>
            <div class="d-flex justify-content-between align-items-center">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="status" id="ps-<?= (int) $pr['id'] ?>" <?= (int) $pr['status'] ? 'checked' : '' ?>>
                <label class="form-check-label small" for="ps-<?= (int) $pr['id'] ?>">Active</label>
              </div>
              <button class="btn btn-sm btn-warning">Save</button>
            </div>
          </form>
          <form method="post" action="<?= Helpers::baseUrl('admin/offers/promos/' . $pr['id'] . '/delete') ?>" class="text-end mt-2" onsubmit="return confirm('Delete this promo code?')">
            <?= Helpers::csrfField() ?>
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
