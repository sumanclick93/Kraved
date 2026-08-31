<?php
use App\Models\Wishlist;

$wishlistProductId = (int) ($wishlistProductId ?? ($p['id'] ?? 0));
$wishlistIds = $wishlistIds ?? Wishlist::currentProductIds();
$wished = in_array($wishlistProductId, $wishlistIds, true);
?>
<button
  type="button"
  class="wish-btn <?= $wished ? 'is-wished' : '' ?>"
  data-wishlist
  data-product-id="<?= $wishlistProductId ?>"
  aria-pressed="<?= $wished ? 'true' : 'false' ?>"
  aria-label="<?= $wished ? 'Remove from wishlist' : 'Add to wishlist' ?>"
  title="<?= $wished ? 'Saved' : 'Save to wishlist' ?>"
>
  <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
    <path d="M12 20s-7-4.6-9.2-8.2C1 9.4 2.2 6 5.5 6c1.8 0 3.1 1 3.8 2.2C10.1 7 11.4 6 13.2 6c3.3 0 4.5 3.4 2.7 5.8C13.7 15.4 12 20 12 20Z"/>
  </svg>
</button>
