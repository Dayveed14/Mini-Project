<?php /** @var array $payment @var array $dues @var array $student @var string $backUrl */ ?>
<div class="wrap page receipt-page">
  <div class="receipt-tools no-print">
    <a class="btn-link" href="<?= e($backUrl) ?>">Back</a>
    <button class="btn btn-primary" type="button" data-print>Print receipt</button>
  </div>
  <?php $ticketDues = $dues; $ticketPayment = $payment; $ticketUser = $student; require __DIR__ . '/partials/ticket.php'; ?>
  <p class="receipt-fine">Keep this reference. The union office can confirm it at any time against Paystack's records.</p>
</div>
