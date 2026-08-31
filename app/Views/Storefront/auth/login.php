<?php use App\Core\Helpers; ?>
<section class="section pt-5">
  <div class="container" style="max-width:420px">
    <div class="text-center mb-3">
      <img src="<?= Helpers::logo() ?>" alt="Kraved" width="72" height="72" style="border-radius:50%;background:#2c1810">
    </div>
    <h1 class="section-title">Login</h1>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= Helpers::e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= Helpers::formAction('login') ?>">
      <?= Helpers::csrfField() ?>
      <div class="mb-3">
        <label class="form-label" for="login-email">Email</label>
        <input id="login-email" type="email" name="email" class="form-control" required maxlength="120" autocomplete="email" value="<?= Helpers::e($oldEmail ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="login-password">Password</label>
        <input id="login-password" type="password" name="password" class="form-control" required autocomplete="current-password">
      </div>
      <button class="btn btn-accent w-100">Sign in</button>
    </form>
    <p class="mt-3 small">No account? <a href="<?= Helpers::baseUrl('register') ?>">Register</a></p>
    <p class="mt-2 small opacity-75">After you sign in you can open your profile, orders, invoices, and wishlist.</p>
  </div>
</section>
