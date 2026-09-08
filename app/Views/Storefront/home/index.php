<?php
use App\Core\Helpers;
use App\Models\SiteSection;

$f = $fulfillment ?? ['type' => 'collection', 'postcode' => '', 'time_slot' => 'ASAP'];
$isDelivery = ($f['type'] ?? 'collection') === 'delivery';
$collectionAddress = (string) Helpers::setting('collection_address', 'Unit 2, Old Biscuit Factory, Dockray Street, Colne, BB8 9HT');
$popular = $popular ?? [];
$cms = $cms ?? [];
$hero = $cms['hero']['content'] ?? SiteSection::content('hero');
$popularCfg = $cms['popular']['content'] ?? SiteSection::content('popular');
$about = $cms['about']['content'] ?? SiteSection::content('about');
$trust = $cms['trust_bar']['content'] ?? SiteSection::content('trust_bar');
$hygiene = $cms['hygiene']['content'] ?? SiteSection::content('hygiene');
$menuCfg = $cms['menu']['content'] ?? SiteSection::content('menu');
$gallery = $gallery ?? [
    Helpers::asset('images/product-lava-cake.png'),
    Helpers::asset('images/product-biscoff-cheesecake.png'),
    Helpers::asset('images/product-cookies-cream.png'),
    Helpers::asset('images/product-choc-waffle.png'),
    Helpers::asset('images/hero-dessert.png'),
];
$show = static fn (string $key): bool => !isset($cms[$key]) || !empty($cms[$key]['is_visible']);
?>

<?php if ($show('hero')): ?>
<section class="lp-hero">
  <div class="container lp-hero-grid">
    <div class="lp-hero-copy">
      <h1 class="lp-headline"><?= Helpers::e($hero['headline_line1'] ?? '') ?><br><?= Helpers::e($hero['headline_line2'] ?? '') ?></h1>
      <p class="lp-subhead"><?= Helpers::e($hero['subhead'] ?? '') ?></p>

      <div class="fulfillment-pills" role="group" aria-label="Order type">
        <button type="button"
          class="ff-pill <?= !$isDelivery ? 'active' : '' ?>"
          data-ff-quick="collection">
          <span class="ff-pill-icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 7h12l-1 12H7L6 7Z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/></svg>
          </span>
          <span class="ff-pill-text">
            <strong><?= Helpers::e($hero['takeaway_title'] ?? 'Collection') ?></strong>
            <small><?= Helpers::e($hero['takeaway_subtitle'] ?? 'Collect in Store') ?></small>
          </span>
        </button>
        <button type="button"
          class="ff-pill <?= $isDelivery ? 'active' : '' ?>"
          data-ff-quick="delivery"
          data-bs-toggle="modal"
          data-bs-target="#fulfillmentModal">
          <span class="ff-pill-icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/><path d="M5 17H3V7l3-3h6l2 3h4v10h-2"/><path d="M9 4v3h5"/></svg>
          </span>
          <span class="ff-pill-text">
            <strong><?= Helpers::e($hero['delivery_title'] ?? 'Order Delivery') ?></strong>
            <small><?= Helpers::e($hero['delivery_subtitle'] ?? 'From Nearest Outlet') ?></small>
          </span>
        </button>
      </div>

      <div class="location-bar <?= $isDelivery ? '' : 'is-static' ?>" id="location-bar" <?= $isDelivery ? 'role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#fulfillmentModal"' : '' ?>>
        <span class="loc-label">
          <span id="ff-loc-prefix"><?= $isDelivery ? 'Delivering to:' : 'Collecting from:' ?></span>
          <strong id="ff-detail-label">
            <?php if ($isDelivery && !empty($f['postcode'])): ?>
              <?= Helpers::e($f['postcode']) ?>
            <?php else: ?>
              <?= Helpers::e($collectionAddress) ?>
            <?php endif; ?>
          </strong>
        </span>
        <span class="loc-chevron" id="ff-loc-chevron" <?= $isDelivery ? '' : 'hidden' ?>>▾</span>
      </div>
      <div class="outlet-status">
        <span class="status-dot"></span>
        <span><?= Helpers::e($hero['outlets_text'] ?? '3 outlets near you') ?></span>
        <button type="button" class="link-change" id="ff-change-btn" data-bs-toggle="modal" data-bs-target="#fulfillmentModal" <?= $isDelivery ? '' : 'hidden' ?>>Change</button>
      </div>
      <span class="visually-hidden">
        <span id="ff-type-label"><?= $isDelivery ? 'Delivery' : 'Collection' ?></span>
        <span id="ff-slot-label"><?= Helpers::e($f['time_slot'] ?? 'ASAP') ?></span>
      </span>
    </div>

    <div class="lp-hero-visual">
      <div class="hero-glow"></div>
      <div class="hero-frame">
        <img
          src="<?= Helpers::e(SiteSection::mediaUrl($hero['image'] ?? null)) ?>"
          alt="<?= Helpers::e($hero['image_alt'] ?? 'Dessert') ?>"
          class="hero-dessert-img"
          width="560"
          height="700"
          onerror="this.style.opacity=.3"
        >
        <div class="uk-badge" aria-label="Proudly Serving the UK">
          <span class="uk-flag-mark" aria-hidden="true"></span>
          <span><?= $hero['badge_html'] ?? 'Proudly<br>Serving<br>the UK' ?></span>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>


