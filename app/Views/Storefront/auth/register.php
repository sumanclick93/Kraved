<?php use App\Core\Helpers; ?>
<section class="section pt-5">
  <div class="container" style="max-width:420px">
    <div class="text-center mb-3">
      <img src="<?= Helpers::logo() ?>" alt="Kraved" width="72" height="72" style="border-radius:50%;background:#2c1810">
    </div>
    <h1 class="section-title">Create Account</h1>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= Helpers::e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= Helpers::formAction('register') ?>">
      <?= Helpers::csrfField() ?>
      <div class="mb-3">
        <label class="form-label" for="reg-name">Name</label>
        <input id="reg-name" name="name" class="form-control" required minlength="2" maxlength="80" autocomplete="name" value="<?= Helpers::e($old['name'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="reg-email">Email</label>
        <input id="reg-email" type="email" name="email" class="form-control" required maxlength="120" autocomplete="email" value="<?= Helpers::e($old['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="reg-phone">Phone</label>
        <input id="reg-phone" name="phone" class="form-control" required minlength="10" maxlength="20" autocomplete="tel" value="<?= Helpers::e($old['phone'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="reg-password">Password</label>
        <input id="reg-password" type="password" name="password" class="form-control" required minlength="6" maxlength="72" autocomplete="new-password">
      </div>
      <button class="btn btn-accent w-100">Register</button>
    </form>
    <p class="mt-3 small">Already registered? <a href="<?= Helpers::baseUrl('login') ?>">Sign in</a> to see your order history.</p>
  </div>
</section>
