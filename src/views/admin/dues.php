<?php /** @var array $dues */ ?>
<div class="wrap page">
  <header class="page-head">
    <h1 class="page-title">Dues</h1>
    <p class="muted">Only one dues can be open at a time. Students always pay the open one.</p>
  </header>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Dues</th><th>Session</th><th>Amount</th><th>Due</th><th>Paid</th><th>Collected</th><th><span class="sr-only">Actions</span></th></tr></thead>
      <tbody>
      <?php foreach ($dues as $d): ?>
        <tr>
          <td><?= e($d['title']) ?> <?php if ($d['is_active']): ?><span class="badge badge-success">Open</span><?php endif; ?></td>
          <td><?= e($d['session_label']) ?></td>
          <td><?= e(naira((int) $d['amount_kobo'])) ?></td>
          <td><?= $d['due_date'] ? e(date('j M Y', strtotime($d['due_date']))) : '' ?></td>
          <td><?= number_format((int) $d['paid']) ?></td>
          <td><?= e(naira((int) $d['collected'])) ?></td>
          <td>
            <form method="post" class="inline">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
              <?php if ($d['is_active']): ?>
                <button class="btn-link danger" name="action" value="close" type="submit">Close</button>
              <?php else: ?>
                <button class="btn-link" name="action" value="activate" type="submit">Open for payment</button>
              <?php endif; ?>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <section class="panel narrow-panel">
    <h2 class="panel-title">New dues</h2>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">
      <div class="field">
        <label for="d-title">Name</label>
        <input id="d-title" name="title" required maxlength="120" value="<?= old('title', "Students' Union dues") ?>">
      </div>
      <div class="field-row">
        <div class="field">
          <label for="d-session">Session</label>
          <input id="d-session" name="session_label" required placeholder="2027/2028" value="<?= old('session_label') ?>">
        </div>
        <div class="field">
          <label for="d-amount">Amount in naira</label>
          <input id="d-amount" name="amount" inputmode="decimal" required placeholder="1700" value="<?= old('amount') ?>">
        </div>
      </div>
      <div class="field">
        <label for="d-due">Pay by (optional)</label>
        <input id="d-due" name="due_date" type="date" value="<?= old('due_date') ?>">
      </div>
      <label class="check"><input type="checkbox" name="activate" value="1" checked> Open for payment now (closes the current one)</label>
      <button class="btn btn-primary" type="submit">Create dues</button>
    </form>
  </section>
</div>