<?php if ($show('popular')): ?>
<section class="lp-popular section" id="popular">
  <div class="container">
    <div class="section-head">
      <h2 class="section-title"><?= Helpers::e($popularCfg['title'] ?? 'Popular Right Now') ?></h2>
      <a href="<?= Helpers::e(SiteSection::resolveHref($popularCfg['view_all_href'] ?? '#menu')) ?>" class="view-all"><?= Helpers::e($popularCfg['view_all_label'] ?? 'View All') ?> <span aria-hidden="true">›</span></a>
    </div>

    <div class="popular-carousel-wrap">
      <button type="button" class="carousel-btn prev" id="popular-prev" aria-label="Previous">‹</button>
      <div class="popular-track" id="popular-track">
        <?php if (!$popular): ?>
          <p class="text-muted p-3">No featured products yet. Mark products as Featured in admin.</p>
        <?php endif; ?>
        <?php foreach ($popular as $p): ?>
          <article class="pop-card">
            <?php if (!empty($p['badge'])): ?>
              <span class="pop-badge pop-badge--<?= Helpers::e($p['badge'][1]) ?>"><?= Helpers::e($p['badge'][0]) ?></span>
            <?php endif; ?>
            <div class="pop-media">
              <?php $wishlistProductId = (int) $p['id']; require dirname(__DIR__, 2) . '/Partials/wishlist-btn.php'; ?>
              <img src="<?= Helpers::e($p['image_url']) ?>" alt="<?= Helpers::e($p['title']) ?>" loading="lazy"
                onerror="this.onerror=null;this.src='<?= Helpers::e(Helpers::asset('images/hero-dessert.png')) ?>'">
            </div>
            <div class="pop-body">
              <h3><?= Helpers::e($p['title']) ?></h3>
              <p><?= Helpers::e($p['short_description']) ?></p>
              <div class="pop-row">
                <span class="pop-price"><?= Helpers::money($p['base_price']) ?></span>
                <button type="button" class="btn-add-round btn-add" data-product-id="<?= (int)$p['id'] ?>" aria-label="Add <?= Helpers::e($p['title']) ?>">+</button>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <button type="button" class="carousel-btn next" id="popular-next" aria-label="Next">›</button>
    </div>
  </div>
</section>
<?php endif; ?>


