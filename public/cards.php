<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_role('student');
require_post();

// Scoped to the signed-in user, so nobody can delete another student's card by changing the ID.
$deleted = q('DELETE FROM payment_methods WHERE id = ? AND user_id = ?', [(int) ($_POST['card_id'] ?? 0), $user['id']])->rowCount();
flash($deleted ? 'success' : 'error', $deleted ? 'Card removed.' : 'That card wasn\'t found.');
redirect('dashboard.php');
