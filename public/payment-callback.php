<?php
/*
 * Paystack sends the student's browser here after checkout with ?reference=...
 * The query string proves nothing, so we ask Paystack directly what happened.
 * The webhook does the same job server-to-server, in case the student closes
 * the tab before getting here.
 */
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$reference = (string) ($_GET['reference'] ?? $_GET['trxref'] ?? '');

$payment = $reference !== '' ? q_one('SELECT * FROM payments WHERE reference = ? AND user_id = ?', [$reference, $user['id']]) : null;
if (!$payment) {
    flash('error', 'We couldn\'t find that payment. If you were charged, contact the union office with your Paystack receipt.');
    redirect(home_for($user));
}

try {
    apply_transaction(paystack_verify($reference));
} catch (PaystackException $ex) {
    flash('info', 'We couldn\'t confirm the payment with Paystack yet. It will show here as soon as Paystack confirms it.');
    redirect('dashboard.php');
}

$payment = q_one('SELECT * FROM payments WHERE id = ?', [$payment['id']]);
if ($payment['status'] === 'success') {
    flash('success', 'Payment received. Your card is saved for next time.');
    redirect('receipt.php?ref=' . urlencode($reference));
}

flash(
    $payment['status'] === 'pending' ? 'info' : 'error',
    $payment['status'] === 'pending'
        ? 'Your payment is still processing. Refresh this page in a minute.'
        : 'The payment didn\'t go through' . ($payment['gateway_response'] ? ': ' . $payment['gateway_response'] : '') . '. You weren\'t charged.'
);
redirect('dashboard.php');
