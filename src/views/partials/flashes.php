<?php $flashes = take_flashes(); if ($flashes): ?>
  <div class="flashes" role="status">
    <?php foreach ($flashes as $f): ?>
      <p class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
