<?php
/*
 * Fill a LOCAL database with demo students and payments so the admin pages have
 * something to show. Refuses to run when APP_ENV=production.
 *
 *   php bin/seed-demo.php
 *
 * Every demo student's password is: Student123
 */
if (PHP_SAPI !== 'cli') {
    exit("Run this from the command line.\n");
}
require __DIR__ . '/../src/bootstrap.php';

if (env('APP_ENV') === 'production') {
    exit("Not running demo seed in production.\n");
}

$dues = active_dues() ?? exit("No active dues. Import database/schema.sql first.\n");
$names = [
    ['Aniekan', 'Udoh'], ['Ini', 'Etim'], ['Mfon', 'Akpan'], ['Ekemini', 'Bassey'], ['Idara', 'Okon'],
    ['Uduak', 'Essien'], ['Nsikak', 'Inyang'], ['Imaobong', 'Ekpo'], ['Chinonso', 'Eze'], ['Tobi', 'Adeyemi'],
    ['Aisha', 'Bello'], ['Emem', 'Umoh'], ['Kufre', 'Offiong'], ['Ifiok', 'Ukpong'], ['Ngozi', 'Okafor'],
];
$depts = ['SC/CS', 'EN/EE', 'SS/EC', 'AR/EN', 'MS/AC'];
$hash = password_hash('Student123', PASSWORD_DEFAULT);
$channels = ['checkout', 'saved_card', 'sms'];
$created = 0;

foreach ($names as $i => [$first, $last]) {
    $reg = sprintf('%02d/%s/%03d', 21 + $i % 4, $depts[$i % 5], 100 + $i);
    $email = strtolower($first . '.' . $last) . '@demo.uniuyo.test';
    if (q_value('SELECT id FROM users WHERE email = ?', [$email])) {
        continue;
    }
    q(
        "INSERT INTO users (first_name, last_name, email, phone, reg_number, role, password_hash) VALUES (?, ?, ?, ?, ?, 'student', ?)",
        [$first, $last, $email, sprintf('+23480310%05d', $i), $reg, $hash]
    );
    $uid = (int) db()->lastInsertId();
    $created++;

    if ($i % 3 !== 2) {   // two in three have paid
        $paidAt = date('Y-m-d H:i:s', time() - ($i * 7 + 2) * 3600);
        q(
            "INSERT INTO payments (user_id, dues_id, reference, amount_kobo, channel, status, gateway_response, card_last4, paid_at, created_at)
             VALUES (?, ?, ?, ?, ?, 'success', 'Approved', ?, ?, ?)",
            [$uid, $dues['id'], new_reference('DEMO'), $dues['amount_kobo'], $channels[intdiv($i, 2) % 3], sprintf('%04d', 4081 + $i), $paidAt, $paidAt]
        );
    } elseif ($i % 2) {
        q(
            "INSERT INTO payments (user_id, dues_id, reference, amount_kobo, channel, status, gateway_response)
             VALUES (?, ?, ?, ?, 'checkout', 'failed', 'Declined')",
            [$uid, $dues['id'], new_reference('DEMO'), $dues['amount_kobo']]
        );
    }
}
echo "Added {$created} demo students. Password for each: Student123\n";
