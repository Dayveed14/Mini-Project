<?php
require __DIR__ . '/../../src/bootstrap.php';

require_role('admin');

$allDues = q('SELECT id, title, session_label FROM dues ORDER BY id DESC')->fetchAll();
$active = active_dues();

$duesId = isset($_GET['dues']) ? (int) $_GET['dues'] : (int) ($active['id'] ?? 0);
$status = in_array($_GET['status'] ?? '', ['success', 'pending', 'failed', 'abandoned'], true) ? $_GET['status'] : '';
$search = trim((string) ($_GET['q'] ?? ''));
$flagged = !empty($_GET['flag']);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;

$where = ['1 = 1'];
$params = [];
if ($duesId) {
    $where[] = 'p.dues_id = ?';
    $params[] = $duesId;
}
if ($status !== '') {
    $where[] = 'p.status = ?';
    $params[] = $status;
}
if ($flagged) {
    $where[] = "(p.gateway_response LIKE 'DUPLICATE%' OR p.gateway_response LIKE 'AMOUNT MISMATCH%')";
}
if ($search !== '') {
    $where[] = '(p.reference LIKE ? OR u.reg_number LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR CONCAT(u.first_name, \' \', u.last_name) LIKE ?)';
    $like = '%' . addcslashes($search, '%_\\') . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
$whereSql = implode(' AND ', $where);
$baseSql = "FROM payments p JOIN users u ON u.id = p.user_id JOIN dues d ON d.id = p.dues_id WHERE $whereSql";
$select = "SELECT p.reference, p.amount_kobo, p.status, p.channel, p.gateway_response, p.card_last4, p.created_at, p.paid_at,
                  u.first_name, u.last_name, u.reg_number, u.email, u.phone, d.title, d.session_label";

if (($_GET['export'] ?? '') === 'csv') {
    $rows = q("$select $baseSql ORDER BY p.created_at DESC", $params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="uniuyo-dues-payments-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8
    fputcsv($out, ['Reference', 'Student', 'Reg. number', 'Email', 'Phone', 'Dues', 'Session', 'Amount (NGN)', 'Method', 'Status', 'Gateway response', 'Card last 4', 'Created', 'Paid at'], ',', '"', '');
    while ($r = $rows->fetch()) {
        $line = [
            $r['reference'], $r['first_name'] . ' ' . $r['last_name'], $r['reg_number'], $r['email'], $r['phone'],
            $r['title'], $r['session_label'], number_format($r['amount_kobo'] / 100, 2, '.', ''), $r['channel'],
            $r['status'], $r['gateway_response'], $r['card_last4'], $r['created_at'], $r['paid_at'],
        ];
        // Stop spreadsheet formula injection from user-entered values.
        $line = array_map(static fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, $line);
        fputcsv($out, $line, ',', '"', '');
    }
    fclose($out);
    exit;
}

$total = (int) q_value("SELECT COUNT(*) $baseSql", $params);
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
$payments = q("$select $baseSql ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset", $params)->fetchAll();

render('admin/payments', [
    'title'    => 'Payments',
    'active'   => 'payments',
    'allDues'  => $allDues,
    'duesId'   => $duesId,
    'status'   => $status,
    'search'   => $search,
    'flagged'  => $flagged,
    'payments' => $payments,
    'total'    => $total,
    'page'     => $page,
    'pages'    => $pages,
]);
