<?php
use App\Core\AuthMiddleware;
use App\Core\Helpers;
use App\Models\Cart;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\SiteSection;

$user = AuthMiddleware::user();
$fulfillment = $fulfillment ?? Cart::fulfillment();
$cartCount = $cartCount ?? Cart::count();
$cms = $cms ?? SiteSection::map();
$storeName = (string) Helpers::setting('store_name', 'Kraved');
$heroCms = $cms['hero']['content'] ?? [];
try {
    $deliveryZones = (new DeliveryZone())->active();
} catch (\Throwable) {
    $deliveryZones = [];
}
$collectionAddress = (string) Helpers::setting('collection_address', 'Unit 2, Old Biscuit Factory, Dockray Street, Colne, BB8 9HT');
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= Helpers::e(Helpers::csrfToken()) ?>">
  <title><?= Helpers::e(($title ?? 'Order') . ' · ' . $storeName) ?></title>
  <link rel="icon" type="image/png" href="<?= Helpers::logo() ?>">
  <link rel="apple-touch-icon" href="<?= Helpers::logo() ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= Helpers::asset('css/store.css') ?>?v=<?= time() ?>" rel="stylesheet">
</head>
<body class="theme-light">
<?php require dirname(__DIR__, 2) . '/Partials/header.php'; ?>

<main>
  <?= $content ?>
</main>

<?php require dirname(__DIR__, 2) . '/Partials/footer.php'; ?>
<?php require dirname(__DIR__, 2) . '/Partials/fulfillment-modal.php'; ?>
<?php require dirname(__DIR__, 2) . '/Partials/product-modal.php'; ?>
<?php require dirname(__DIR__, 2) . '/Partials/wishlist-login-modal.php'; ?>
<?php require dirname(__DIR__, 2) . '/Partials/checkout-upsell-modal.php'; ?>
<?php require dirname(__DIR__, 2) . '/Partials/cart-drawer.php'; ?>

<script>
  window.KRAVED = {
    baseUrl: <?= json_encode(Helpers::baseUrl()) ?>,
    csrf: <?= json_encode(Helpers::csrfToken()) ?>,
    currency: <?= json_encode((string) Helpers::setting('currency_symbol', '£')) ?>,
    freeDelivery: <?= (float) Helpers::setting('free_delivery_threshold', 25) ?>,
    fulfillment: <?= json_encode($fulfillment) ?>,
    defaultLocation: <?= json_encode((string) ($heroCms['default_location'] ?? 'Colne, UK')) ?>,
    collectionAddress: <?= json_encode($collectionAddress) ?>,
    deliveryZones: <?= json_encode(array_map(static fn (array $z): array => [
        'id' => (int) $z['id'],
        'postcode_prefix' => $z['postcode_prefix'],
        'delivery_fee' => (float) $z['delivery_fee'],
        'min_order_amount' => (float) $z['min_order_amount'],
        'estimated_mins' => (int) ($z['estimated_mins'] ?? 45),
    ], $deliveryZones), JSON_UNESCAPED_UNICODE) ?>,
    loggedIn: <?= $user ? 'true' : 'false' ?>,
    loginUrl: <?= json_encode(Helpers::baseUrl('login?next=wishlist')) ?>,
    upsell: <?= json_encode($upsell ?? Category::checkoutUpsell()) ?>
  };
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="<?= Helpers::asset('js/store.js') ?>" defer></script>
</body>
</html>
