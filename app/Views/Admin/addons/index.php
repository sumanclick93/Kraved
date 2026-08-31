<?php use App\Core\Helpers; ?>
<h1 class="h3 mb-4">Add-on Groups</h1>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6">New Group</h2>
        <form method="post" action="<?= Helpers::baseUrl('admin/addons/groups') ?>">
          <?= Helpers::csrfField() ?>
          <input name="title" class="form-control mb-2" placeholder="Title" required>
          <div class="row g-2 mb-2">
            <div class="col"><input type="number" name="min_selection" class="form-control" placeholder="Min" value="0"></div>
            <div class="col"><input type="number" name="max_selection" class="form-control" placeholder="Max" value="1"></div>
          </div>
          <div class="mb-2"><input type="number" name="display_order" class="form-control" placeholder="Order" value="0"></div>
          <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_required" id="req"><label for="req" class="form-check-label">Required</label></div>
          <button class="btn btn-warning">Create Group</button>
        </form>
      </div>
    </div>
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h2 class="h6">New Add-on</h2>
        <form method="post" action="<?= Helpers::baseUrl('admin/addons') ?>">
          <?= Helpers::csrfField() ?>
          <select name="group_id" class="form-select mb-2" required>
            <?php foreach ($groups as $g): ?>
              <option value="<?= (int)$g['id'] ?>"><?= Helpers::e($g['title']) ?></option>
            <?php endforeach; ?>
          </select>
          <input name="name" class="form-control mb-2" placeholder="Name" required>
          <input type="number" step="0.01" name="price" class="form-control mb-2" placeholder="Price" value="0">
          <input type="number" name="display_order" class="form-control mb-2" placeholder="Order" value="0">
          <button class="btn btn-outline-dark">Add Option</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <?php foreach ($groups as $g): ?>
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <form method="post" action="<?= Helpers::baseUrl('admin/addons/groups/' . $g['id'] . '/update') ?>" class="row g-2 align-items-end mb-3">
            <?= Helpers::csrfField() ?>
            <div class="col-md-4">
              <label class="form-label small mb-0">Group title</label>
              <input name="title" class="form-control form-control-sm" value="<?= Helpers::e($g['title']) ?>" required>
            </div>
            <div class="col-md-2">
              <label class="form-label small mb-0">Min</label>
              <input type="number" name="min_selection" class="form-control form-control-sm" value="<?= (int)$g['min_selection'] ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label small mb-0">Max</label>
              <input type="number" name="max_selection" class="form-control form-control-sm" value="<?= (int)$g['max_selection'] ?>">
            </div>
            <div class="col-md-1">
              <label class="form-label small mb-0">Order</label>
              <input type="number" name="display_order" class="form-control form-control-sm" value="<?= (int)$g['display_order'] ?>">
            </div>
            <div class="col-md-1">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="is_required" id="req-<?= (int)$g['id'] ?>" <?= (int)$g['is_required'] ? 'checked' : '' ?>>
                <label class="form-check-label small" for="req-<?= (int)$g['id'] ?>">Req</label>
              </div>
            </div>
            <div class="col-md-1">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="status" id="gst-<?= (int)$g['id'] ?>" <?= (int)$g['status'] ? 'checked' : '' ?>>
                <label class="form-check-label small" for="gst-<?= (int)$g['id'] ?>">On</label>
              </div>
            </div>
            <div class="col-md-1 text-end">
              <button class="btn btn-sm btn-warning">Save</button>
            </div>
          </form>
          <div class="text-end mb-2">
            <form method="post" action="<?= Helpers::baseUrl('admin/addons/groups/' . $g['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Delete group and its add-ons?')">
              <?= Helpers::csrfField() ?>
              <button class="btn btn-sm btn-outline-danger">Delete group</button>
            </form>
          </div>

          <h3 class="h6 text-muted">Options</h3>
          <?php if (empty($g['addons'])): ?>
            <p class="small text-muted mb-0">No add-ons in this group.</p>
          <?php else: ?>
            <?php foreach ($g['addons'] as $a): ?>
              <form method="post" action="<?= Helpers::baseUrl('admin/addons/' . $a['id'] . '/update') ?>" class="row g-2 align-items-end mb-2 border-bottom pb-2">
                <?= Helpers::csrfField() ?>
                <div class="col-md-3">
                  <input name="name" class="form-control form-control-sm" value="<?= Helpers::e($a['name']) ?>" required>
                </div>
                <div class="col-md-2">
                  <input type="number" step="0.01" name="price" class="form-control form-control-sm" value="<?= Helpers::e((string)$a['price']) ?>">
                </div>
                <div class="col-md-3">
                  <select name="group_id" class="form-select form-select-sm">
                    <?php foreach ($groups as $opt): ?>
                      <option value="<?= (int)$opt['id'] ?>" <?= (int)$opt['id'] === (int)$a['group_id'] ? 'selected' : '' ?>><?= Helpers::e($opt['title']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-1">
                  <input type="number" name="display_order" class="form-control form-control-sm" value="<?= (int)$a['display_order'] ?>" title="Order">
                </div>
                <div class="col-md-1">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="status" id="ast-<?= (int)$a['id'] ?>" <?= (int)$a['status'] ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="ast-<?= (int)$a['id'] ?>">On</label>
                  </div>
                </div>
                <div class="col-md-2 text-end">
                  <button class="btn btn-sm btn-outline-dark">Save</button>
                  <button formaction="<?= Helpers::baseUrl('admin/addons/' . $a['id'] . '/delete') ?>" formmethod="post" class="btn btn-sm btn-link text-danger p-0 ms-1" onclick="return confirm('Delete add-on?')">×</button>
                </div>
              </form>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
