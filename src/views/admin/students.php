<?php /** @var array|null $dues @var string $status @var string $search @var array $students @var int $total @var int $page @var int $pages */ ?>
<div class="wrap page">
  <header class="page-head">
    <h1 class="page-title">Students</h1>
    <p class="muted"><?= number_format($total) ?> student<?= $total === 1 ? '' : 's' ?><?= $dues ? ', with payment status for ' . e($dues['title'] . ' ' . $dues['session_label']) : '' ?></p>
  </header>

  <form class="filters" method="get">
    <div class="field">
      <label for="s-q">Search</label>
      <input id="s-q" name="q" type="search" value="<?= e($search) ?>" placeholder="Name, reg. number, email or phone">
    </div>
    <?php if ($dues): ?>
      <div class="field">
        <label for="s-status">Payment</label>
        <select id="s-status" name="status">
          <option value="">Everyone</option>
          <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
          <option value="unpaid" <?= $status === 'unpaid' ? 'selected' : '' ?>>Not yet paid</option>
        </select>
      </div>
    <?php endif; ?>
    <button class="btn btn-primary" type="submit">Filter</button>
  </form>

  <?php if (!$students): ?>
    <div class="empty"><h2>No students match</h2><p>Students appear here once they create an account.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Name</th><th>Reg. number</th><th>Contact</th><th>Saved card</th><th>Dues</th></tr></thead>
        <tbody>
        <?php foreach ($students as $s): ?>
          <tr>
            <td><?= e($s['last_name'] . ', ' . $s['first_name']) ?></td>
            <td><?= e($s['reg_number']) ?></td>
            <td><?= e($s['email']) ?><span class="sub"><?= e(format_phone($s['phone'])) ?></span></td>
            <td><?= $s['cards'] ? 'Yes' : 'No' ?></td>
            <td>
              <?php if ($s['reference']): ?>
                <a class="badge badge-success" href="<?= e(url('receipt.php?ref=' . urlencode($s['reference']))) ?>">Paid <?= e(date('j M', strtotime($s['paid_at']))) ?></a>
              <?php elseif ($dues): ?>
                <span class="badge badge-abandoned">Not yet paid</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php require __DIR__ . '/../partials/pagination.php'; ?>
  <?php endif; ?>
</div>
