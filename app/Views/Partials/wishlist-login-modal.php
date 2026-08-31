<?php use App\Core\Helpers; ?>
<div id="wishlist-login-prompt" class="kraved-prompt" hidden>
  <div class="kraved-prompt-card" role="alertdialog" aria-modal="true" aria-labelledby="wishlist-login-msg">
    <p id="wishlist-login-msg">To add product in wishlist you have to login</p>
    <div class="kraved-prompt-actions">
      <button type="button" class="btn btn-ghost" id="wishlist-login-cancel">Cancel</button>
      <a class="btn btn-accent" id="wishlist-login-proceed" href="<?= Helpers::baseUrl('login?next=wishlist') ?>">Proceed</a>
    </div>
  </div>
</div>
