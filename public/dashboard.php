<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_role('student');
// Confirm any unfinished payments with Paystack (covers hosts where webhooks can't arrive).
reconcile_pending((int) $user['id'], 3);
$dues = active_dues();
$paid = $dues ? successful_payment((int) $user['id'], (int) $dues['id']) : null;
$card = default_card((int) $user['id']);

$history = q(
    'SELECT p.*, d.title, d.session_label FROM payments p JOIN dues d ON d.id = p.dues_id
     WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 20',
    [$user['id']]
)->fetchAll();

$processing = null;
foreach ($history as $row) {
    if ($row['status'] === 'pending' && $row['channel'] !== 'checkout' && $dues && (int) $row['dues_id'] === (int) $dues['id']) {
        $processing = $row;
        break;
    }
}

render('dashboard', [
    'title'      => 'My dues',
    'active'     => 'dashboard',
    'user'       => $user,
    'dues'       => $dues,
    'paid'       => $paid,
    'card'       => $card,
    'history'    => $history,
    'processing' => $processing,
    'smsNumber'  => env('SMS_NUMBER'),
]);
