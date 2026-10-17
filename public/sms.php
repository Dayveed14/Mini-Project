<?php
/*
 * SMS Enabler "SMS to Webserver" handler.
 * In SMS Enabler set the URL to:  https://your-site/sms.php?token=<SMS_WEBHOOK_TOKEN>
 * SMS Enabler POSTs `sender` and `text`; whatever we print is sent back as the reply SMS.
 *
 * A student texts:  PAY 4821   (PAY + their 4-digit SMS PIN)
 * Sender numbers can be spoofed, which is why the PIN is required.
 */
define('NO_SESSION', true);
require __DIR__ . '/../src/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

$expected = (string) env('SMS_WEBHOOK_TOKEN', '');
$given = (string) ($_GET['token'] ?? '');
if ($expected === '' || str_contains($expected, 'replace_with') || !hash_equals($expected, $given)) {
    http_response_code(403);
    exit;   // empty body = no reply SMS
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}

$rawSender = mb_substr(trim((string) ($_POST['sender'] ?? '')), 0, 20);
$text = trim((string) ($_POST['text'] ?? ''));
// Note: replies avoid the naira sign so they fit the plain GSM character set (160 chars, not 70).
$phone = normalize_phone($rawSender);

/** Log (with the PIN masked), print the reply, stop. */
function reply(string $sender, ?int $userId, string $text, string $message): never
{
    $masked = preg_replace('/\d{4}/', '****', mb_substr($text, 0, 160));
    q('INSERT INTO sms_messages (sender, user_id, body, reply) VALUES (?, ?, ?, ?)', [$sender, $userId, $masked, $message]);
    echo $message;
    exit;
}

if ($rawSender === '') {
    exit;
}

// Flood control: at most 5 texts from one number per 10 minutes.
$recent = (int) q_value(
    'SELECT COUNT(*) FROM sms_messages WHERE sender = ? AND received_at > (NOW() - INTERVAL 10 MINUTE)',
    [$rawSender]
);
if ($recent >= 5) {
    exit;   // stay silent so we don't pay for replies to a flood
}

if (!preg_match('/^PAY(?:\s+(\d{4}))?$/i', $text, $m)) {
    reply($rawSender, null, $text, 'UniUyo Dues: to pay, text PAY followed by your 4-digit SMS PIN, e.g. PAY 4821.');
}

$user = $phone ? q_one("SELECT * FROM users WHERE phone = ? AND role = 'student' AND is_active = 1", [$phone]) : null;
if (!$user) {
    reply($rawSender, null, $text, 'UniUyo Dues: this number is not registered. Sign up at ' . url('register.php'));
}
$userId = (int) $user['id'];

if (empty($user['sms_pin_hash'])) {
    reply($rawSender, $userId, $text, 'UniUyo Dues: set an SMS PIN in your account first: ' . url('account.php'));
}

// Lock SMS payments after 3 wrong PINs in an hour.
$wrongPins = (int) q_value(
    "SELECT COUNT(*) FROM sms_messages WHERE user_id = ? AND reply LIKE 'UniUyo Dues: wrong PIN%' AND received_at > (NOW() - INTERVAL 1 HOUR)",
    [$userId]
);
if ($wrongPins >= 3) {
    reply($rawSender, $userId, $text, 'UniUyo Dues: SMS payments are paused for an hour after 3 wrong PINs. Pay online at ' . url('login.php'));
}
if (empty($m[1]) || !password_verify($m[1], $user['sms_pin_hash'])) {
    reply($rawSender, $userId, $text, 'UniUyo Dues: wrong PIN. Text PAY and your 4-digit SMS PIN.');
}

$dues = active_dues();
if (!$dues) {
    reply($rawSender, $userId, $text, 'UniUyo Dues: no dues are open for payment right now.');
}
if ($done = successful_payment($userId, (int) $dues['id'])) {
    reply($rawSender, $userId, $text, 'UniUyo Dues: you already paid ' . $dues['title'] . ' ' . $dues['session_label'] . '. Ref ' . $done['reference']);
}

$card = default_card($userId);
if (!$card || card_expired($card)) {
    reply($rawSender, $userId, $text, 'UniUyo Dues: no usable saved card. Pay once online to save one: ' . url('login.php'));
}

try {
    $payment = charge_saved_card($user, $dues, $card, 'sms');
} catch (DomainException $ex) {
    reply($rawSender, $userId, $text, 'UniUyo Dues: ' . $ex->getMessage());
}

reply($rawSender, $userId, $text, match ($payment['status']) {
    'success' => 'UniUyo Dues: paid NGN' . number_format((int) $dues['amount_kobo'] / 100) . ' for ' . $dues['title'] . ' ' . $dues['session_label'] . ' with card ending ' . $card['last4'] . '. Ref ' . $payment['reference'],
    'pending' => 'UniUyo Dues: your payment is processing. Ref ' . $payment['reference'] . '. Check your dashboard shortly.',
    default   => 'UniUyo Dues: your card was declined. Pay online with another card: ' . url('login.php'),
});
