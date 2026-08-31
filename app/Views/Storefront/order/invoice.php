<?php
use App\Core\Helpers;

$storeName = (string) Helpers::setting('store_name', 'Kraved');
$address = (string) Helpers::setting('collection_address', 'Unit 2, Old Biscuit Factory, Dockray Street, Colne, BB8 9HT');
$phone = (string) Helpers::setting('contact_phone', '01282 943888');
$placed = strtotime((string) $order['created_at']);
$payMethod = Helpers::paymentMethodLabel((string) ($order['payment_method'] ?? ''));
$payStatus = Helpers::paymentStatusLabel((string) ($order['payment_status'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Invoice <?= Helpers::e($order['order_number']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root { --choc:#2c1810; --muted:#6b5244; --line:rgba(44,24,16,.14); --accent:#6b3e2a; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #f6efe4; color: var(--choc); font-family: Outfit, system-ui, sans-serif; }
    .toolbar { max-width: 760px; margin: 1.25rem auto 0; display: flex; gap: .6rem; justify-content: flex-end; padding: 0 1rem; }
    .toolbar a, .toolbar button {
      border: 0; border-radius: 999px; padding: .65rem 1.15rem; font-weight: 700; cursor: pointer; text-decoration: none;
      font-family: inherit; font-size: .92rem;
    }
    .toolbar button { background: #3d2418; color: #fff; }
    .toolbar a { background: #fff; color: var(--choc); border: 1px solid var(--line); }
    .sheet {
      max-width: 760px; margin: 1rem auto 2.5rem; background: #fffaf4;
      border: 1px solid var(--line); border-radius: 22px; padding: 2rem 2.1rem 1.75rem;
      box-shadow: 0 18px 40px rgba(44,24,16,.1);
    }
    .head { display: flex; justify-content: space-between; gap: 1.5rem; border-bottom: 1px dashed var(--line); padding-bottom: 1.2rem; }
    h1 { font-family: Fraunces, Georgia, serif; margin: 0; font-size: 1.8rem; }
    .eyebrow { letter-spacing: .14em; text-transform: uppercase; font-size: .72rem; color: var(--muted); margin: 0 0 .35rem; }
    .meta { text-align: right; font-size: .92rem; color: var(--muted); }
    .meta strong { display: block; color: var(--choc); font-size: 1.05rem; }
    .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin: 1.25rem 0 1.5rem; font-size: .92rem; }
    .cols h2 { font-size: .78rem; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin: 0 0 .4rem; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-size: .75rem; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); border-bottom: 1px solid var(--line); padding: .5rem 0; }
    td { padding: .7rem 0; border-bottom: 1px solid var(--line); vertical-align: top; }
    td.right, th.right { text-align: right; }
    .small { color: var(--muted); font-size: .82rem; }
    .totals { margin-top: 1rem; margin-left: auto; width: min(280px, 100%); }
    .totals div { display: flex; justify-content: space-between; padding: .3rem 0; }
    .totals .grand { font-weight: 800; font-size: 1.1rem; border-top: 1px solid var(--line); margin-top: .4rem; padding-top: .55rem; }
    .thanks { margin-top: 1.5rem; font-family: Fraunces, Georgia, serif; font-style: italic; color: var(--accent); }
    @media print {
      body { background: #fff; }
      .toolbar { display: none; }
      .sheet { box-shadow: none; border: 0; margin: 0; max-width: none; border-radius: 0; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <a href="<?= Helpers::baseUrl('order/' . $order['order_number']) ?>">Back to order</a>
    <button type="button" onclick="window.print()">Download / print PDF</button>
  </div>
  <article class="sheet">
    <div class="head">
      <div>
        <img src="<?= Helpers::logo() ?>" alt="<?= Helpers::e($storeName) ?>" width="72" height="72" style="border-radius:50%;background:#2c1810;display:block;margin-bottom:.65rem">
        <p class="eyebrow">Invoice</p>
        <h1><?= Helpers::e($storeName) ?></h1>
        <p class="small"><?= Helpers::e($address) ?><br><?= Helpers::e($phone) ?></p>
      </div>
      <div class="meta">
        <strong><?= Helpers::e($order['order_number']) ?></strong>
        <?= $placed ? date('d M Y · H:i', $placed) : '' ?><br>
        <?= Helpers::e(ucfirst((string) $order['fulfillment_type'])) ?> · <?= Helpers::e($order['delivery_time_slot'] ?: 'ASAP') ?>
      </div>
    </div>

    <div class="cols">
      <div>
        <h2>Billed to</h2>
        <p>
          <?= Helpers::e($order['customer_name']) ?><br>
          <?= Helpers::e($order['customer_email']) ?><br>
          <?= Helpers::e($order['customer_phone']) ?>
          <?php if ($order['fulfillment_type'] === 'delivery'): ?>
            <br><?= Helpers::e($order['delivery_address']) ?> <?= Helpers::e($order['postcode']) ?>
          <?php endif; ?>
        </p>
      </div>
      <div>
        <h2>Payment</h2>
        <p><?= Helpers::e($payMethod) ?><br><?= Helpers::e($payStatus) ?></p>
      </div>
    </div>

    <table>
      <thead>
        <tr><th>Item</th><th class="right">Qty</th><th class="right">Total</th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it):
          $meta = json_decode($it['addons_json'] ?? '{}', true) ?: [];
        ?>
        <tr>
          <td>
            <?= Helpers::e($it['product_name']) ?>
            <?php if (!empty($meta['variant']['label'])): ?><div class="small"><?= Helpers::e($meta['variant']['label']) ?></div><?php endif; ?>
            <?php foreach ($meta['addons'] ?? [] as $a): ?><div class="small">+ <?= Helpers::e($a['name']) ?></div><?php endforeach; ?>
            <?php foreach ($meta['box_picks'] ?? [] as $b): ?><div class="small">• <?= Helpers::e($b['name']) ?></div><?php endforeach; ?>
          </td>
          <td class="right"><?= (int) $it['quantity'] ?></td>
          <td class="right"><?= Helpers::money($it['total_item_price']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="totals">
      <div><span>Subtotal</span><span><?= Helpers::money($order['subtotal']) ?></span></div>
      <?php if ((float) ($order['discount_amount'] ?? 0) > 0): ?>
        <div><span>Promo <?= Helpers::e($order['promo_code'] ?? '') ?></span><span>−<?= Helpers::money($order['discount_amount']) ?></span></div>
      <?php endif; ?>
      <div><span>Delivery</span><span><?= Helpers::money($order['delivery_fee']) ?></span></div>
      <div class="grand"><span>Total</span><span><?= Helpers::money($order['total_amount']) ?></span></div>
    </div>
    <p class="thanks">Thank you — bake fresh, eat warm.</p>
  </article>
</body>
</html>
