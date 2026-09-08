<?php
use App\Core\Helpers;
use App\Models\SiteSection;
$key = $section['section_key'];
$c = $content;
$icons = ['phone', 'pin', 'truck', 'bag', 'percent', 'store', 'chef'];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <a href="<?= Helpers::baseUrl('admin/cms') ?>" class="small text-muted">&larr; All sections</a>
    <h1 class="h3 mb-0"><?= Helpers::e($section['label']) ?></h1>
  </div>
</div>

<form method="post" action="<?= Helpers::baseUrl('admin/cms/' . rawurlencode($key) . '/update') ?>" enctype="multipart/form-data" class="bg-white rounded shadow-sm p-4">
  <?= Helpers::csrfField() ?>

  <div class="form-check form-switch mb-4">
    <input class="form-check-input" type="checkbox" name="is_visible" id="is_visible" <?= (int)$section['is_visible'] ? 'checked' : '' ?>>
    <label class="form-check-label" for="is_visible">Show this section on the storefront</label>
  </div>

  <?php if ($key === 'nav'): ?>
    <p class="text-muted small">Use <code>/#menu</code> for in-page anchors, or full paths like <code>/login</code>.</p>
    <?php $links = $c['links'] ?? []; ?>
    <div id="link-rows">
      <?php foreach ($links as $i => $link): ?>
        <div class="row g-2 mb-2 link-row">
          <div class="col-md-5"><input name="links_label[]" class="form-control" placeholder="Label" value="<?= Helpers::e($link['label'] ?? '') ?>"></div>
          <div class="col-md-5"><input name="links_href[]" class="form-control" placeholder="Href" value="<?= Helpers::e($link['href'] ?? '') ?>"></div>
          <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.link-row').remove()">Remove</button></div>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-sm btn-outline-dark mb-3" onclick="addLinkRow('links')">+ Add link</button>

  <?php elseif ($key === 'hero'): ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Headline line 1</label><input name="headline_line1" class="form-control" value="<?= Helpers::e($c['headline_line1'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Headline line 2</label><input name="headline_line2" class="form-control" value="<?= Helpers::e($c['headline_line2'] ?? '') ?>"></div>
      <div class="col-12"><label class="form-label">Subhead</label><textarea name="subhead" class="form-control" rows="2"><?= Helpers::e($c['subhead'] ?? '') ?></textarea></div>
      <div class="col-md-6"><label class="form-label">Default location label</label><input name="default_location" class="form-control" value="<?= Helpers::e($c['default_location'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Outlets status text</label><input name="outlets_text" class="form-control" value="<?= Helpers::e($c['outlets_text'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Delivery button title</label><input name="delivery_title" class="form-control" value="<?= Helpers::e($c['delivery_title'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Delivery subtitle</label><input name="delivery_subtitle" class="form-control" value="<?= Helpers::e($c['delivery_subtitle'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Takeaway title</label><input name="takeaway_title" class="form-control" value="<?= Helpers::e($c['takeaway_title'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Takeaway subtitle</label><input name="takeaway_subtitle" class="form-control" value="<?= Helpers::e($c['takeaway_subtitle'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Image alt text</label><input name="image_alt" class="form-control" value="<?= Helpers::e($c['image_alt'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Badge HTML (use &lt;br&gt; for lines)</label><input name="badge_html" class="form-control" value="<?= Helpers::e($c['badge_html'] ?? '') ?>"></div>
      <div class="col-md-6">
        <label class="form-label">Hero image</label>
        <input type="file" name="hero_image" class="form-control" accept="image/*">
        <div class="small text-muted mt-1">Current: <?= Helpers::e($c['image'] ?? '') ?></div>
        <img src="<?= Helpers::e(SiteSection::mediaUrl($c['image'] ?? null)) ?>" alt="" class="mt-2 rounded" style="max-height:140px">
      </div>
    </div>

  <?php elseif ($key === 'popular'): ?>
    <div class="alert alert-info small">Products shown here are those marked <strong>Featured</strong> in Products admin.</div>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Section title</label><input name="title" class="form-control" value="<?= Helpers::e($c['title'] ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">Max products</label><input type="number" name="limit" class="form-control" min="1" max="24" value="<?= (int)($c['limit'] ?? 8) ?>"></div>
      <div class="col-md-6"><label class="form-label">View all label</label><input name="view_all_label" class="form-control" value="<?= Helpers::e($c['view_all_label'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">View all link</label><input name="view_all_href" class="form-control" value="<?= Helpers::e($c['view_all_href'] ?? '#menu') ?>"></div>
    </div>

  <?php elseif ($key === 'about'): ?>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Eyebrow</label><input name="eyebrow" class="form-control" value="<?= Helpers::e($c['eyebrow'] ?? '') ?>"></div>
      <div class="col-md-8"><label class="form-label">Heading (optional)</label><input name="title" class="form-control" value="<?= Helpers::e($c['title'] ?? '') ?>"></div>
      <div class="col-12"><label class="form-label">Main Copy (Paragraph 1)</label><textarea name="copy" class="form-control" rows="3"><?= Helpers::e($c['copy'] ?? '') ?></textarea></div>
      <div class="col-12"><label class="form-label">Second Paragraph</label><textarea name="copy_extra" class="form-control" rows="3"><?= Helpers::e($c['copy_extra'] ?? '') ?></textarea></div>
      <div class="col-12"><label class="form-label">Additional Story &amp; Details</label><textarea name="story" class="form-control" rows="3" placeholder="Add more about us details here..."><?= Helpers::e($c['story'] ?? '') ?></textarea></div>
      <div class="col-md-6"><label class="form-label">Quote</label><input name="quote" class="form-control" value="<?= Helpers::e($c['quote'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Mission line</label><input name="mission" class="form-control" value="<?= Helpers::e($c['mission'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">CTA label</label><input name="cta_label" class="form-control" value="<?= Helpers::e($c['cta_label'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">CTA link</label><input name="cta_href" class="form-control" value="<?= Helpers::e($c['cta_href'] ?? '#menu') ?>"></div>
      <div class="col-md-6">
        <label class="form-label">About Section Image</label>
        <input type="file" name="about_image" class="form-control" accept="image/*">
        <div class="small text-muted mt-1">Current image: <?= Helpers::e($c['image'] ?? 'images/hero-dessert.png') ?></div>
        <img src="<?= Helpers::e(SiteSection::mediaUrl($c['image'] ?? 'images/hero-dessert.png')) ?>" alt="About Image Preview" class="mt-2 rounded" style="max-height:120px">
      </div>
    </div>

  <?php elseif ($key === 'trust_bar'): ?>
    <label class="form-label">Chips (one per line)</label>
    <textarea name="chips" class="form-control" rows="6"><?= Helpers::e(implode("\n", $c['chips'] ?? [])) ?></textarea>

  <?php elseif ($key === 'hygiene'): ?>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Eyebrow</label>
        <input name="eyebrow" class="form-control" value="<?= Helpers::e($c['eyebrow'] ?? 'FOOD SAFETY & HYGIENE') ?>">
      </div>
      <div class="col-md-8">
        <label class="form-label">Title</label>
        <input name="title" class="form-control" value="<?= Helpers::e($c['title'] ?? '⭐ 5-Star Food Hygiene Rated') ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="4"><?= Helpers::e($c['description'] ?? '') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Rating number</label>
        <input name="rating" class="form-control" value="<?= Helpers::e($c['rating'] ?? '5') ?>" placeholder="5">
      </div>
      <div class="col-md-6">
        <label class="form-label">Rating label</label>
        <input name="rating_label" class="form-control" value="<?= Helpers::e($c['rating_label'] ?? 'VERY GOOD') ?>" placeholder="VERY GOOD">
      </div>
      <div class="col-md-6">
        <label class="form-label">Badge Image (optional custom upload)</label>
        <input type="file" name="badge_image" class="form-control" accept="image/*">
        <div class="small text-muted mt-1">Current badge: <?= Helpers::e($c['badge_image'] ?? 'images/food-hygiene-rating-5.svg') ?></div>
        <img src="<?= Helpers::e(SiteSection::mediaUrl($c['badge_image'] ?? 'images/food-hygiene-rating-5.svg')) ?>" alt="Hygiene Badge Preview" class="mt-2 rounded" style="max-height:120px;background:#000;padding:6px">
      </div>
    </div>

  <?php elseif ($key === 'menu'): ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Title</label><input name="title" class="form-control" value="<?= Helpers::e($c['title'] ?? '') ?>"></div>
      <div class="col-12"><label class="form-label">Lead text</label><textarea name="lead" class="form-control" rows="2"><?= Helpers::e($c['lead'] ?? '') ?></textarea></div>
    </div>

  <?php elseif ($key === 'footer'): ?>
    <div class="row g-3 mb-4">
      <div class="col-12"><label class="form-label">Mission text</label><textarea name="mission" class="form-control" rows="2"><?= Helpers::e($c['mission'] ?? '') ?></textarea></div>
      <div class="col-12"><label class="form-label">Tagline</label><input name="tagline" class="form-control" value="<?= Helpers::e($c['tagline'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Newsletter title</label><input name="newsletter_title" class="form-control" value="<?= Helpers::e($c['newsletter_title'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Newsletter text</label><input name="newsletter_text" class="form-control" value="<?= Helpers::e($c['newsletter_text'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Copyright (after year)</label><input name="copyright" class="form-control" value="<?= Helpers::e($c['copyright'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Made in line</label><input name="made_in" class="form-control" value="<?= Helpers::e($c['made_in'] ?? '') ?>"></div>
    </div>

    <h2 class="h6">Quick links</h2>
    <div id="quick-rows" class="mb-3">
      <?php foreach (($c['quick_links'] ?? []) as $link): ?>
        <div class="row g-2 mb-2 link-row">
          <div class="col-md-5"><input name="quick_links_label[]" class="form-control" value="<?= Helpers::e($link['label'] ?? '') ?>"></div>
          <div class="col-md-5"><input name="quick_links_href[]" class="form-control" value="<?= Helpers::e($link['href'] ?? '') ?>"></div>
          <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.link-row').remove()">Remove</button></div>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-sm btn-outline-dark mb-4" onclick="addNamedLinkRow('quick-rows','quick_links')">+ Quick link</button>

    <h2 class="h6">Info links</h2>
    <div id="info-rows" class="mb-3">
      <?php foreach (($c['info_links'] ?? []) as $link): ?>
        <div class="row g-2 mb-2 link-row">
          <div class="col-md-5"><input name="info_links_label[]" class="form-control" value="<?= Helpers::e($link['label'] ?? '') ?>"></div>
          <div class="col-md-5"><input name="info_links_href[]" class="form-control" value="<?= Helpers::e($link['href'] ?? '') ?>"></div>
          <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.link-row').remove()">Remove</button></div>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-sm btn-outline-dark mb-4" onclick="addNamedLinkRow('info-rows','info_links')">+ Info link</button>

    <h2 class="h6">Social</h2>
    <div id="social-rows">
      <?php foreach (($c['social'] ?? []) as $s): ?>
        <div class="row g-2 mb-2 social-row">
          <div class="col-md-2"><input name="social_label[]" class="form-control" placeholder="IG" value="<?= Helpers::e($s['label'] ?? '') ?>"></div>
          <div class="col-md-4"><input name="social_url[]" class="form-control" placeholder="https://" value="<?= Helpers::e($s['url'] ?? '') ?>"></div>
          <div class="col-md-4"><input name="social_aria[]" class="form-control" placeholder="Aria label" value="<?= Helpers::e($s['aria'] ?? '') ?>"></div>
          <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.social-row').remove()">Remove</button></div>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-sm btn-outline-dark" onclick="addSocialRow()">+ Social</button>
  <?php endif; ?>

  <div class="mt-4 d-flex gap-2">
    <button class="btn btn-warning">Save section</button>
    <a class="btn btn-outline-secondary" href="<?= Helpers::baseUrl('admin/cms') ?>">Cancel</a>
  </div>
</form>

<script>
function addLinkRow(prefix) {
  const wrap = document.getElementById('link-rows');
  const div = document.createElement('div');
  div.className = 'row g-2 mb-2 link-row';
  div.innerHTML = `<div class="col-md-5"><input name="${prefix}_label[]" class="form-control" placeholder="Label"></div>
    <div class="col-md-5"><input name="${prefix}_href[]" class="form-control" placeholder="Href"></div>
    <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.link-row').remove()">Remove</button></div>`;
  wrap.appendChild(div);
}
function addNamedLinkRow(wrapId, prefix) {
  const wrap = document.getElementById(wrapId);
  const div = document.createElement('div');
  div.className = 'row g-2 mb-2 link-row';
  div.innerHTML = `<div class="col-md-5"><input name="${prefix}_label[]" class="form-control" placeholder="Label"></div>
    <div class="col-md-5"><input name="${prefix}_href[]" class="form-control" placeholder="Href"></div>
    <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.link-row').remove()">Remove</button></div>`;
  wrap.appendChild(div);
}
function addSocialRow() {
  const wrap = document.getElementById('social-rows');
  const div = document.createElement('div');
  div.className = 'row g-2 mb-2 social-row';
  div.innerHTML = `<div class="col-md-2"><input name="social_label[]" class="form-control" placeholder="IG"></div>
    <div class="col-md-4"><input name="social_url[]" class="form-control" placeholder="https://"></div>
    <div class="col-md-4"><input name="social_aria[]" class="form-control" placeholder="Aria"></div>
    <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.social-row').remove()">Remove</button></div>`;
  wrap.appendChild(div);
}
</script>
