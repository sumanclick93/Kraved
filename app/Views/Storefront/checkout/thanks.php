<?php use App\Core\Helpers; ?>
<section class="section pt-5">
  <div class="container" style="max-width:520px">
    <p class="text-uppercase small letter-spaced opacity-75 mb-1">Checkout</p>
    <h1 class="section-title">Order received</h1>
    <?php if ($orderNumber !== ''): ?>
      <p class="section-lead">We’ve got ticket <strong><?= Helpers::e($orderNumber) ?></strong>. Sign in with the same email to open order details, invoices, and your wishlist.</p>
    <?php else: ?>
      <p class="section-lead">Your bake is with us. Sign in to follow it from the oven to the door.</p>
    <?php endif; ?>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-accent" href="<?= Helpers::baseUrl('login?next=orders') ?>">Sign in to view order</a>
      <a class="btn btn-ghost" href="<?= Helpers::baseUrl() ?>">Back to menu</a>
    </div>
  </div>
</section>
