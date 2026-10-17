<?php
require __DIR__ . '/../../src/bootstrap.php';

require_role('admin');
$dues = active_dues();
$duesId = (int) ($dues['id'] ?? 0);

$status = in_array($_GET['status'] ?? '', ['paid', 'unpaid'], true) ? $_GET['status'] : '';
$search = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 30;

$where = ["u.role = 'student'"];
$params = [$duesId];
if ($status === 'paid') {
    $where[] = 'p.id IS NOT NULL';
} elseif ($status === 'unpaid') {
    $where[] = 'p.id IS NULL';
}
if ($search !== '') {
    $where[] = '(u.reg_number LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR CONCAT(u.first_name, \' \', u.last_name) LIKE ?)';
    $like = '%' . addcslashes($search, '%_\\') . '%';
    array_push($params, $like, $like, $like, $like);
}
$baseSql = "FROM users u
            LEFT JOIN payments p ON p.user_id = u.id AND p.dues_id = ? AND p.status = 'success'
            WHERE " . implode(' AND ', $where);

$total = (int) q_value("SELECT COUNT(*) $baseSql", $params);
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$students = q(
    "SELECT u.id, u.first_name, u.last_name, u.reg_number, u.email, u.phone, u.created_at,
            p.reference, p.paid_at,
            (SELECT COUNT(*) FROM payment_methods pm WHERE pm.user_id = u.id) AS cards
     $baseSql ORDER BY u.last_name, u.first_name LIMIT $perPage OFFSET $offset",
    $params
)->fetchAll();

render('admin/students', [
    'title'    => 'Students',
    'active'   => 'students',
    'dues'     => $dues,
    'status'   => $status,
    'search'   => $search,
    'students' => $students,
    'total'    => $total,
    'page'     => $page,
    'pages'    => $pages,
]);
