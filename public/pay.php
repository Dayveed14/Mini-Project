<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_role('student');
require_post();

$dues = active_dues();
if (!$dues) {
    flash('error', 'No dues are open for payment right now.');
    redirect('dashboard.php');
}

$method = $_POST['method'] ?? 'checkout';

try {
    if ($method === 'saved_card') {
        $card = default_card((int) $user['id']);
        if (!$card || card_expired($card)) {
            flash('error', 'Your saved card can\'t be used. Pay with a new card instead.');
            redirect('dashboard.php');
        }
        $payment = charge_saved_card($user, $dues, $card);
        match ($payment['status']) {
            'success' => flash('success', 'Payment received. Your receipt is ready.'),
            'pending' => flash('info', 'Your bank is still processing the payment. This page will show it once it clears.'),
            default   => flash('error', 'Your card was declined: ' . ($payment['gateway_response'] ?: 'no reason given') . '. Try a different card.'),
        };
        redirect($payment['status'] === 'success' ? 'receipt.php?ref=' . urlencode($payment['reference']) : 'dashboard.php');
    }

    // New card: Paystack hosted checkout. Card details are typed on Paystack's page, not ours.
    redirect(start_checkout($user, $dues));
} catch (DomainException $ex) {
    flash('info', $ex->getMessage());
} catch (PaystackException $ex) {
    flash('error', 'Paystack couldn\'t start the payment: ' . $ex->getMessage());
}
redirect('dashboard.php');