<?php if ($show('about')): ?>
<section class="lp-about section" id="about">
  <div class="container about-band">
    <div class="row align-items-center g-4">
      <?php if (!empty($about['image'])): ?>
        <div class="col-lg-5 col-md-6 order-md-2">
          <div class="about-image-wrap text-center">
            <img src="<?= Helpers::e(SiteSection::mediaUrl($about['image'])) ?>" alt="About Us" class="img-fluid rounded-4 shadow-sm" style="max-height:360px;object-fit:cover;width:100%">
          </div>
        </div>
      <?php endif; ?>
      <div class="<?= !empty($about['image']) ? 'col-lg-7 col-md-6' : 'col-12' ?> order-md-1">
        <div class="about-copy-col">
          <?php if (!empty($about['eyebrow'])): ?>
            <p class="eyebrow"><?= Helpers::e($about['eyebrow']) ?></p>
          <?php endif; ?>
          <?php if (!empty($about['copy'])): ?>
            <p class="about-copy"><?= Helpers::e($about['copy']) ?></p>
          <?php endif; ?>
          <?php if (!empty($about['copy_extra'])): ?>
            <p class="about-copy"><?= Helpers::e($about['copy_extra']) ?></p>
          <?php endif; ?>
          <?php if (!empty($about['story'])): ?>
            <p class="about-story mb-3"><?= Helpers::e($about['story']) ?></p>
          <?php endif; ?>
          <?php if (!empty($about['quote'])): ?>
            <p class="about-quote">“<?= Helpers::e($about['quote']) ?>”</p>
          <?php endif; ?>
          <?php if (!empty($about['mission'])): ?>
            <p class="about-mission"><?= Helpers::e($about['mission']) ?></p>
          <?php endif; ?>
          <?php if (!empty($about['cta_label'])): ?>
            <div class="mt-4">
              <a href="<?= Helpers::e(SiteSection::resolveHref($about['cta_href'] ?? '#menu')) ?>" class="btn btn-accent"><?= Helpers::e($about['cta_label']) ?></a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($show('hygiene')): ?>
<section class="lp-hygiene section" id="hygiene">
  <div class="container hygiene-band">
    <div class="hygiene-grid">
      <div class="hygiene-badge-wrap">
        <img src="<?= Helpers::e(SiteSection::mediaUrl($hygiene['badge_image'] ?? 'images/food-hygiene-rating-5.svg')) ?>" alt="<?= Helpers::e($hygiene['title'] ?? '5-Star Food Hygiene Rating') ?>" class="hygiene-badge-img" width="240" height="126">
      </div>
      <div class="hygiene-text-col">
        <?php if (!empty($hygiene['eyebrow'])): ?>
          <p class="eyebrow"><?= Helpers::e($hygiene['eyebrow']) ?></p>
        <?php endif; ?>
        <h2 class="hygiene-title"><?= Helpers::e($hygiene['title'] ?? '⭐ 5-Star Food Hygiene Rated') ?></h2>
        <?php if (!empty($hygiene['description'])): ?>
          <p class="hygiene-desc"><?= Helpers::e($hygiene['description']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($show('trust_bar')): ?>
<section class="trust-bar" id="offers">
  <div class="container">
    <div class="trust-row">
      <?php foreach (($trust['chips'] ?? []) as $chip): ?>
        <span class="trust-chip"><i aria-hidden="true">✦</i> <?= Helpers::e($chip) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<nav class="category-nav sticky-cat" id="cat-nav" aria-label="Menu categories">
  <div class="container">
    <div class="cat-scroll">
      <?php foreach ($categories as $cat): ?>
        <a href="#cat-<?= Helpers::e($cat['slug']) ?>" class="cat-link"><?= Helpers::e($cat['name']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</nav>

<?php if ($show('menu')): ?>
<section class="section" id="menu">
  <div class="container">
    <div class="menu-intro">
      <h2 class="section-title"><?= Helpers::e($menuCfg['title'] ?? 'Full Menu') ?></h2>
      <p class="section-lead"><?= Helpers::e($menuCfg['lead'] ?? '') ?></p>
    </div>
    <?php
    $g = 0;
    foreach ($menu as $block):
      $cat = $block['category'];
      $products = $block['products'];
      if (!$products) continue;
    ?>
      <div class="category-block" id="cat-<?= Helpers::e($cat['slug']) ?>">
        <h3 class="category-heading"><?= Helpers::e($cat['name']) ?></h3>
        <?php if (!empty($cat['sub_categories'])): ?>
          <div class="subcat-pills mb-3">
            <?php foreach ($cat['sub_categories'] as $sub): ?>
              <a href="<?= Helpers::baseUrl('subcategory/' . $sub['slug']) ?>" class="subcat-pill"><?= Helpers::e($sub['name']) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="product-grid">
          <?php foreach ($products as $p):
            if (empty($p['image'])) {
              $p['_fallback_image'] = $gallery[$g % count($gallery)];
              $g++;
            }
          ?>
            <?php require dirname(__DIR__, 2) . '/Partials/product-card.php'; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
