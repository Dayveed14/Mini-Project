<?php
/*
 * Paystack webhook. Set this URL in Paystack Dashboard > Settings > API Keys & Webhooks:
 *   https://your-site/webhook.php
 *
 * 1. Reject anything without a valid HMAC-SHA512 signature.
 * 2. Log the event.
 * 3. For charge.success, re-verify with Paystack and apply. Idempotent, so
 *    Paystack's retries and the browser callback can't double-count.
 */
define('NO_SESSION', true);
require __DIR__ . '/../src/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$raw = (string) file_get_contents('php://input');
$signature = (string) ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '');

if (!paystack_signature_valid($raw, $signature)) {
    app_log('webhook', 'rejected: bad signature', ['ip' => client_ip()]);
    http_response_code(401);
    exit('Invalid signature');
}

$event = json_decode($raw, true);
if (!is_array($event) || empty($event['event'])) {
    http_response_code(400);
    exit('Bad payload');
}

$reference = (string) ($event['data']['reference'] ?? '');
q('INSERT INTO webhook_events (event, reference, payload) VALUES (?, ?, ?)', [$event['event'], $reference ?: null, $raw]);
$logId = (int) db()->lastInsertId();

$outcome = 'ignored';
try {
    $known = $reference !== '' && q_value('SELECT id FROM payments WHERE reference = ?', [$reference]);
    if (!$known) {
        // Not one of ours (e.g. another product on the same Paystack account). Acknowledge so Paystack stops retrying.
        $outcome = 'unknown reference';
    } elseif (in_array($event['event'], ['charge.success', 'charge.failed'], true)) {
        // Don't act on the webhook body alone; fetch the transaction from Paystack.
        $outcome = apply_transaction(paystack_verify($reference));
    }
} catch (PaystackException $ex) {
    q('UPDATE webhook_events SET outcome = ? WHERE id = ?', ['verify failed: ' . mb_substr($ex->getMessage(), 0, 100), $logId]);
    http_response_code(500);   // Paystack will retry later
    exit('Retry');
}

q('UPDATE webhook_events SET outcome = ? WHERE id = ?', [$outcome, $logId]);
http_response_code(200);
echo 'OK';
