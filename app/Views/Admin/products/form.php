<?php use App\Core\Helpers; $p = $product; $images = $images ?? []; $variants = $variants ?? []; ?>
<h1 class="h3 mb-4"><?= Helpers::e($title) ?></h1>
<form method="post" enctype="multipart/form-data"
  action="<?= $p ? Helpers::baseUrl('admin/products/' . $p['id'] . '/update') : Helpers::baseUrl('admin/products') ?>"
  class="card border-0 shadow-sm">
  <div class="card-body row g-3">
    <?= Helpers::csrfField() ?>
    <div class="col-md-6">
      <label class="form-label">Title</label>
      <input name="title" class="form-control" required value="<?= Helpers::e($p['title'] ?? '') ?>" data-slug-source>
    </div>
    <div class="col-md-6">
      <label class="form-label">Slug <span class="text-muted fw-normal">(auto)</span></label>
      <input type="text" class="form-control bg-light" value="<?= Helpers::e($p['slug'] ?? '') ?>" readonly tabindex="-1" data-slug-preview aria-label="Auto slug">
    </div>
    <div class="col-md-4">
      <label class="form-label">Category</label>
      <select name="category_id" id="category_id" class="form-select" required>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (($p['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>><?= Helpers::e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Sub-category</label>
      <select name="sub_category_id" id="sub_category_id" class="form-select">
        <option value="">— None —</option>
        <?php foreach ($subs as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= (($p['sub_category_id'] ?? '') == $s['id']) ? 'selected' : '' ?>><?= Helpers::e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Base Price (£)</label>
      <input type="number" step="0.01" name="base_price" class="form-control" required value="<?= Helpers::e((string)($p['base_price'] ?? '0')) ?>">
      <div class="form-text">Used when no variants are set. With variants, customers pick a size price.</div>
    </div>
    <div class="col-md-3">
      <label class="form-label">Stock</label>
      <input type="number" name="stock_qty" class="form-control" value="<?= (int)($p['stock_qty'] ?? 0) ?>">
      <div class="form-text">Ignored when variants manage stock.</div>
    </div>
    <div class="col-md-3">
      <label class="form-label">Weight / Pack badge</label>
      <input name="weight_label" class="form-control" value="<?= Helpers::e($p['weight_label'] ?? '') ?>" placeholder="e.g. From 100g">
    </div>
    <div class="col-md-3">
      <label class="form-label">Display order</label>
      <input type="number" name="display_order" class="form-control" value="<?= (int)($p['display_order'] ?? 0) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Short description</label>
      <input name="short_description" class="form-control" value="<?= Helpers::e($p['short_description'] ?? '') ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Full description</label>
      <textarea name="full_description" class="form-control" rows="3"><?= Helpers::e($p['full_description'] ?? '') ?></textarea>
    </div>

    <div class="col-12">
      <hr class="my-2">
      <h2 class="h6 mb-2">Images</h2>
      <label class="form-label">Upload images</label>
      <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
      <div class="form-text">You can select multiple images. Mark one as primary for the product card.</div>
      <?php if ($images): ?>
        <div class="d-flex flex-wrap gap-3 mt-3">
          <?php foreach ($images as $img): ?>
            <div class="border rounded p-2 text-center" style="width:120px">
              <img src="<?= Helpers::upload($img['image_path']) ?>" alt="" class="rounded mb-2" style="height:72px;width:100%;object-fit:cover">
              <div class="form-check mb-1">
                <input class="form-check-input" type="radio" name="primary_image" value="<?= (int)$img['id'] ?>" id="prim<?= (int)$img['id'] ?>"
                  <?= (int)$img['is_primary'] ? 'checked' : '' ?>>
                <label class="form-check-label small" for="prim<?= (int)$img['id'] ?>">Primary</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remove_images[]" value="<?= (int)$img['id'] ?>" id="rm<?= (int)$img['id'] ?>">
                <label class="form-check-label small text-danger" for="rm<?= (int)$img['id'] ?>">Remove</label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif (!empty($p['image'])): ?>
        <img src="<?= Helpers::upload($p['image']) ?>" alt="" class="mt-2 rounded" style="height:48px">
      <?php endif; ?>
    </div>

    <div class="col-12">
      <hr class="my-2">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h2 class="h6 mb-0">Variants <span class="text-muted fw-normal">(size / weight)</span></h2>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="add-variant-row">+ Add variant</button>
      </div>
      <p class="small text-muted mb-2">e.g. 100g, 250g, 1 pound — each with its own price and stock.</p>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0" id="variants-table">
          <thead>
            <tr>
              <th style="min-width:140px">Label</th>
              <th style="min-width:100px">Price (£)</th>
              <th style="min-width:90px">Stock</th>
              <th style="min-width:110px">SKU</th>
              <th style="min-width:100px">Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="variants-body">
            <?php if ($variants): ?>
              <?php foreach ($variants as $v): ?>
                <tr class="variant-row">
                  <td>
                    <input type="hidden" name="variant_id[]" value="<?= (int)$v['id'] ?>">
                    <input name="variant_label[]" class="form-control form-control-sm" value="<?= Helpers::e($v['label']) ?>" placeholder="100g">
                  </td>
                  <td><input type="number" step="0.01" name="variant_price[]" class="form-control form-control-sm" value="<?= Helpers::e((string)$v['price']) ?>"></td>
                  <td><input type="number" name="variant_stock[]" class="form-control form-control-sm" value="<?= (int)$v['stock_qty'] ?>"></td>
                  <td><input name="variant_sku[]" class="form-control form-control-sm" value="<?= Helpers::e($v['sku'] ?? '') ?>"></td>
                  <td>
                    <select name="variant_status[]" class="form-select form-select-sm">
                      <option value="1" <?= (int)$v['status'] ? 'selected' : '' ?>>Active</option>
                      <option value="0" <?= !(int)$v['status'] ? 'selected' : '' ?>>Hidden</option>
                    </select>
                  </td>
                  <td><button type="button" class="btn btn-sm btn-link text-danger remove-variant-row">Remove</button></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <template id="variant-row-template">
        <tr class="variant-row">
          <td>
            <input type="hidden" name="variant_id[]" value="">
            <input name="variant_label[]" class="form-control form-control-sm" placeholder="e.g. 250g">
          </td>
          <td><input type="number" step="0.01" name="variant_price[]" class="form-control form-control-sm" value="0"></td>
          <td><input type="number" name="variant_stock[]" class="form-control form-control-sm" value="0"></td>
          <td><input name="variant_sku[]" class="form-control form-control-sm" placeholder="Optional"></td>
          <td>
            <select name="variant_status[]" class="form-select form-select-sm">
              <option value="1" selected>Active</option>
              <option value="0">Hidden</option>
            </select>
          </td>
          <td><button type="button" class="btn btn-sm btn-link text-danger remove-variant-row">Remove</button></td>
        </tr>
      </template>
    </div>

    <div class="col-12 d-flex flex-wrap gap-4">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="status" id="status" <?= !isset($p) || (int)($p['status'] ?? 1) ? 'checked' : '' ?>><label for="status" class="form-check-label">Active</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="is_featured" id="feat" <?= (int)($p['is_featured'] ?? 0) ? 'checked' : '' ?>><label for="feat" class="form-check-label">Featured</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="is_box_deal" id="box" <?= (int)($p['is_box_deal'] ?? 0) ? 'checked' : '' ?>><label for="box" class="form-check-label">Box Deal</label></div>
    </div>
    <div class="col-md-4" id="box-max-wrap" style="<?= (int)($p['is_box_deal'] ?? 0) ? '' : 'display:none' ?>">
      <label class="form-label">Box max items</label>
      <input type="number" name="box_max_items" class="form-control" value="<?= (int)($p['box_max_items'] ?? 4) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Add-on groups</label>
      <div class="row">
        <?php foreach ($addonGroups as $g): ?>
          <div class="col-md-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="addon_groups[]" value="<?= (int)$g['id'] ?>" id="ag<?= (int)$g['id'] ?>"
                <?= in_array((int)$g['id'], $selectedGroups, true) ? 'checked' : '' ?>>
              <label class="form-check-label" for="ag<?= (int)$g['id'] ?>"><?= Helpers::e($g['title']) ?></label>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="col-12" id="box-choices-wrap" style="<?= (int)($p['is_box_deal'] ?? 0) ? '' : 'display:none' ?>">
      <label class="form-label">Eligible cookies for this box</label>
      <div class="row">
        <?php foreach ($allProducts as $ap): ?>
          <?php if ((int)$ap['is_box_deal']) continue; ?>
          <div class="col-md-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="box_choices[]" value="<?= (int)$ap['id'] ?>" id="bc<?= (int)$ap['id'] ?>"
                <?= in_array((int)$ap['id'], $selectedChoices, true) ? 'checked' : '' ?>>
              <label class="form-check-label" for="bc<?= (int)$ap['id'] ?>"><?= Helpers::e($ap['title']) ?></label>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="col-12">
      <button class="btn btn-warning">Save Product</button>
      <a href="<?= Helpers::baseUrl('admin/products') ?>" class="btn btn-link">Cancel</a>
    </div>
  </div>
</form>
<script>
document.getElementById('box')?.addEventListener('change', function() {
  document.getElementById('box-max-wrap').style.display = this.checked ? '' : 'none';
  document.getElementById('box-choices-wrap').style.display = this.checked ? '' : 'none';
});
document.getElementById('category_id')?.addEventListener('change', async function() {
  const res = await fetch(KRAVED.baseUrl + '/admin/api/categories/' + this.value + '/subs');
  const data = await res.json();
  const sel = document.getElementById('sub_category_id');
  sel.innerHTML = '<option value="">— None —</option>';
  data.forEach(s => {
    const o = document.createElement('option');
    o.value = s.id; o.textContent = s.name; sel.appendChild(o);
  });
});

(function() {
  const body = document.getElementById('variants-body');
  const tpl = document.getElementById('variant-row-template');
  document.getElementById('add-variant-row')?.addEventListener('click', () => {
    if (!body || !tpl) return;
    body.appendChild(tpl.content.cloneNode(true));
  });
  body?.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-variant-row')) {
      e.target.closest('tr')?.remove();
    }
  });
})();
</script>
