<?php
require __DIR__ . '/../src/bootstrap.php';

$viewer = require_login();
$reference = (string) ($_GET['ref'] ?? '');

$payment = q_one("SELECT * FROM payments WHERE reference = ? AND status = 'success'", [$reference]);
// Students can only open their own receipts; admins can open any.
if (!$payment || ($viewer['role'] !== 'admin' && (int) $payment['user_id'] !== (int) $viewer['id'])) {
    abort(404, 'There is no paid receipt with that reference on your account.');
}

render('receipt', [
    'title'     => 'Receipt ' . $payment['reference'],
    'active'    => $viewer['role'] === 'admin' ? 'payments' : 'dashboard',
    'bodyClass' => 'page-receipt',
    'payment'   => $payment,
    'dues'      => q_one('SELECT * FROM dues WHERE id = ?', [$payment['dues_id']]),
    'student'   => q_one('SELECT * FROM users WHERE id = ?', [$payment['user_id']]),
    'backUrl'   => $viewer['role'] === 'admin' ? url('admin/payments.php') : url('dashboard.php'),
]);
