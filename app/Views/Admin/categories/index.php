<?php use App\Core\Helpers; ?>
<div class="d-flex justify-content-between mb-4">
  <h1 class="h3">Categories</h1>
</div>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h2 class="h6">Add Category</h2>
        <form method="post" action="<?= Helpers::baseUrl('admin/categories') ?>">
          <?= Helpers::csrfField() ?>
          <div class="mb-2"><input name="name" class="form-control" placeholder="Name" required data-slug-source></div>
          <div class="mb-2"><input type="text" class="form-control bg-light" placeholder="Slug (auto)" readonly tabindex="-1" data-slug-preview aria-label="Auto slug"></div>
          <div class="mb-2"><input name="display_order" type="number" class="form-control" value="0" placeholder="Order"></div>
          <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="status" id="st" checked><label for="st" class="form-check-label">Active</label></div>
          <button class="btn btn-warning">Create</button>
        </form>
        <hr>
        <h2 class="h6">Add Sub-category</h2>
        <form method="post" action="<?= Helpers::baseUrl('admin/subcategories') ?>">
          <?= Helpers::csrfField() ?>
          <div class="mb-2">
            <select name="category_id" class="form-select" required>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= Helpers::e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2"><input name="name" class="form-control" placeholder="Sub-category name" required data-slug-source></div>
          <div class="mb-2"><input type="text" class="form-control bg-light" placeholder="Slug (auto)" readonly tabindex="-1" data-slug-preview aria-label="Auto slug"></div>
          <div class="mb-2"><input name="display_order" type="number" class="form-control" value="0" placeholder="Order"></div>
          <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="status" id="subst" checked><label for="subst" class="form-check-label">Active</label></div>
          <button class="btn btn-outline-dark">Add Sub</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <?php foreach ($categories as $c): ?>
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <form method="post" action="<?= Helpers::baseUrl('admin/categories/' . $c['id'] . '/update') ?>" class="row g-2 align-items-end">
            <?= Helpers::csrfField() ?>
            <div class="col-md-4">
              <label class="form-label small mb-0">Name</label>
              <input name="name" class="form-control form-control-sm" value="<?= Helpers::e($c['name']) ?>" required data-slug-source>
            </div>
            <div class="col-md-3">
              <label class="form-label small mb-0">Slug</label>
              <input type="text" class="form-control form-control-sm bg-light" value="<?= Helpers::e($c['slug']) ?>" readonly tabindex="-1" data-slug-preview aria-label="Auto slug">
            </div>
            <div class="col-md-2">
              <label class="form-label small mb-0">Order</label>
              <input type="number" name="display_order" class="form-control form-control-sm" value="<?= (int)$c['display_order'] ?>">
            </div>
            <div class="col-md-1">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="status" id="cat-st-<?= (int)$c['id'] ?>" <?= (int)$c['status'] ? 'checked' : '' ?>>
                <label class="form-check-label small" for="cat-st-<?= (int)$c['id'] ?>">On</label>
              </div>
            </div>
            <div class="col-md-2 text-end">
              <button class="btn btn-sm btn-warning">Save</button>
              <button formaction="<?= Helpers::baseUrl('admin/categories/' . $c['id'] . '/delete') ?>" formmethod="post" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete category?')">Del</button>
            </div>
          </form>

          <hr class="my-3">
          <h3 class="h6 text-muted">Sub-categories</h3>
          <?php if (empty($c['subs'])): ?>
            <p class="small text-muted mb-0">No sub-categories yet.</p>
          <?php else: ?>
            <?php foreach ($c['subs'] as $s): ?>
              <form method="post" action="<?= Helpers::baseUrl('admin/subcategories/' . $s['id'] . '/update') ?>" class="row g-2 align-items-end mb-2 border-bottom pb-2">
                <?= Helpers::csrfField() ?>
                <div class="col-md-3">
                  <input name="name" class="form-control form-control-sm" value="<?= Helpers::e($s['name']) ?>" required data-slug-source>
                </div>
                <div class="col-md-3">
                  <input type="text" class="form-control form-control-sm bg-light" value="<?= Helpers::e($s['slug']) ?>" readonly tabindex="-1" data-slug-preview aria-label="Auto slug">
                </div>
                <div class="col-md-2">
                  <select name="category_id" class="form-select form-select-sm">
                    <?php foreach ($categories as $opt): ?>
                      <option value="<?= (int)$opt['id'] ?>" <?= (int)$opt['id'] === (int)$s['category_id'] ? 'selected' : '' ?>><?= Helpers::e($opt['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-1">
                  <input type="number" name="display_order" class="form-control form-control-sm" value="<?= (int)$s['display_order'] ?>" title="Order">
                </div>
                <div class="col-md-1">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="status" id="sub-st-<?= (int)$s['id'] ?>" <?= (int)$s['status'] ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="sub-st-<?= (int)$s['id'] ?>">On</label>
                  </div>
                </div>
                <div class="col-md-2 text-end">
                  <button class="btn btn-sm btn-outline-dark">Save</button>
                  <button formaction="<?= Helpers::baseUrl('admin/subcategories/' . $s['id'] . '/delete') ?>" formmethod="post" class="btn btn-sm btn-link text-danger p-0 ms-1" onclick="return confirm('Delete sub-category?')">×</button>
                </div>
              </form>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
