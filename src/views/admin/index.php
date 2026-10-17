<?php /** @var array|null $dues @var array $stats @var array $recent @var int $attention @var int $pendingCount */ ?>
<div class="wrap page">
  <header class="page-head">
    <h1 class="page-title"><?= $dues ? e($dues['title']) . ', ' . e($dues['session_label']) : 'Overview' ?></h1>
    <p class="muted"><?= $dues ? e(naira((int) $dues['amount_kobo'])) . ' per student' . ($dues['due_date'] ? ', due ' . e(date('j F Y', strtotime($dues['due_date']))) : '') : 'No dues are open. Open one from the Dues page.' ?></p>
  </header>

  <?php if ($attention): ?>
    <p class="notice notice-warn">
      <?= $attention ?> payment<?= $attention === 1 ? ' needs' : 's need' ?> a manual check (duplicate or amount mismatch).
      <a href="<?= e(url('admin/payments.php?flag=1')) ?>">Review them</a>
    </p>
  <?php endif; ?>

  <?php if ($pendingCount): ?>
    <form method="post" class="notice notice-row">
      <?= csrf_field() ?>
      <span><?= $pendingCount ?> payment<?= $pendingCount === 1 ? ' is' : 's are' ?> waiting for confirmation from Paystack.</span>
      <button class="btn btn-quiet" type="submit">Check with Paystack now</button>
    </form>
  <?php endif; ?>

  <?php if ($dues): ?>
    <section class="progress-block" aria-label="Collection progress">
      <div class="progress-figures">
        <p class="big-figure"><?= e(naira($stats['collected'])) ?></p>
        <p class="muted">collected from <?= number_format($stats['paid']) ?> of <?= number_format($stats['students']) ?> registered students</p>
      </div>
      <progress class="progress" value="<?= $stats['percent'] ?>" max="100" aria-label="<?= $stats['percent'] ?>% of students have paid"><?= $stats['percent'] ?>%</progress>
      <dl class="progress-legend">
        <div><dt>Paid</dt><dd><?= number_format($stats['paid']) ?></dd></div>
        <div><dt>Not yet paid</dt><dd><a href="<?= e(url('admin/students.php?status=unpaid')) ?>"><?= number_format($stats['unpaid']) ?></a></dd></div>
        <div><dt>Paid by SMS</dt><dd><?= number_format((int) ($stats['by_channel']['sms'] ?? 0)) ?></dd></div>
        <div><dt>Paid online</dt><dd><?= number_format((int) ($stats['by_channel']['checkout'] ?? 0) + (int) ($stats['by_channel']['saved_card'] ?? 0)) ?></dd></div>
      </dl>
    </section>
  <?php endif; ?>

  <section>
    <div class="section-head">
      <h2 class="section-title">Latest activity</h2>
      <a href="<?= e(url('admin/payments.php')) ?>">All payments</a>
    </div>
    <?php if (!$recent): ?>
      <p class="muted">No payments yet.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Student</th><th>Reg. number</th><th>Amount</th><th>Method</th><th>Status</th><th>When</th></tr></thead>
          <tbody>
          <?php foreach ($recent as $p): ?>
            <tr>
              <td><?= e($p['first_name'] . ' ' . $p['last_name']) ?></td>
              <td><?= e($p['reg_number']) ?></td>
              <td><?= e(naira((int) $p['amount_kobo'])) ?></td>
              <td><?= e(['checkout' => 'Card', 'saved_card' => 'Saved card', 'sms' => 'SMS'][$p['channel']]) ?></td>
              <td><span class="badge badge-<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span></td>
              <td><?= e(date('j M, g:i a', strtotime($p['paid_at'] ?: $p['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
