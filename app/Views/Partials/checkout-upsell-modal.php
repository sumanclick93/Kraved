<div class="modal fade" id="checkoutUpsellModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content upsell-modal">
      <div class="modal-header border-0 pb-0">
        <div>
          <h2 class="modal-title h4 mb-1" id="upsell-title">Want a little extra?</h2>
          <p class="mb-0 opacity-75" id="upsell-subtitle">Add drinks or dessert before you place your order.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="upsell-body">
        <div class="upsell-home" id="upsell-home">
          <div class="upsell-cat-grid" id="upsell-cats"></div>
        </div>
        <div class="upsell-products d-none" id="upsell-products-wrap">
          <div class="upsell-products-head">
            <button type="button" class="upsell-back" id="upsell-back">
              <span class="upsell-back-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                  <path d="M15 5 8 12l7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
              <span>Back to extras</span>
            </button>
            <h3 class="upsell-cat-title" id="upsell-cat-title"></h3>
          </div>
          <div class="upsell-product-grid" id="upsell-products"></div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-ghost" id="upsell-skip">No thanks</button>
        <button type="button" class="btn btn-accent" id="upsell-continue">Continue to checkout</button>
      </div>
    </div>
  </div>
</div>
