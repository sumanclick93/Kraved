try {
(() => {
  const base = () => (window.KRAVED?.baseUrl || '').replace(/\/$/, '');
  const csrf = () => window.KRAVED?.csrf
    || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || '';
  const money = (n) => (window.KRAVED?.currency || '£') + Number(n).toFixed(2);
  let lastCart = null;

  function kravedToast(message, type = 'error', title = '') {
    let container = document.getElementById('kraved-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'kraved-toast-container';
      container.className = 'kraved-toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `kraved-toast kraved-toast-${type}`;

    const iconSvg = type === 'success'
      ? `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`
      : type === 'warning'
      ? `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`
      : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;

    toast.innerHTML = `
      <div class="kraved-toast-icon">${iconSvg}</div>
      <div class="kraved-toast-content">
        ${title ? `<div class="kraved-toast-title">${escapeHtml(title)}</div>` : ''}
        <div class="kraved-toast-message">${escapeHtml(message)}</div>
      </div>
      <button class="kraved-toast-close" type="button" aria-label="Close">&times;</button>
    `;

    const closeBtn = toast.querySelector('.kraved-toast-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
      });
    }

    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
      if (toast.parentNode) {
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
      }
    }, 4500);
  }

  // Intercept default alert calls
  window.alert = function(msg) {
    if (!msg) return;
    kravedToast(String(msg), 'warning');
  };

  async function api(path, opts = {}) {
    const headers = Object.assign({
      'X-CSRF-TOKEN': csrf(),
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    }, opts.headers || {});
    if (opts.body instanceof FormData) {
      if (!opts.body.has('_csrf')) opts.body.append('_csrf', csrf());
    } else if (opts.body && typeof opts.body === 'object') {
      headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(Object.assign({ _csrf: csrf() }, opts.body));
    }
    const res = await fetch(base() + path, { ...opts, headers, credentials: 'same-origin' });
    const text = await res.text();
    let data = {};
    try { data = text ? JSON.parse(text) : {}; } catch (e) { data = {}; }
    if (!res.ok) throw Object.assign(new Error(data.error || data.message || 'Request failed'), { data, status: res.status });
    return data;
  }

  function hideKravedModal(el) {
    if (!el) return;
    el.classList.remove('show', 'kraved-open');
    el.setAttribute('aria-hidden', 'true');
    el.style.display = '';
    document.getElementById('kraved-modal-backdrop')?.remove();
    document.body.classList.remove('kraved-modal-open', 'modal-open');
    el.dispatchEvent(new Event('hidden.bs.modal'));
  }

  function showKravedModal(el) {
    if (!el) return;
    el.classList.add('show', 'kraved-open');
    el.setAttribute('aria-hidden', 'false');
    el.style.display = 'block';
    if (!document.getElementById('kraved-modal-backdrop')) {
      const bd = document.createElement('div');
      bd.id = 'kraved-modal-backdrop';
      bd.className = 'kraved-modal-backdrop';
      bd.addEventListener('click', () => hideKravedModal(el));
      document.body.appendChild(bd);
    }
    document.body.classList.add('kraved-modal-open');
  }

  function kravedModal(el) {
    if (!el) return null;
    try {
      if (window.bootstrap?.Modal) {
        return window.bootstrap.Modal.getOrCreateInstance(el);
      }
    } catch (e) { /* fall through */ }
    return {
      show() { showKravedModal(el); },
      hide() { hideKravedModal(el); },
    };
  }

  document.addEventListener('click', (e) => {
    const closer = e.target.closest('[data-bs-dismiss="modal"]');
    if (!closer || window.bootstrap?.Modal) return;
    const modal = closer.closest('.modal');
    if (modal) hideKravedModal(modal);
  });

  /* ---- Cart drawer ---- */
  const drawer = document.getElementById('cart-drawer');
  const backdrop = document.getElementById('cart-backdrop');

  function openCart() {
    drawer?.classList.add('open');
    backdrop?.classList.add('show');
    drawer?.setAttribute('aria-hidden', 'false');
    refreshCart();
  }
  function closeCart() {
    drawer?.classList.remove('open');
    backdrop?.classList.remove('show');
    drawer?.setAttribute('aria-hidden', 'true');
  }

  document.getElementById('open-cart')?.addEventListener('click', openCart);
  document.getElementById('close-cart')?.addEventListener('click', closeCart);
  backdrop?.addEventListener('click', closeCart);
  drawer?.addEventListener('click', (e) => {
    if (e.target === drawer) closeCart();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer?.classList.contains('open')) closeCart();
  });

  function renderCart(cart) {
    lastCart = cart;
    const badge = document.getElementById('cart-badge');
    if (badge) badge.textContent = cart.count || 0;

    const wrap = document.getElementById('cart-items');
    if (!wrap) return;

    if (!cart.items?.length) {
      wrap.innerHTML = '<p class="text-muted">Your basket is empty.</p>';
    } else {
      wrap.innerHTML = cart.items.map(item => {
        const addons = (item.addons || []).map(a => a.name).concat(
          (item.box_picks || []).map(b => b.name)
        ).join(', ');
        return `<div class="cart-line" data-key="${item.key}" data-qty="${item.quantity}">
          <div class="cart-line-top">
            <strong>${item.quantity}× ${escapeHtml(item.name)}</strong>
            <span>${item.offer_label ? `<span class="cart-line-offer">${escapeHtml(item.offer_label)}</span>` : ''}
            ${item.original_unit_price && Number(item.original_unit_price) > Number(item.unit_price)
              ? `<span class="pc-price-was" style="display:inline;margin-right:.35rem">${money(item.original_unit_price * item.quantity)}</span>` : ''}
            ${money(item.line_total)}</span>
          </div>
          ${addons ? `<div class="cart-line-addons">${escapeHtml(addons)}</div>` : ''}
          <div class="d-flex gap-2 mt-2 align-items-center">
            <button type="button" class="btn btn-sm btn-outline-secondary qty-btn" data-delta="-1" aria-label="Decrease quantity">−</button>
            <span class="qty-val">${item.quantity}</span>
            <button type="button" class="btn btn-sm btn-outline-secondary qty-btn" data-delta="1" aria-label="Increase quantity">+</button>
            <button type="button" class="btn btn-sm btn-link text-danger remove-btn ms-auto">Remove</button>
          </div>
        </div>`;
      }).join('');
    }

    document.getElementById('cart-subtotal').textContent = money(cart.subtotal);
    const discRow = document.getElementById('cart-discount-row');
    if (discRow) {
      if (cart.discount > 0 && cart.promo) {
        discRow.style.display = 'flex';
        document.getElementById('cart-promo-code').textContent = cart.promo.code || '';
        document.getElementById('cart-discount').textContent = '−' + money(cart.discount);
      } else {
        discRow.style.display = 'none';
      }
    }
    const onCheckout = !!document.querySelector('[data-page="checkout"]');
    const isDelivery = (cart.fulfillment?.type || window.KRAVED?.fulfillment?.type) === 'delivery';
    const deliveryEl = document.getElementById('cart-delivery');
    if (deliveryEl) {
      deliveryEl.textContent = onCheckout
        ? money(cart.delivery_fee)
        : (isDelivery ? 'At checkout' : money(0));
    }
    const totalEl = document.getElementById('cart-total');
    if (totalEl) {
      const shown = onCheckout ? cart.total : (cart.goods_total ?? Math.max(0, Number(cart.subtotal || 0) - Number(cart.discount || 0)));
      totalEl.textContent = money(shown);
    }

    const threshold = cart.free_delivery_at || window.KRAVED.freeDelivery || 25;
    const pct = Math.min(100, (cart.subtotal / threshold) * 100);
    const fill = document.getElementById('meter-fill');
    const text = document.getElementById('meter-text');
    if (fill) fill.style.width = pct + '%';
    if (text) {
      text.textContent = cart.free_delivery_remaining > 0
        ? `Spend ${money(cart.free_delivery_remaining)} more for free delivery`
        : 'You\'ve unlocked free delivery!';
    }

    const checkout = document.getElementById('cart-checkout');
    if (checkout) {
      checkout.href = base() + '/checkout';
      const empty = !(cart.items && cart.items.length);
      const belowMin = !!minOrderMessage(cart);
      checkout.classList.toggle('disabled', empty || belowMin);
      checkout.setAttribute('aria-disabled', (empty || belowMin) ? 'true' : 'false');
      checkout.tabIndex = (empty || belowMin) ? -1 : 0;
      if (belowMin) checkout.title = minOrderMessage(cart);
      else checkout.removeAttribute('title');
    }

    renderCheckoutSummary(cart);
  }

  function renderCheckoutSummary(cart) {
    const wrap = document.getElementById('checkout-summary');
    const btn = document.getElementById('place-order-btn');
    if (!wrap) return;
    if (!cart.items?.length) {
      wrap.innerHTML = '<p class="text-muted mb-0">Your basket is empty.</p>';
      if (btn) btn.textContent = 'Place Order';
      return;
    }
    const lines = cart.items.map(item => {
      const extras = (item.addons || []).map(a => `<div class="small opacity-75">+ ${escapeHtml(a.name)}</div>`).join('')
        + (item.box_picks || []).map(b => `<div class="small opacity-75">• ${escapeHtml(b.name)}</div>`).join('');
      return `<div class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25">
        <div><strong>${item.quantity}× ${escapeHtml(item.name)}</strong>${extras}</div>
        <span>${money(item.line_total)}</span>
      </div>`;
    }).join('');
    const discount = cart.discount > 0 && cart.promo
      ? `<div class="d-flex justify-content-between text-success"><span>Promo ${escapeHtml(cart.promo.code || '')}</span><span>−${money(cart.discount)}</span></div>`
      : '';
    wrap.innerHTML = lines
      + `<div class="d-flex justify-content-between mt-2"><span>Subtotal</span><span>${money(cart.subtotal)}</span></div>`
      + discount
      + `<div class="d-flex justify-content-between"><span>Delivery</span><span>${isDeliveryCheckout(cart) ? money(cart.delivery_fee) : money(0)}</span></div>`
      + `<div class="d-flex justify-content-between fw-bold mt-1"><span>Total</span><span>${money(cart.total)}</span></div>`;
    if (btn) {
      const block = minOrderMessage(cart);
      btn.disabled = !!block;
      btn.textContent = block ? 'Below minimum order' : `Place Order · ${money(cart.total)}`;
    }
  }

  function isDeliveryCheckout(cart) {
    return (cart?.fulfillment?.type || window.KRAVED?.fulfillment?.type) === 'delivery';
  }

  function minOrderMessage(cart) {
    const f = cart?.fulfillment || window.KRAVED?.fulfillment || {};
    if (f.type !== 'delivery') return '';
    const min = Number(cart?.min_order ?? f.min_order ?? f.zone?.min_order_amount ?? 0);
    const sub = Number(cart?.subtotal ?? 0);
    if (min > 0 && sub < min) {
      const area = f.postcode || f.zone?.postcode_prefix || 'your area';
      return `Minimum order for ${area} is ${money(min)}. Add a little more to continue.`;
    }
    return '';
  }

  async function refreshCart() {
    try {
      const cart = await api('/api/cart');
      renderCart(cart);
    } catch (e) { /* ignore */ }
  }

  document.getElementById('cart-promo-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const input = document.getElementById('cart-promo-input');
    const msg = document.getElementById('cart-promo-msg');
    const code = (input?.value || '').trim().toUpperCase();
    if (!code) return;
    if (!/^[A-Z0-9_-]{2,24}$/.test(code)) {
      if (msg) { msg.textContent = 'Enter a valid promo code.'; msg.className = 'small mt-1 mb-0 text-danger'; }
      return;
    }
    try {
      const data = await api('/api/cart/promo', { method: 'POST', body: { code } });
      renderCart(data.cart);
      if (msg) { msg.textContent = data.message || 'Applied'; msg.className = 'small mt-1 mb-0 text-success'; }
    } catch (err) {
      if (err.data?.cart) renderCart(err.data.cart);
      if (msg) { msg.textContent = err.data?.error || err.message; msg.className = 'small mt-1 mb-0 text-danger'; }
    }
  });

  document.getElementById('cart-items')?.addEventListener('click', async (e) => {
    const line = e.target.closest('.cart-line');
    if (!line) return;
    const key = line.dataset.key;
    if (e.target.classList.contains('remove-btn')) {
      const cart = (await api('/api/cart/remove', { method: 'POST', body: { key } })).cart;
      renderCart(cart);
      return;
    }
    const btn = e.target.closest('.qty-btn');
    if (btn) {
      const current = parseInt(line.dataset.qty || line.querySelector('.qty-val')?.textContent || '1', 10);
      const qty = current + parseInt(btn.dataset.delta, 10);
      const cart = (await api('/api/cart/update', { method: 'POST', body: { key, quantity: qty } })).cart;
      renderCart(cart);
    }
  });

  /* ---- Fulfillment ---- */
  let ffType = window.KRAVED?.fulfillment?.type || 'collection';

  function collectionAddress() {
    return window.KRAVED?.collectionAddress || window.KRAVED?.defaultLocation || 'Colne, UK';
  }

  function selectedZoneId() {
    return Number(document.getElementById('ff-zone')?.value || 0);
  }

  function setFulfillmentFields(type) {
    ffType = type === 'delivery' ? 'delivery' : 'collection';
    document.querySelectorAll('[data-ff-type]').forEach(b => {
      b.classList.toggle('active', b.dataset.ffType === ffType);
    });
    document.querySelectorAll('[data-ff-quick]').forEach(b => {
      b.classList.toggle('active', b.dataset.ffQuick === ffType);
    });
    document.getElementById('ff-delivery-fields')?.classList.toggle('d-none', ffType !== 'delivery');
    document.getElementById('ff-collection-note')?.classList.toggle('d-none', ffType !== 'collection');
  }

  function applyFulfillmentUi(f) {
    const type = f?.type || 'collection';
    const isDelivery = type === 'delivery';
    setFulfillmentFields(type);
    const typeLabel = document.getElementById('ff-type-label');
    const slotLabel = document.getElementById('ff-slot-label');
    const detail = document.getElementById('ff-detail-label');
    const prefix = document.getElementById('ff-loc-prefix');
    const bar = document.getElementById('location-bar');
    const chevron = document.getElementById('ff-loc-chevron');
    const changeBtn = document.getElementById('ff-change-btn');
    if (typeLabel) typeLabel.textContent = isDelivery ? 'Delivery' : 'Collection';
    if (slotLabel) slotLabel.textContent = f?.time_slot || 'ASAP';
    if (prefix) prefix.textContent = isDelivery ? 'Delivering to:' : 'Collecting from:';
    if (detail) {
      detail.textContent = isDelivery
        ? (f?.postcode || f?.zone?.postcode_prefix || '')
        : collectionAddress();
    }
    if (bar) {
      bar.classList.toggle('is-static', !isDelivery);
      if (isDelivery) {
        bar.setAttribute('role', 'button');
        bar.setAttribute('tabindex', '0');
        bar.setAttribute('data-bs-toggle', 'modal');
        bar.setAttribute('data-bs-target', '#fulfillmentModal');
      } else {
        bar.removeAttribute('role');
        bar.removeAttribute('tabindex');
        bar.removeAttribute('data-bs-toggle');
        bar.removeAttribute('data-bs-target');
      }
    }
    if (chevron) chevron.hidden = !isDelivery;
    if (changeBtn) changeBtn.hidden = !isDelivery;
    const zoneSel = document.getElementById('ff-zone');
    if (zoneSel) {
      if (f?.zone?.id) {
        zoneSel.value = String(f.zone.id);
      } else if (f?.postcode) {
        const match = [...zoneSel.options].find(o => (o.dataset.prefix || '') === String(f.postcode).toUpperCase());
        if (match) zoneSel.value = match.value;
      }
    }
  }

  async function saveFulfillment(type) {
    const msg = document.getElementById('ff-zone-msg');
    const zoneEl = document.getElementById('ff-zone');
    const body = {
      type,
      time_slot: document.getElementById('ff-slot')?.value || 'ASAP',
    };
    if (type === 'delivery') {
      body.zone_id = selectedZoneId();
      body.postcode = zoneEl?.selectedOptions?.[0]?.dataset.prefix || '';
    }
    const data = await api('/api/fulfillment', { method: 'POST', body });
    if (msg) { msg.textContent = data.message || 'Saved'; msg.className = 'small mb-3 text-success'; }
    applyFulfillmentUi(data.fulfillment || { type });
    if (window.KRAVED) window.KRAVED.fulfillment = data.fulfillment;
    if (data.cart) renderCart(data.cart);
    return data;
  }

  document.querySelectorAll('[data-ff-type]').forEach(btn => {
    btn.addEventListener('click', () => {
      setFulfillmentFields(btn.dataset.ffType);
    });
  });

  document.getElementById('ff-save')?.addEventListener('click', async () => {
    const msg = document.getElementById('ff-zone-msg');
    if (ffType === 'delivery' && !selectedZoneId()) {
      if (msg) { msg.textContent = 'Please choose a postal area.'; msg.className = 'small mb-3 text-danger'; }
      document.getElementById('ff-zone')?.focus();
      return;
    }
    try {
      await saveFulfillment(ffType);
      setTimeout(() => {
        kravedModal(document.getElementById('fulfillmentModal'))?.hide();
      }, 250);
    } catch (err) {
      if (msg) { msg.textContent = err.data?.message || err.data?.error || err.message; msg.className = 'small mb-3 text-danger'; }
    }
  });

  /* ---- Product modal ---- */
  let pmState = { product: null, groups: [], boxChoices: [], variants: [], images: [], qty: 1, variantId: null };
  let upsellState = { active: false, continueUrl: null, onPage: false, openingProduct: false };

  const pmModalEl = document.getElementById('productModal');

  document.body.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-add');
    if (!btn) return;
    e.preventDefault();
    const id = btn.dataset.productId;
    if (!id) return;
    const body = document.getElementById('pm-body');
    if (body) body.innerHTML = '<div class="text-center py-4 text-muted">Loading…</div>';
    pmState.qty = 1;
    pmState.variantId = null;
    const qtyEl = document.getElementById('pm-qty');
    if (qtyEl) qtyEl.textContent = '1';
    if (upsellState.active) {
      upsellState.openingProduct = true;
      kravedModal(document.getElementById('checkoutUpsellModal'))?.hide();
    }
    kravedModal(pmModalEl)?.show();
    try {
      const data = await api('/api/product/' + id + '/customise');
      pmState.product = data.product;
      pmState.groups = data.groups || [];
      pmState.boxChoices = data.box_choices || [];
      pmState.variants = data.variants || [];
      pmState.images = data.images || [];
      pmState.salePrice = Number(data.sale_price ?? data.product.base_price);
      pmState.offer = data.offer || null;
      if (pmState.variants.length) {
        pmState.variantId = Number(pmState.variants[0].id);
      }
      const titleEl = document.getElementById('pm-title');
      if (titleEl) titleEl.textContent = data.product.title;
      if (body) body.innerHTML = buildCustomiseHtml(data);
      updatePmPrice();
    } catch (err) {
      if (body) body.innerHTML = '<p class="text-danger">Could not load product.</p>';
    }
  });

  function uploadUrl(path) {
    if (!path) return '';
    if (/^https?:\/\//i.test(path) || path.startsWith('/')) return path;
    return base() + '/uploads/' + String(path).replace(/^\/+/, '');
  }

  function buildCustomiseHtml(data) {
    let html = '';
    const imgs = data.images || [];
    if (imgs.length) {
      html += `<div class="pm-gallery mb-3">
        <img src="${escapeHtml(uploadUrl(imgs[0]))}" alt="" class="pm-main-img rounded w-100 mb-2" id="pm-main-img" style="max-height:220px;object-fit:cover">
        ${imgs.length > 1 ? `<div class="d-flex gap-2 flex-wrap">${imgs.map((src, i) => `
          <button type="button" class="pm-thumb border-0 p-0 rounded overflow-hidden ${i === 0 ? 'active' : ''}" data-src="${escapeHtml(uploadUrl(src))}" style="width:52px;height:52px">
            <img src="${escapeHtml(uploadUrl(src))}" alt="" style="width:100%;height:100%;object-fit:cover">
          </button>`).join('')}</div>` : ''}
      </div>`;
    }
    html += `<p class="opacity-75">${escapeHtml(data.product.short_description || '')}</p>`;
    if (data.offer) {
      const label = data.offer.discount_type === 'percent'
        ? `${Number(data.offer.discount_value)}% off`
        : `Save ${money(data.offer.discount_value)}`;
      html += `<p class="pc-sale-badge" style="position:static;display:inline-flex;margin-bottom:.75rem">${escapeHtml(data.offer.title || 'Offer')} · ${escapeHtml(label)}</p>`;
    }

    if (data.variants?.length) {
      html += `<div class="addon-group" id="pm-variants">
        <h3>Choose size</h3>
        ${data.variants.map((v, i) => {
          const original = Number(v.original_price ?? v.price);
          const sale = Number(v.sale_price ?? v.price);
          const onSale = sale < original - 0.001;
          return `
          <label class="addon-option">
            <span class="addon-check">
              <input type="radio" class="variant-input" name="pm_variant" value="${v.id}" data-price="${original}" data-sale-price="${sale}" ${i === 0 ? 'checked' : ''}>
              <span class="addon-tick"></span>
            </span>
            <span class="addon-name">${escapeHtml(v.label)}</span>
            <span class="addon-price">${onSale ? `<s class="opacity-75">${money(original)}</s> ${money(sale)}` : money(sale)}</span>
          </label>`;
        }).join('')}
      </div>`;
    }

    if (Number(data.product.is_box_deal) && data.box_choices?.length) {
      const max = Number(data.product.box_max_items) || 4;
      html += `<div class="addon-group" data-box-max="${max}">
        <h3>Pick ${max} cookies</h3>
        <p class="addon-hint">Select exactly ${max}</p>
        ${data.box_choices.map(c => `
          <label class="addon-option">
            <span class="addon-check">
              <input type="checkbox" class="box-pick" value="${c.id}">
              <span class="addon-tick"></span>
            </span>
            <span class="addon-name">${escapeHtml(c.title)}</span>
            <span class="addon-price"></span>
          </label>`).join('')}
      </div>`;
    }
    (data.groups || []).forEach(g => {
      const min = Number(g.min_selection || 0);
      const maxRaw = Number(g.max_selection);
      const max = !Number.isFinite(maxRaw) || maxRaw <= 0 ? 99 : maxRaw;
      const required = Number(g.is_required);
      const type = (max === 1 && (required || min >= 1)) ? 'radio' : 'checkbox';
      let hint = '';
      if (type === 'checkbox') {
        if (min > 0 && max < 99) hint = min === max ? `Choose ${min}` : `Choose ${min}–${max}`;
        else if (max < 99) hint = `Choose up to ${max}`;
        else if (min > 0) hint = `Choose at least ${min}`;
        else hint = 'Select any that apply';
      }
      html += `<div class="addon-group" data-group-id="${g.id}" data-min="${min}" data-max="${max}" data-required="${required}">
        <h3>${escapeHtml(g.title)}${required ? ' *' : ''}</h3>
        ${hint ? `<p class="addon-hint">${escapeHtml(hint)}</p>` : ''}
        ${(g.addons || []).map(a => `
          <label class="addon-option">
            <span class="addon-check">
              <input type="${type}" class="addon-input" name="g${g.id}" value="${a.id}" data-price="${a.price}">
              <span class="addon-tick"></span>
            </span>
            <span class="addon-name">${escapeHtml(a.name)}</span>
            <span class="addon-price">${Number(a.price) > 0 ? '+' + money(a.price) : 'Free'}</span>
          </label>`).join('')}
      </div>`;
    });
    if (!Number(data.product.is_box_deal) && !(data.groups || []).length && !(data.variants || []).length) {
      html += '<p class="small opacity-75">No extras — ready to add.</p>';
    }
    return html;
  }

  function selectedAddonIds() {
    return [...document.querySelectorAll('#pm-body .addon-input:checked')].map(el => Number(el.value));
  }

  function selectedBoxPicks() {
    return [...document.querySelectorAll('#pm-body .box-pick:checked')].map(el => Number(el.value));
  }

  function selectedVariantId() {
    const el = document.querySelector('#pm-body .variant-input:checked');
    return el ? Number(el.value) : null;
  }

  function selectedVariantPrice() {
    const el = document.querySelector('#pm-body .variant-input:checked');
    if (el) return Number(el.dataset.salePrice || el.dataset.price || 0);
    return Number(pmState.salePrice ?? pmState.product?.base_price ?? 0);
  }

  function updatePmPrice() {
    if (!pmState.product) return;
    let extra = 0;
    document.querySelectorAll('#pm-body .addon-input:checked').forEach(el => {
      extra += Number(el.dataset.price || 0);
    });
    const unit = selectedVariantPrice() + extra;
    const total = unit * pmState.qty;
    document.getElementById('pm-add').textContent = `Add · ${money(total)}`;
  }

  document.getElementById('pm-body')?.addEventListener('click', (e) => {
    const thumb = e.target.closest('.pm-thumb');
    if (!thumb) return;
    const main = document.getElementById('pm-main-img');
    if (main && thumb.dataset.src) main.src = thumb.dataset.src;
    document.querySelectorAll('#pm-body .pm-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
  });

  document.getElementById('pm-body')?.addEventListener('change', (e) => {
    if (e.target.classList.contains('variant-input')) {
      pmState.variantId = Number(e.target.value);
    }
    if (e.target.classList.contains('box-pick')) {
      const max = Number(e.target.closest('[data-box-max]')?.dataset.boxMax || 4);
      const checked = document.querySelectorAll('#pm-body .box-pick:checked');
      if (checked.length > max) {
        e.target.checked = false;
      }
    }
    // Enforce max per addon group
    const group = e.target.closest('.addon-group');
    if (group) {
      group.classList.remove('has-error');
      group.querySelectorAll('.addon-error-msg').forEach(el => el.remove());
      if (e.target.classList.contains('addon-input') && e.target.type === 'checkbox') {
        const max = Number(group.dataset.max || 99) || 99;
        const checked = group.querySelectorAll('.addon-input:checked');
        if (checked.length > max) e.target.checked = false;
      }
    }
    updatePmPrice();
  });

  document.getElementById('pm-minus')?.addEventListener('click', () => {
    pmState.qty = Math.max(1, pmState.qty - 1);
    document.getElementById('pm-qty').textContent = String(pmState.qty);
    updatePmPrice();
  });
  document.getElementById('pm-plus')?.addEventListener('click', () => {
    pmState.qty += 1;
    document.getElementById('pm-qty').textContent = String(pmState.qty);
    updatePmPrice();
  });

  document.getElementById('pm-add')?.addEventListener('click', async () => {
    if (!pmState.product) return;

    // Clear previous error states
    document.querySelectorAll('#pm-body .has-error').forEach(el => el.classList.remove('has-error'));
    document.querySelectorAll('#pm-body .addon-error-msg').forEach(el => el.remove());

    if (pmState.variants.length && !selectedVariantId()) {
      const varGroup = document.getElementById('pm-variants');
      if (varGroup) {
        varGroup.classList.add('has-error');
        varGroup.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      kravedToast('Please select your preferred size option.', 'warning', 'Size Required');
      return;
    }

    // Validate required groups
    let firstFailedGroup = null;
    let failedTitle = '';
    let failedMin = 1;

    for (const g of pmState.groups) {
      if (!Number(g.is_required) && Number(g.min_selection) === 0) continue;
      const wrap = document.querySelector(`.addon-group[data-group-id="${g.id}"]`);
      const n = wrap ? wrap.querySelectorAll('.addon-input:checked').length : 0;
      const minReq = Number(g.min_selection || (g.is_required ? 1 : 0));

      if (n < minReq) {
        if (!firstFailedGroup && wrap) {
          firstFailedGroup = wrap;
          failedTitle = g.title;
          failedMin = minReq;
        }
        if (wrap) {
          wrap.classList.add('has-error');
          if (!wrap.querySelector('.addon-error-msg')) {
            const errEl = document.createElement('div');
            errEl.className = 'addon-error-msg';
            errEl.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> ${minReq > 1 ? `Please select at least ${minReq} options` : 'Selection required'}`;
            const header = wrap.querySelector('.addon-hint') || wrap.querySelector('h3');
            if (header && header.nextSibling) {
              wrap.insertBefore(errEl, header.nextSibling);
            } else {
              wrap.appendChild(errEl);
            }
          }
        }
      }
    }

    if (firstFailedGroup) {
      firstFailedGroup.scrollIntoView({ behavior: 'smooth', block: 'center' });
      const msg = failedMin > 1
        ? `Please select at least ${failedMin} options for ${failedTitle}.`
        : `Please choose an option for ${failedTitle} to continue.`;
      kravedToast(msg, 'warning', 'Selection Required');
      return;
    }

    if (Number(pmState.product.is_box_deal)) {
      const max = Number(pmState.product.box_max_items) || 4;
      const picked = selectedBoxPicks().length;
      if (picked !== max) {
        const boxGroup = document.querySelector('[data-box-max]');
        if (boxGroup) {
          boxGroup.classList.add('has-error');
          boxGroup.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        kravedToast(`Please select exactly ${max} cookies for this box (${picked}/${max} chosen).`, 'warning', 'Incomplete Selection');
        return;
      }
    }

    try {
      const data = await api('/api/cart/add', {
        method: 'POST',
        body: {
          product_id: Number(pmState.product.id),
          quantity: pmState.qty,
          variant_id: selectedVariantId(),
          addon_ids: selectedAddonIds(),
          box_picks: selectedBoxPicks(),
        },
      });
      renderCart(data.cart);
      pmState.justAdded = true;
      kravedModal(pmModalEl)?.hide();
    } catch (err) {
      kravedToast(err.data?.error || err.message || 'Could not add item to cart.', 'error', 'Error');
    }
  });

  /* ---- Checkout extras popup ---- */
  const upsellEl = document.getElementById('checkoutUpsellModal');
  const productCache = {};

  function shouldShowUpsell() {
    const cfg = window.KRAVED?.upsell;
    return !!(cfg?.enabled && cfg.categories?.length);
  }

  function markUpsellSeen() {
    try { sessionStorage.setItem('kraved-upsell-seen', '1'); } catch (e) { /* ignore */ }
  }

  function showUpsellHome() {
    document.getElementById('upsell-home')?.classList.remove('d-none');
    document.getElementById('upsell-products-wrap')?.classList.add('d-none');
  }

  function showUpsellPanel() {
    kravedModal(upsellEl)?.show();
  }

  function finishUpsell() {
    markUpsellSeen();
    upsellState.active = false;
    kravedModal(upsellEl)?.hide();
    if (upsellState.continueUrl && !upsellState.onPage) {
      window.location.href = upsellState.continueUrl;
    }
  }

  function renderUpsellCats() {
    const wrap = document.getElementById('upsell-cats');
    const cfg = window.KRAVED?.upsell || {};
    if (!wrap) return;
    document.getElementById('upsell-title').textContent = cfg.title || 'Want a little extra?';
    document.getElementById('upsell-subtitle').textContent = cfg.subtitle || '';
    wrap.innerHTML = (cfg.categories || []).map(cat => `
      <button type="button" class="upsell-cat-btn" data-cat-id="${cat.id}" data-cat-name="${escapeHtml(cat.label || cat.name)}">
        ${cat.image ? `<img src="${escapeHtml(cat.image)}" alt="">` : '<span class="upsell-cat-icon">＋</span>'}
        <strong>${escapeHtml(cat.label || cat.name)}</strong>
        <small>Browse ${escapeHtml(cat.name)}</small>
      </button>`).join('');
  }

  async function openUpsellCategory(id, name) {
    const list = document.getElementById('upsell-products');
    const title = document.getElementById('upsell-cat-title');
    document.getElementById('upsell-home')?.classList.add('d-none');
    document.getElementById('upsell-products-wrap')?.classList.remove('d-none');
    if (title) title.textContent = name || 'Products';
    if (list) list.innerHTML = '<p class="text-muted">Loading…</p>';
    try {
      if (!productCache[id]) {
        productCache[id] = await api('/api/category/' + id + '/products');
      }
      const data = productCache[id];
      if (!data.products?.length) {
        list.innerHTML = '<p class="text-muted">No products in this category yet.</p>';
        return;
      }
      list.innerHTML = data.products.map(p => `
        <article class="upsell-product">
          <img src="${escapeHtml(p.image)}" alt="">
          <div class="upsell-product-body">
            <h4>${escapeHtml(p.title)}</h4>
            <p>${escapeHtml(p.short_description || '')}</p>
            <div class="upsell-product-row">
              <span>${p.from ? 'From ' : ''}${p.on_offer && p.original_price > p.price ? `<s class="opacity-75">${money(p.original_price)}</s> ` : ''}${money(p.price)}</span>
              <button type="button" class="btn-add-round btn-add" data-product-id="${p.id}" aria-label="Add ${escapeHtml(p.title)}">+</button>
            </div>
          </div>
        </article>`).join('');
    } catch (err) {
      if (list) list.innerHTML = '<p class="text-danger">Could not load this category.</p>';
    }
  }

  function openUpsell(opts = {}) {
    if (!shouldShowUpsell() || !upsellEl) {
      if (opts.continueUrl) window.location.href = opts.continueUrl;
      return;
    }
    upsellState.active = true;
    upsellState.continueUrl = opts.continueUrl || null;
    upsellState.onPage = !!opts.onPage;
    const continueBtn = document.getElementById('upsell-continue');
    if (continueBtn) {
      continueBtn.textContent = upsellState.onPage ? 'Continue with my order' : 'Continue to checkout';
    }
    renderUpsellCats();
    showUpsellHome();
    kravedModal(upsellEl)?.show();
  }

  document.getElementById('upsell-cats')?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-cat-id]');
    if (!btn) return;
    openUpsellCategory(btn.dataset.catId, btn.dataset.catName);
  });
  document.getElementById('upsell-back')?.addEventListener('click', showUpsellHome);
  document.getElementById('upsell-skip')?.addEventListener('click', finishUpsell);
  document.getElementById('upsell-continue')?.addEventListener('click', finishUpsell);
  upsellEl?.addEventListener('hidden.bs.modal', () => {
    if (upsellState.openingProduct) return;
    if (upsellState.active) finishUpsell();
  });
  pmModalEl?.addEventListener('hidden.bs.modal', () => {
    if (upsellState.active) {
      upsellState.openingProduct = false;
      showUpsellPanel();
      return;
    }
    if (pmState.justAdded) {
      pmState.justAdded = false;
      openCart();
    }
  });

  document.getElementById('cart-checkout')?.addEventListener('click', (e) => {
    const btn = e.currentTarget;
    if (btn.classList.contains('disabled')) {
      e.preventDefault();
      const warn = minOrderMessage(lastCart || {});
      if (warn) kravedToast(warn, 'warning', 'Minimum Order Required');
      return;
    }
    if (shouldShowUpsell()) {
      e.preventDefault();
      closeCart();
      openUpsell({ continueUrl: base() + '/checkout' });
    }
  });

  if (document.querySelector('[data-page="checkout"]') && shouldShowUpsell()) {
    let seen = false;
    try { seen = sessionStorage.getItem('kraved-upsell-seen') === '1'; } catch (e) { seen = false; }
    if (!seen) {
      openUpsell({ onPage: true });
    }
  }

  /* ---- Smooth category nav ---- */
  document.querySelectorAll('.cat-link').forEach(link => {
    link.addEventListener('click', (e) => {
      const href = link.getAttribute('href');
      if (href?.startsWith('#')) {
        e.preventDefault();
        document.querySelector(href)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.querySelectorAll('.cat-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');
      }
    });
  });

  /* ---- Mobile nav ---- */
  document.getElementById('nav-toggle')?.addEventListener('click', () => {
    document.getElementById('main-nav')?.classList.toggle('open');
  });

  /* ---- Popular carousel ---- */
  const track = document.getElementById('popular-track');
  document.getElementById('popular-prev')?.addEventListener('click', () => {
    track?.scrollBy({ left: -280, behavior: 'smooth' });
  });
  document.getElementById('popular-next')?.addEventListener('click', () => {
    track?.scrollBy({ left: 280, behavior: 'smooth' });
  });

  /* ---- Hero fulfillment quick select ---- */
  document.querySelectorAll('[data-ff-quick]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const type = btn.dataset.ffQuick;
      if (type === 'collection') {
        e.preventDefault();
        setFulfillmentFields('collection');
        try { await saveFulfillment('collection'); } catch (err) { /* ignore */ }
        kravedModal(document.getElementById('fulfillmentModal'))?.hide();
        return;
      }
      setFulfillmentFields('delivery');
    });
  });

  document.getElementById('location-bar')?.addEventListener('click', (e) => {
    if (e.currentTarget.classList.contains('is-static')) {
      e.preventDefault();
      e.stopPropagation();
    }
  }, true);

  /* ---- Wishlist ---- */
  const loginUrl = window.KRAVED?.loginUrl || (base() + '/login?next=wishlist');
  const wishLoginPrompt = document.getElementById('wishlist-login-prompt');
  const wishLoginProceed = document.getElementById('wishlist-login-proceed');
  if (wishLoginProceed) wishLoginProceed.href = loginUrl;

  function setWishState(btn, wished) {
    btn.classList.toggle('is-wished', wished);
    btn.setAttribute('aria-pressed', wished ? 'true' : 'false');
    btn.setAttribute('aria-label', wished ? 'Remove from wishlist' : 'Add to wishlist');
    btn.title = wished ? 'Saved' : 'Save to wishlist';
  }

  function syncWishBadge(count) {
    const badge = document.getElementById('wish-badge');
    if (badge) badge.textContent = String(count);
  }

  function closeWishlistLogin() {
    if (wishLoginPrompt) wishLoginPrompt.hidden = true;
    document.body.classList.remove('kraved-modal-open');
  }

  function openWishlistLogin() {
    if (wishLoginProceed) wishLoginProceed.href = loginUrl;
    if (wishLoginPrompt) {
      wishLoginPrompt.hidden = false;
      document.body.classList.add('kraved-modal-open');
      return;
    }
    window.location.href = loginUrl;
  }

  document.getElementById('wishlist-login-cancel')?.addEventListener('click', closeWishlistLogin);
  wishLoginPrompt?.addEventListener('click', (e) => {
    if (e.target === wishLoginPrompt) closeWishlistLogin();
  });

  document.body.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-wishlist]');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    if (!window.KRAVED?.loggedIn) {
      openWishlistLogin();
      return;
    }
    const productId = Number(btn.dataset.productId);
    try {
      const data = await api('/api/wishlist/toggle', { method: 'POST', body: { product_id: productId } });
      document.querySelectorAll('[data-wishlist][data-product-id="' + productId + '"]').forEach((el) => {
        setWishState(el, !!data.wished);
      });
      if (typeof data.count === 'number') syncWishBadge(data.count);
      if (!data.wished) {
        const card = btn.closest('.account-page .product-card');
        card?.remove();
      }
    } catch (err) {
      if (err.status === 401) openWishlistLogin();
      else kravedToast(err.data?.error || err.message || 'Action failed.', 'error', 'Error');
    }
  });

  /* ---- Order status polling ---- */
  const tracker = document.getElementById('status-tracker');
  if (tracker) {
    const orderNo = tracker.dataset.order;
    setInterval(async () => {
      try {
        const data = await api('/api/order/' + encodeURIComponent(orderNo) + '/status');
        if (Array.isArray(data.steps) && data.steps.length) {
          tracker.innerHTML = data.steps.map(step => {
            const key = escapeHtml(step.key || '');
            const state = escapeHtml(step.state || 'upcoming');
            const label = escapeHtml(step.label || '');
            return '<div class="status-step ' + state + '" data-key="' + key + '">'
              + '<div class="status-dot"></div>'
              + '<div class="status-label">' + label + '</div>'
              + '</div>';
          }).join('');
        }
        const pill = document.querySelector('.order-details-head .order-status-pill');
        if (pill && data.order_status) {
          pill.className = 'order-status-pill ' + data.order_status;
          pill.textContent = data.label || data.order_status;
        }
      } catch (e) { /* ignore */ }
    }, 8000);
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  refreshCart();
})();
} catch (err) {
  console.error('Kraved storefront failed to start', err);
}
