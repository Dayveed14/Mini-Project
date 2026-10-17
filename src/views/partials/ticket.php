<?php
/**
 * The dues "ticket": the receipt-shaped card used on the landing page,
 * the student dashboard and the printable receipt.
 *
 * @var array      $ticketDues
 * @var array|null $ticketPayment  successful payment, if any
 * @var array|null $ticketUser
 */
$isPaid = !empty($ticketPayment) && $ticketPayment['status'] === 'success';
$payee = env('PAYEE_NAME', "University of Uyo Students' Union");
?>
<article class="ticket <?= $isPaid ? 'is-paid' : '' ?>" aria-label="<?= e($ticketDues['title']) ?> <?= $isPaid ? 'receipt' : 'summary' ?>">
  <div class="ticket-head">
    <img src="<?= e(asset('img/seal.png')) ?>" alt="University of Uyo seal" width="52" height="56">
    <div>
      <p class="ticket-payee"><?= e($payee) ?></p>
      <h2 class="ticket-title"><?= e($ticketDues['title']) ?></h2>
      <p class="ticket-session"><?= e($ticketDues['session_label']) ?> session</p>
    </div>
  </div>

  <p class="ticket-amount">
    <span class="ticket-amount-label"><?= $isPaid ? 'Amount paid' : 'Amount due' ?></span>
    <span class="ticket-amount-value"><?= e(naira((int) $ticketDues['amount_kobo'])) ?></span>
  </p>

  <dl class="ticket-rows">
    <?php if (!empty($ticketUser)): ?>
      <div><dt>Student</dt><dd><?= e($ticketUser['first_name'] . ' ' . $ticketUser['last_name']) ?></dd></div>
      <?php if (!empty($ticketUser['reg_number'])): ?>
        <div><dt>Reg. number</dt><dd><?= e($ticketUser['reg_number']) ?></dd></div>
      <?php endif; ?>
    <?php endif; ?>
    <?php if ($isPaid): ?>
      <div><dt>Paid on</dt><dd><?= e(date('j M Y, g:i a', strtotime($ticketPayment['paid_at']))) ?></dd></div>
      <?php if (!empty($ticketPayment['card_last4'])): ?>
        <div><dt>Card</dt><dd>•••• <?= e($ticketPayment['card_last4']) ?></dd></div>
      <?php endif; ?>
      <div><dt>Reference</dt><dd class="ticket-ref"><?= e($ticketPayment['reference']) ?></dd></div>
    <?php elseif (!empty($ticketDues['due_date'])): ?>
      <div><dt>Pay by</dt><dd><?= e(date('j F Y', strtotime($ticketDues['due_date']))) ?></dd></div>
    <?php endif; ?>
  </dl>

  <div class="ticket-tear" aria-hidden="true"></div>

  <div class="ticket-foot">
    <?php if ($isPaid): ?>
      <span class="stamp">Paid</span>
      <span class="ticket-note">Verified by Paystack</span>
    <?php else: ?>
      <span class="ticket-note"><?= isset($ticketNote) ? e($ticketNote) : 'Card payments are processed by Paystack' ?></span>
    <?php endif; ?>
  </div>
</article>
