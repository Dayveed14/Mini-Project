<?php
require __DIR__ . '/../../src/bootstrap.php';

require_role('admin');

if (is_post()) {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $session = trim((string) ($_POST['session_label'] ?? ''));
        $amount = (float) str_replace(',', '', (string) ($_POST['amount'] ?? '0'));
        $dueDate = (string) ($_POST['due_date'] ?? '');
        remember_input(['title', 'session_label', 'amount', 'due_date']);

        if ($title === '' || mb_strlen($title) > 120) {
            flash('error', 'Give the dues a name, up to 120 characters.');
        } elseif (!preg_match('#^\d{4}/\d{4}$#', $session)) {
            flash('error', 'Write the session as two years, e.g. 2026/2027.');
        } elseif ($amount < 100 || $amount > 1000000) {
            flash('error', 'Enter an amount between ₦100 and ₦1,000,000.');
        } elseif ($dueDate !== '' && !DateTime::createFromFormat('Y-m-d', $dueDate)) {
            flash('error', 'Pick a valid due date.');
        } else {
            $makeActive = !empty($_POST['activate']);
            if ($makeActive) {
                q('UPDATE dues SET is_active = 0');
            }
            q(
                'INSERT INTO dues (title, session_label, amount_kobo, is_active, due_date) VALUES (?, ?, ?, ?, ?)',
                [$title, $session, (int) round($amount * 100), $makeActive ? 1 : 0, $dueDate ?: null]
            );
            forget_input();
            flash('success', 'Dues created' . ($makeActive ? ' and opened for payment.' : '.'));
        }
    } elseif ($action === 'activate') {
        q('UPDATE dues SET is_active = 0');
        q('UPDATE dues SET is_active = 1 WHERE id = ?', [(int) ($_POST['id'] ?? 0)]);
        flash('success', 'Students now pay this dues.');
    } elseif ($action === 'close') {
        q('UPDATE dues SET is_active = 0 WHERE id = ?', [(int) ($_POST['id'] ?? 0)]);
        flash('success', 'Dues closed. Students can\'t pay until you open one again.');
    }
    redirect('admin/dues.php');
}

$dues = q(
    "SELECT d.*, COUNT(p.id) AS paid, COALESCE(SUM(p.amount_kobo), 0) AS collected
     FROM dues d LEFT JOIN payments p ON p.dues_id = d.id AND p.status = 'success'
     GROUP BY d.id ORDER BY d.id DESC"
)->fetchAll();

render('admin/dues', ['title' => 'Dues', 'active' => 'dues', 'dues' => $dues]);
