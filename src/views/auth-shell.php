<?php
/**
 * Split-screen shell for sign-in and registration.
 * @var string $photo  @var string $photoAlt  @var string $photoCaption  @var string $formView
 */
?>
<div class="auth">
  <aside class="auth-photo">
    <img src="<?= e(asset('img/' . $photo)) ?>" alt="<?= e($photoAlt) ?>" width="1100" height="825">
    <div class="auth-photo-shade" aria-hidden="true"></div>
    <a class="brand brand-light auth-brand" href="<?= e(url()) ?>">
      <img src="<?= e(asset('img/seal.png')) ?>" alt="" width="40" height="43">
      <span>UniUyo Dues</span>
    </a>
    <p class="auth-caption"><?= e($photoCaption) ?></p>
  </aside>

  <section class="auth-panel">
    <a class="brand auth-brand-mobile" href="<?= e(url()) ?>">
      <img src="<?= e(asset('img/seal.png')) ?>" alt="" width="36" height="38">
      <span>UniUyo Dues</span>
    </a>
    <div class="auth-form">
      <?php require __DIR__ . '/partials/flashes.php'; ?>
      <?php require __DIR__ . '/' . $formView . '.php'; ?>
    </div>
  </section>
</div>
