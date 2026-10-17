<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();

if (is_post()) {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $current = (string) ($_POST['current_password'] ?? '');
    $hash = (string) q_value('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);

    // Every change on this page needs the current password.
    if (!password_verify($current, $hash)) {
        flash('error', 'Your current password is wrong, so nothing was changed.');
        redirect('account.php');
    }

    if ($action === 'password') {
        $new = (string) ($_POST['new_password'] ?? '');
        if ($problem = password_problem($new)) {
            flash('error', $problem);
        } elseif ($new !== ($_POST['new_password_confirm'] ?? '')) {
            flash('error', 'The new passwords don\'t match.');
        } else {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);
            flash('success', 'Password changed.');
        }
    } elseif ($action === 'phone') {
        $phone = normalize_phone((string) ($_POST['phone'] ?? ''));
        if ($phone === null) {
            flash('error', 'Enter a Nigerian mobile number, e.g. 0803 123 4567.');
        } elseif (q_value('SELECT id FROM users WHERE phone = ? AND id <> ?', [$phone, $user['id']])) {
            flash('error', 'Another account already uses that phone number.');
        } else {
            q('UPDATE users SET phone = ? WHERE id = ?', [$phone, $user['id']]);
            flash('success', 'Phone number updated. Text PAY from ' . format_phone($phone) . ' from now on.');
        }
    } elseif ($action === 'pin' && $user['role'] === 'student') {
        $pin = (string) ($_POST['pin'] ?? '');
        if (!preg_match('/^\d{4}$/', $pin) || in_array($pin, ['0000', '1111', '1234', '4321', '1212'], true)) {
            flash('error', 'Choose 4 digits that aren\'t easy to guess (not 1234 or 0000).');
        } elseif ($pin !== ($_POST['pin_confirm'] ?? '')) {
            flash('error', 'The two PINs don\'t match.');
        } else {
            q('UPDATE users SET sms_pin_hash = ? WHERE id = ?', [password_hash($pin, PASSWORD_DEFAULT), $user['id']]);
            flash('success', 'SMS PIN saved.');
        }
    } elseif ($action === 'pin_remove') {
        q('UPDATE users SET sms_pin_hash = NULL WHERE id = ?', [$user['id']]);
        flash('success', 'SMS PIN removed. Paying by SMS is now off.');
    }
    redirect('account.php');
}

render('account', ['title' => 'Account', 'active' => 'account', 'user' => $user]);
