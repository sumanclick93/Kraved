<?php use App\Core\Helpers; ?>
<h1 class="h3 mb-2">Homepage CMS</h1>
<p class="text-muted mb-4">Edit each storefront section. Products, categories, add-ons and delivery zones stay in their own admin pages.</p>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= Helpers::e($error) ?></div>
<?php endif; ?>

<?php if (!empty($tableMissing)): ?>
  <div class="alert alert-warning">
    <strong>CMS tables not found.</strong>
    Run the migration once:
    <a href="<?= Helpers::baseUrl('migrate-cms.php?run=1') ?>" class="alert-link" target="_blank">migrate-cms.php?run=1</a>
    then refresh this page. Delete that file after it succeeds.
  </div>
<?php endif; ?>

<div class="table-responsive bg-white rounded shadow-sm">
  <table class="table mb-0 align-middle">
    <thead>
      <tr>
        <th>Section</th>
        <th>Key</th>
        <th>Visible</th>
        <th>Updated</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($sections as $s): ?>
      <tr>
        <td class="fw-semibold"><?= Helpers::e($s['label']) ?></td>
        <td><code><?= Helpers::e($s['section_key']) ?></code></td>
        <td>
          <form method="post" action="<?= Helpers::baseUrl('admin/cms/' . rawurlencode($s['section_key']) . '/toggle') ?>" class="d-inline">
            <?= Helpers::csrfField() ?>
            <button class="btn btn-sm <?= (int)$s['is_visible'] ? 'btn-success' : 'btn-outline-secondary' ?>">
              <?= (int)$s['is_visible'] ? 'On' : 'Off' ?>
            </button>
          </form>
        </td>
        <td class="small text-muted"><?= Helpers::e((string)($s['updated_at'] ?? '—')) ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-warning" href="<?= Helpers::baseUrl('admin/cms/' . rawurlencode($s['section_key']) . '/edit') ?>">Edit</a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$sections): ?>
      <tr><td colspan="5" class="text-muted p-4">No sections yet.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="mt-4">
  <a class="btn btn-outline-dark" href="<?= Helpers::baseUrl('admin/settings') ?>">Store Settings</a>
  <a class="btn btn-outline-secondary" href="<?= Helpers::baseUrl('admin/products') ?>">Featured products (Popular)</a>
</div>
