<?php
use App\Core\Helpers;

$img = !empty($p['image'])
    ? Helpers::upload($p['image'])
    : ($p['_fallback_image'] ?? Helpers::asset('images/product-lava-cake.png'));
$variantCount = (int) ($p['variant_count'] ?? 0);
$displayPrice = isset($p['sale_price'])
    ? (float) $p['sale_price']
    : ($variantCount > 0 && $p['min_variant_price'] !== null
        ? (float) $p['min_variant_price']
        : (float) $p['base_price']);
$originalPrice = (float) ($p['original_price'] ?? $displayPrice);
$onOffer = !empty($p['on_offer']) && $originalPrice > $displayPrice + 0.001;
$showFrom = $variantCount > 1;
$wishlistProductId = (int) $p['id'];
?>
<article class="product-card">
  <div class="pc-media">
    <?php require __DIR__ . '/wishlist-btn.php'; ?>
    <?php if ($onOffer && !empty($p['offer_badge'])): ?>
      <span class="pc-sale-badge"><?= Helpers::e($p['offer_badge']) ?></span>
    <?php endif; ?>
    <img src="<?= Helpers::e($img) ?>" alt="<?= Helpers::e($p['title'] ?? '') ?>" loading="lazy"
      onerror="this.onerror=null;this.src='<?= Helpers::e(Helpers::asset('images/hero-dessert.png')) ?>'">
  </div>
  <div class="pc-body">
    <?php if (!empty($p['weight_label'])): ?>
      <span class="pc-tag"><?= Helpers::e($p['weight_label']) ?></span>
    <?php endif; ?>
    <h3 class="pc-title"><?= Helpers::e($p['title']) ?></h3>
    <p class="pc-desc"><?= Helpers::e($p['short_description'] ?? '') ?></p>
    <div class="pc-row">
      <span class="pc-price">
        <?php if ($onOffer): ?>
          <span class="pc-price-was"><?= Helpers::money($originalPrice) ?></span>
        <?php endif; ?>
        <?= $showFrom ? 'From ' : '' ?><?= Helpers::money($displayPrice) ?>
      </span>
      <button type="button" class="btn-add-round btn-add"
        data-product-id="<?= (int)$p['id'] ?>"
        aria-label="Add <?= Helpers::e($p['title']) ?>">+</button>
    </div>
  </div>
</article>
