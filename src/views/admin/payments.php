<?php /** @var array $allDues @var int $duesId @var string $status @var string $search @var bool $flagged @var array $payments @var int $total @var int $page @var int $pages */ ?>
<div class="wrap page">
  <header class="page-head page-head-row">
    <div>
      <h1 class="page-title">Payments</h1>
      <p class="muted"><?= number_format($total) ?> matching payment<?= $total === 1 ? '' : 's' ?><?= $flagged ? ' needing a manual check' : '' ?></p>
    </div>
    <a class="btn btn-quiet" href="<?= e(query_url(['export' => 'csv', 'page' => null])) ?>">Export CSV</a>
  </header>

  <form class="filters" method="get">
    <div class="field">
      <label for="f-q">Search</label>
      <input id="f-q" name="q" type="search" value="<?= e($search) ?>" placeholder="Name, reg. number, phone or reference">
    </div>
    <div class="field">
      <label for="f-dues">Dues</label>
      <select id="f-dues" name="dues">
        <option value="0">All dues</option>
        <?php foreach ($allDues as $d): ?>
          <option value="<?= (int) $d['id'] ?>" <?= $duesId === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['title'] . ' ' . $d['session_label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="f-status">Status</label>
      <select id="f-status" name="status">
        <option value="">Any status</option>
        <?php foreach (['success', 'pending', 'failed', 'abandoned'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Filter</button>
  </form>

  <?php if (!$payments): ?>
    <div class="empty"><h2>No payments match</h2><p>Clear the search or pick a different status.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Reference</th><th>Student</th><th>Reg. number</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr>
        </thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
          <tr>
            <td class="mono">
              <?php if ($p['status'] === 'success'): ?>
                <a href="<?= e(url('receipt.php?ref=' . urlencode($p['reference']))) ?>"><?= e($p['reference']) ?></a>
              <?php else: ?><?= e($p['reference']) ?><?php endif; ?>
              <?php if (preg_match('/^(DUPLICATE|AMOUNT MISMATCH)/', (string) $p['gateway_response'])): ?>
                <span class="flag"><?= e($p['gateway_response']) ?></span>
              <?php endif; ?>
            </td>
            <td><?= e($p['first_name'] . ' ' . $p['last_name']) ?><span class="sub"><?= e(format_phone($p['phone'])) ?></span></td>
            <td><?= e($p['reg_number']) ?></td>
            <td><?= e(naira((int) $p['amount_kobo'])) ?></td>
            <td><?= e(['checkout' => 'Card', 'saved_card' => 'Saved card', 'sms' => 'SMS'][$p['channel']]) ?></td>
            <td>
              <span class="badge badge-<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span>
              <?php if ($p['status'] === 'failed' && $p['gateway_response']): ?><span class="sub"><?= e($p['gateway_response']) ?></span><?php endif; ?>
            </td>
            <td><?= e(date('j M Y, g:i a', strtotime($p['paid_at'] ?: $p['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php require __DIR__ . '/../partials/pagination.php'; ?>
  <?php endif; ?>
</div>
