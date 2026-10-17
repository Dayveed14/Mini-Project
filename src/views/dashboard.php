<?php
/** @var array $user @var array|null $dues @var array|null $paid @var array|null $card @var array $history @var array|null $processing @var string|null $smsNumber */
$cardUsable = $card && !card_expired($card);
?>
<div class="wrap page">
  <header class="page-head">
    <h1 class="page-title">Hello, <?= e($user['first_name']) ?></h1>
    <?php if ($dues): ?>
      <p class="muted"><?= $paid ? 'You\'re all paid up for the ' . e($dues['session_label']) . ' session.' : 'Your ' . e($dues['session_label']) . ' dues are still open.' ?></p>
    <?php endif; ?>
  </header>

  <?php if (!$dues): ?>
    <div class="empty">
      <h2>No dues are open right now</h2>
      <p>When the union opens dues for a new session, you'll be able to pay here.</p>
    </div>
  <?php else: ?>
    <div class="dash-grid">
      <div class="dash-ticket">
        <?php $ticketDues = $dues; $ticketPayment = $paid; $ticketUser = $user; require __DIR__ . '/partials/ticket.php'; ?>

        <?php if ($paid): ?>
          <div class="actions">
            <a class="btn btn-primary" href="<?= e(url('receipt.php?ref=' . urlencode($paid['reference']))) ?>">View and print receipt</a>
          </div>
        <?php elseif ($processing): ?>
          <p class="notice">A payment of <?= e(naira((int) $processing['amount_kobo'])) ?> is processing (ref. <?= e($processing['reference']) ?>). Refresh in a minute.</p>
        <?php else: ?>
          <div class="pay-box">
            <?php if ($cardUsable): ?>
              <form method="post" action="<?= e(url('pay.php')) ?>" data-confirm="Charge <?= e(naira((int) $dues['amount_kobo'])) ?> to your card ending <?= e($card['last4']) ?>?">
                <?= csrf_field() ?>
                <input type="hidden" name="method" value="saved_card">
                <button class="btn btn-primary btn-block" type="submit">Pay <?= e(naira((int) $dues['amount_kobo'])) ?> with card ending <?= e($card['last4']) ?></button>
              </form>
              <form method="post" action="<?= e(url('pay.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="method" value="checkout">
                <button class="btn btn-quiet btn-block" type="submit">Use a different card</button>
              </form>
            <?php else: ?>
              <form method="post" action="<?= e(url('pay.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="method" value="checkout">
                <button class="btn btn-primary btn-block" type="submit">Pay <?= e(naira((int) $dues['amount_kobo'])) ?> with card</button>
              </form>
              <p class="hint center">You'll enter your card on Paystack's secure page.</p>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="dash-side">
        <section class="panel">
          <h2 class="panel-title">Saved card</h2>
          <?php if ($card): ?>
            <div class="card-chip <?= card_expired($card) ? 'is-expired' : '' ?>">
              <span class="card-brand"><?= e(ucfirst((string) ($card['card_type'] ?: 'Card'))) ?></span>
              <span class="card-number">•••• <?= e($card['last4']) ?></span>
              <span class="card-meta"><?= e($card['bank'] ?: '') ?> <?= card_expired($card) ? 'Expired' : 'Expires' ?> <?= e($card['exp_month'] . '/' . substr($card['exp_year'], -2)) ?></span>
            </div>
            <form method="post" action="<?= e(url('cards.php')) ?>" data-confirm="Remove this card? You can add it again on your next payment.">
              <?= csrf_field() ?>
              <input type="hidden" name="card_id" value="<?= (int) $card['id'] ?>">
              <button class="btn-link danger" type="submit">Remove card</button>
            </form>
          <?php else: ?>
            <p class="muted">Your card is saved securely by Paystack after your first card payment. Only its last four digits are shown here.</p>
          <?php endif; ?>
        </section>

        <section class="panel">
          <h2 class="panel-title">Pay by SMS</h2>
          <?php if (!$card): ?>
            <p class="muted">Available after your first card payment.</p>
          <?php elseif (empty($user['sms_pin_hash'])): ?>
            <p class="muted">Set a 4-digit SMS PIN to pay by text message.</p>
            <a class="btn btn-quiet" href="<?= e(url('account.php#sms-pin')) ?>">Set SMS PIN</a>
          <?php else: ?>
            <p>From <strong><?= e(format_phone($user['phone'])) ?></strong>, text</p>
            <p class="sms-sample">PAY 1234</p>
            <p class="muted">replacing 1234 with your PIN<?= $smsNumber ? ', to <strong>' . e($smsNumber) . '</strong>' : '' ?>. We'll charge your saved card and reply with your reference.</p>
          <?php endif; ?>
        </section>
      </div>
    </div>
  <?php endif; ?>

  <section class="history">
    <h2 class="section-title">Payment history</h2>
    <?php if (!$history): ?>
      <p class="muted">No payments yet.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Date</th><th>Dues</th><th>Amount</th><th>Method</th><th>Status</th><th><span class="sr-only">Receipt</span></th></tr></thead>
          <tbody>
          <?php foreach ($history as $p): ?>
            <tr>
              <td><?= e(date('j M Y', strtotime($p['created_at']))) ?></td>
              <td><?= e($p['title'] . ' ' . $p['session_label']) ?></td>
              <td><?= e(naira((int) $p['amount_kobo'])) ?></td>
              <td><?= e(['checkout' => 'Card', 'saved_card' => 'Saved card', 'sms' => 'SMS'][$p['channel']]) ?></td>
              <td><span class="badge badge-<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span></td>
              <td><?php if ($p['status'] === 'success'): ?><a href="<?= e(url('receipt.php?ref=' . urlencode($p['reference']))) ?>">Receipt</a><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
