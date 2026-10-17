<?php
require __DIR__ . '/../../src/bootstrap.php';

require_role('admin');

if (is_post()) {
    csrf_verify();
    $changed = reconcile_pending(null, 50);
    flash('success', $changed ? "Checked with Paystack: {$changed} payment(s) updated." : 'Checked with Paystack: nothing to update.');
    redirect('admin/index.php');
}

$dues = active_dues();
$pendingCount = (int) q_value("SELECT COUNT(*) FROM payments WHERE status = 'pending' AND created_at > (NOW() - INTERVAL 2 DAY)");

$stats = ['students' => (int) q_value("SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = 1")];
if ($dues) {
    $row = q_one(
        "SELECT COUNT(*) AS paid, COALESCE(SUM(amount_kobo), 0) AS collected
         FROM payments WHERE dues_id = ? AND status = 'success'",
        [$dues['id']]
    );
    $stats['paid'] = (int) $row['paid'];
    $stats['collected'] = (int) $row['collected'];
    $stats['unpaid'] = max(0, $stats['students'] - $stats['paid']);
    $stats['percent'] = $stats['students'] ? (int) round($stats['paid'] / $stats['students'] * 100) : 0;
    $stats['by_channel'] = q(
        "SELECT channel, COUNT(*) AS n FROM payments WHERE dues_id = ? AND status = 'success' GROUP BY channel",
        [$dues['id']]
    )->fetchAll(PDO::FETCH_KEY_PAIR);
}

$recent = q(
    "SELECT p.reference, p.amount_kobo, p.status, p.channel, p.created_at, p.paid_at, p.gateway_response,
            u.first_name, u.last_name, u.reg_number
     FROM payments p JOIN users u ON u.id = p.user_id
     ORDER BY p.created_at DESC LIMIT 8"
)->fetchAll();

$attention = (int) q_value("SELECT COUNT(*) FROM payments WHERE gateway_response LIKE 'DUPLICATE%' OR gateway_response LIKE 'AMOUNT MISMATCH%'");

render('admin/index', [
    'title'     => 'Overview',
    'active'    => 'overview',
    'dues'      => $dues,
    'stats'     => $stats,
    'recent'    => $recent,
    'attention' => $attention,
    'pendingCount' => $pendingCount,
]);
