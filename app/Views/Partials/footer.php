<?php
use App\Core\AuthMiddleware;
use App\Core\Helpers;
use App\Models\SiteSection;

$user = $user ?? AuthMiddleware::user();

$footer = ($cms['footer']['content'] ?? null) ?: SiteSection::content('footer');
$showFooter = !isset($cms['footer']) || !empty($cms['footer']['is_visible']);
if (!$showFooter) {
    return;
}
$storeName = (string) Helpers::setting('store_name', 'Kraved');
$madeIn = (string) ($footer['made_in'] ?? 'Made with ♥ in Colne');
$address = (string) Helpers::setting('collection_address', 'Unit 2, Old Biscuit Factory, Dockray Street, Colne, BB8 9HT');
$phone = (string) Helpers::setting('contact_phone', '01282 943888');
$hours = (string) Helpers::setting('contact_hours', 'Mon–Fri 5pm to 11pm · Sat & Sun 2pm to 11pm');
$tagline = (string) ($footer['tagline'] ?? Helpers::setting('store_tagline', 'Taste the sweetness in every bite.'));
$phoneHref = 'tel:' . preg_replace('/\s+/', '', $phone);
?>
<footer class="site-footer" id="contact">
  <div class="container footer-grid">
    <div class="footer-brand-col">
      <a class="footer-logo-link" href="<?= Helpers::baseUrl() ?>" aria-label="<?= Helpers::e($storeName) ?>">
        <img class="footer-logo-img" src="<?= Helpers::logo() ?>" alt="<?= Helpers::e($storeName) ?>" width="120" height="120">
      </a>
      <p class="footer-mission"><?= Helpers::e($footer['mission'] ?? '') ?></p>
      <?php if ($tagline !== ''): ?>
        <p class="footer-tagline"><?= Helpers::e($tagline) ?></p>
      <?php endif; ?>
      <div class="footer-social">
        <?php foreach (($footer['social'] ?? []) as $s): ?>
          <a href="<?= Helpers::e($s['url'] ?? '#') ?>" aria-label="<?= Helpers::e($s['aria'] ?? ($s['label'] ?? '')) ?>"><?= Helpers::e($s['label'] ?? '') ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <div>
      <h3 class="footer-heading">Quick Links</h3>
      <ul class="footer-links">
        <?php foreach (($footer['quick_links'] ?? []) as $link): ?>
          <li><a href="<?= Helpers::e(SiteSection::resolveHref($link['href'] ?? '#')) ?>"><?= Helpers::e($link['label'] ?? '') ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div>
      <h3 class="footer-heading">Our Services</h3>
      <ul class="footer-links">
        <li><a href="<?= Helpers::e(SiteSection::resolveHref('/#menu')) ?>">Order Online</a></li>
        <?php if ($user): ?>
          <li><a href="<?= Helpers::baseUrl('orders') ?>">My orders</a></li>
          <li><a href="<?= Helpers::baseUrl('wishlist') ?>">Wishlist</a></li>
        <?php else: ?>
          <li><a href="<?= Helpers::baseUrl('login') ?>">Sign in</a></li>
        <?php endif; ?>
        <li><a href="#" data-bs-toggle="modal" data-bs-target="#fulfillmentModal" data-ff-quick="delivery">Delivery</a></li>
        <li><a href="#" data-ff-quick="collection">Collection</a></li>
      </ul>
    </div>

    <div class="footer-contact">
      <h3 class="footer-heading">Contact Us</h3>
      <ul class="footer-contact-list">
        <li>
          <span class="footer-contact-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 10h16v10H4z"/><path d="M8 10V7l4-3 4 3v3"/></svg>
          </span>
          <span><?= Helpers::e($address) ?></span>
        </li>
        <li>
          <span class="footer-contact-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6.5 3.5 9 6.2c.3.3.3.8 0 1.1L7.8 8.5c-.3.3-.3.7 0 1 1.4 1.6 3.1 3.3 4.7 4.7.3.3.7.3 1 0l1.2-1.2c.3-.3.8-.3 1.1 0l2.7 2.5c.4.4.4 1 0 1.4l-1.3 1.3c-.8.8-2 .9-3.1.4-2.6-1.2-5.3-3.4-7.6-7.6-.5-1.1-.4-2.3.4-3.1l1.3-1.3c.4-.4 1-.4 1.4 0Z"/></svg>
          </span>
          <a href="<?= Helpers::e($phoneHref) ?>"><?= Helpers::e($phone) ?></a>
        </li>
        <li>
          <span class="footer-contact-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.5 3 3.8 6 3.8 9s-1.3 6-3.8 9c-2.5-3-3.8-6-3.8-9S9.5 6 12 3Z"/></svg>
          </span>
          <a href="https://www.kraveddesserts.com">www.kraveddesserts.com</a>
        </li>
        <li>
          <span class="footer-contact-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          </span>
          <span><?= Helpers::e($hours) ?></span>
        </li>
      </ul>
    </div>
  </div>

  <div class="container footer-bottom">
    <p>&copy; <?= date('Y') ?> <?= Helpers::e($footer['copyright'] ?? ($storeName . '. All rights reserved.')) ?></p>
    <p class="made-uk"><?= str_replace('♥', '<span class="heart">♥</span>', Helpers::e($madeIn)) ?></p>
  </div>
</footer>
