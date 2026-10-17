<?php /** @var int $page @var int $pages */ if ($pages > 1): ?>
  <nav class="pagination" aria-label="Pages">
    <?php if ($page > 1): ?><a href="<?= e(query_url(['page' => $page - 1])) ?>">Previous</a><?php endif; ?>
    <span>Page <?= $page ?> of <?= $pages ?></span>
    <?php if ($page < $pages): ?><a href="<?= e(query_url(['page' => $page + 1])) ?>">Next</a><?php endif; ?>
  </nav>
<?php endif; ?>
