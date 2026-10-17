<?php /** @var string $title @var int $status @var string $message */ ?>
<div class="wrap page narrow error-page">
  <p class="error-code"><?= (int) $status ?></p>
  <h1 class="page-title"><?= e($title) ?></h1>
  <?php if ($message): ?><p class="muted"><?= e($message) ?></p><?php endif; ?>
  <p><a class="btn btn-primary" href="<?= e(url(current_user() ? home_for(current_user()) : '')) ?>">Go to the start page</a></p>
</div>
