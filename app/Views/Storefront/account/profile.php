<?php
use App\Core\Helpers;
?>
<section class="section account-section">
  <div class="container account-page">
    <header class="account-hero">
      <p class="account-kicker">Account</p>
      <h1 class="section-title">Profile</h1>
      <p class="section-lead">Keep your details ready for the next cookie run.</p>
    </header>

    <div class="account-layout">
      <?php require dirname(__DIR__, 2) . '/Partials/account-nav.php'; ?>
      <div>
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?= Helpers::e($error) ?></div><?php endif; ?>
        <?php if (!empty($success)): ?><div class="alert alert-success"><?= Helpers::e($success) ?></div><?php endif; ?>

        <form method="post" action="<?= Helpers::baseUrl('account') ?>" class="checkout-form">
          <?= Helpers::csrfField() ?>
          <h2 class="h5">Personal details</h2>
          <div class="mb-3">
            <label class="form-label" for="name">Name</label>
            <input id="name" name="name" class="form-control" required minlength="2" maxlength="80" autocomplete="name" value="<?= Helpers::e($profile['name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input id="email" type="email" name="email" class="form-control" required maxlength="120" autocomplete="email" value="<?= Helpers::e($profile['email'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label" for="phone">Phone</label>
            <input id="phone" name="phone" class="form-control" maxlength="20" autocomplete="tel" value="<?= Helpers::e($profile['phone'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label" for="address">Address</label>
            <input id="address" name="address" class="form-control" value="<?= Helpers::e($profile['address'] ?? '') ?>">
          </div>
          <div class="mb-4">
            <label class="form-label" for="postcode">Postcode</label>
            <input id="postcode" name="postcode" class="form-control text-uppercase" maxlength="8" autocomplete="postal-code" placeholder="e.g. BB11 1AA" value="<?= Helpers::e($profile['postcode'] ?? '') ?>">
          </div>

          <h2 class="h5">Change password</h2>
          <p class="small text-muted">Leave blank to keep your current password.</p>
          <div class="mb-3">
            <label class="form-label" for="current_password">Current password</label>
            <input id="current_password" type="password" name="current_password" class="form-control" autocomplete="current-password">
          </div>
          <div class="mb-4">
            <label class="form-label" for="new_password">New password</label>
            <input id="new_password" type="password" name="new_password" class="form-control" minlength="6" autocomplete="new-password">
          </div>
          <button class="btn btn-accent" type="submit">Save profile</button>
        </form>
      </div>
    </div>
  </div>
</section>
