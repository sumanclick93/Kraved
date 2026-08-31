<?php use App\Core\Helpers; ?>
<aside class="cart-drawer" id="cart-drawer" aria-hidden="true">
  <div class="cart-drawer-panel">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h4 mb-0">Your Basket</h2>
      <button type="button" class="btn-close" id="close-cart" aria-label="Close"></button>
    </div>
    <div id="cart-items" class="cart-items"></div>
    <div class="free-delivery-meter mt-3" id="free-delivery-meter">
      <div class="meter-track"><div class="meter-fill" id="meter-fill"></div></div>
      <p class="small mt-1 mb-0" id="meter-text"></p>
    </div>
    <div class="cart-totals mt-3">
      <div class="d-flex justify-content-between"><span>Subtotal</span><strong id="cart-subtotal">£0.00</strong></div>
      <div class="d-flex justify-content-between <?= empty($cart['discount'] ?? 0) ? '' : '' ?>" id="cart-discount-row" style="display:none">
        <span>Promo <strong id="cart-promo-code"></strong></span>
        <strong id="cart-discount">−£0.00</strong>
      </div>
      <div class="d-flex justify-content-between"><span>Delivery</span><strong id="cart-delivery">At checkout</strong></div>
      <div class="d-flex justify-content-between total-row"><span>Total</span><strong id="cart-total">£0.00</strong></div>
    </div>
    <form class="promo-form mt-3" id="cart-promo-form">
      <label class="form-label small mb-1" for="cart-promo-input">Promo code</label>
      <div class="d-flex gap-2">
        <input id="cart-promo-input" class="form-control form-control-sm" name="code" placeholder="CODE" autocomplete="off">
        <button type="submit" class="btn btn-ghost btn-sm">Apply</button>
      </div>
      <p class="small mt-1 mb-0" id="cart-promo-msg"></p>
    </form>
    <a href="<?= Helpers::baseUrl('checkout') ?>" id="cart-checkout" class="btn btn-accent w-100 mt-3">Checkout</a>
  </div>
</aside>
<div class="cart-backdrop" id="cart-backdrop"></div>
