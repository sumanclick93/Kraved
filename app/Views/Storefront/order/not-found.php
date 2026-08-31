<?php use App\Core\Helpers; ?>
<section class="section pt-5">
  <div class="container" style="max-width:520px">
    <p class="text-uppercase small letter-spaced opacity-75 mb-1">Orders</p>
    <h1 class="section-title">Order not found</h1>
    <p class="section-lead">That ticket isn’t in your account. Sign in and open <a href="<?= Helpers::baseUrl('orders') ?>">My orders</a> to find it.</p>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-accent" href="<?= Helpers::baseUrl('orders') ?>">My orders</a>
      <a class="btn btn-ghost" href="<?= Helpers::baseUrl() ?>">Back to menu</a>
    </div>
  </div>
</section>
