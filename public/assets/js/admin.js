(() => {
  const base = () => (window.KRAVED?.baseUrl || '').replace(/\/$/, '');
  const csrf = () => window.KRAVED?.csrf
    || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || '';

  function slugify(text) {
    return String(text || '')
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'item';
  }

  function bindSlugPreview(source) {
    const form = source.closest('form');
    if (!form) return;
    const preview = form.querySelector('[data-slug-preview]');
    if (!preview) return;
    const sync = () => { preview.value = slugify(source.value); };
    source.addEventListener('input', sync);
    source.addEventListener('change', sync);
    if (!preview.value) sync();
  }

  document.querySelectorAll('[data-slug-source]').forEach(bindSlugPreview);

  let lastId = window.KRAVED_LAST_ORDER_ID || 0;
  let lastPlacedAt = window.KRAVED_LAST_PLACED_AT || '';

  async function poll() {
    if (typeof window.KRAVED_LAST_ORDER_ID === 'undefined') return;
    try {
      const qs = new URLSearchParams({ after: String(lastId) });
      if (lastPlacedAt) qs.set('placed_after', lastPlacedAt);
      const res = await fetch(base() + '/admin/api/orders/poll?' + qs.toString(), {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        credentials: 'same-origin',
      });
      const data = await res.json();
      if (data.pending != null) {
        const badge = document.getElementById('pending-badge');
        if (badge) badge.textContent = data.pending > 0 ? String(data.pending) : '';
      }
      if (data.latest_placed_at) lastPlacedAt = data.latest_placed_at;
      if (data.latest) lastId = data.latest;
      if (data.orders?.length) {
        const status = document.getElementById('poll-status');
        if (status) status.textContent = `${data.orders.length} new order(s) — refreshing…`;
        document.getElementById('order-chime')?.play().catch(() => {});
        setTimeout(() => location.reload(), 1200);
      }
    } catch (e) { /* ignore */ }
  }

  if (typeof window.KRAVED_LAST_ORDER_ID !== 'undefined') {
    setInterval(poll, 10000);
  }
})();
