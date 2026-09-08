<?php
use App\Core\Helpers;
?>

<div class="menu-page-wrapper py-4 py-md-5">
  <div class="container">
    <div class="menu-layout">
      <!-- Left Sidebar: Category List -->
      <aside class="menu-sidebar">
        <div class="menu-sidebar-card">
          <ul class="menu-sidebar-nav" role="tablist">
            <li class="menu-sidebar-item">
              <button 
                type="button" 
                class="menu-sidebar-btn active" 
                data-cat-filter="all"
                aria-selected="true"
              >
                All Categories
              </button>
            </li>
            <?php foreach ($categories as $cat): ?>
              <li class="menu-sidebar-item">
                <button 
                  type="button" 
                  class="menu-sidebar-btn" 
                  data-cat-filter="<?= Helpers::e($cat['slug']) ?>"
                  aria-selected="false"
                >
                  <?= Helpers::e($cat['name']) ?>
                </button>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </aside>

      <!-- Right Main Content: Category Sections & Products -->
      <main class="menu-content" id="menu-content-area">
        <?php foreach ($menu as $block):
          $cat = $block['category'];
          $products = $block['products'];
          if (!$products) continue;
        ?>
          <section 
            class="menu-category-section" 
            id="cat-section-<?= Helpers::e($cat['slug']) ?>"
            data-cat-slug="<?= Helpers::e($cat['slug']) ?>"
          >
            <h2 class="menu-category-header"><?= Helpers::e(strtoupper($cat['name'])) ?></h2>
            
            <?php if (!empty($cat['sub_categories'])): ?>
              <div class="subcat-pills mb-3">
                <?php foreach ($cat['sub_categories'] as $sub): ?>
                  <a href="<?= Helpers::baseUrl('subcategory/' . $sub['slug']) ?>" class="subcat-pill"><?= Helpers::e($sub['name']) ?></a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="menu-products-grid">
              <?php foreach ($products as $p): ?>
                <article class="menu-item-card">
                  <div class="menu-item-body">
                    <h3 class="menu-item-title"><?= Helpers::e($p['title']) ?></h3>
                    <?php if (!empty($p['short_description'])): ?>
                      <p class="menu-item-desc"><?= Helpers::e($p['short_description']) ?></p>
                    <?php endif; ?>
                    <div class="menu-item-footer">
                      <span class="menu-item-price"><?= Helpers::money($p['sale_price'] ?? $p['base_price']) ?></span>
                      <button 
                        type="button" 
                        class="btn-add-round btn-add" 
                        data-product-id="<?= (int)$p['id'] ?>" 
                        aria-label="Add <?= Helpers::e($p['title']) ?>"
                        title="Add <?= Helpers::e($p['title']) ?>"
                      >+</button>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endforeach; ?>

        <div id="menu-no-results" class="text-center py-5 text-muted" style="display: none;">
          <p class="fs-5">No products found in this category.</p>
        </div>
      </main>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const buttons = document.querySelectorAll('.menu-sidebar-btn');
  const sections = document.querySelectorAll('.menu-category-section');
  const noResults = document.getElementById('menu-no-results');

  function filterCategory(targetSlug, updateHash = true) {
    let count = 0;
    
    buttons.forEach(btn => {
      const isMatch = btn.getAttribute('data-cat-filter') === targetSlug;
      btn.classList.toggle('active', isMatch);
      btn.setAttribute('aria-selected', isMatch ? 'true' : 'false');
      if (isMatch && window.innerWidth < 992) {
        btn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
      }
    });

    sections.forEach(sec => {
      const secSlug = sec.getAttribute('data-cat-slug');
      if (targetSlug === 'all' || secSlug === targetSlug) {
        sec.style.display = 'block';
        count++;
      } else {
        sec.style.display = 'none';
      }
    });

    if (noResults) {
      noResults.style.display = count === 0 ? 'block' : 'none';
    }

    if (updateHash) {
      if (targetSlug === 'all') {
        history.replaceState(null, '', window.location.pathname);
      } else {
        history.replaceState(null, '', '#' + targetSlug);
      }
    }
  }

  buttons.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const slug = this.getAttribute('data-cat-filter');
      filterCategory(slug);
    });
  });

  // Check initial hash or URL query param
  const hash = window.location.hash.replace('#', '');
  const urlParams = new URLSearchParams(window.location.search);
  const catParam = urlParams.get('category');
  const initialCategory = hash || catParam;

  if (initialCategory) {
    const matchedBtn = document.querySelector(`.menu-sidebar-btn[data-cat-filter="${initialCategory}"]`);
    if (matchedBtn) {
      filterCategory(initialCategory, false);
    }
  }
});
</script>
