<?php
declare(strict_types=1);

/*
 * Payment rules, in one place:
 *  - A payment row is created as 'pending' before Paystack is called.
 *  - It only becomes 'success' after paystack_verify() confirms status, amount
 *    and currency. Callback, webhook and SMS all funnel into apply_transaction().
 *  - The database refuses a second successful payment for the same student and dues.
 */

function active_dues(): ?array
{
    return q_one('SELECT * FROM dues WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
}

function successful_payment(int $userId, int $duesId): ?array
{
    return q_one(
        "SELECT * FROM payments WHERE user_id = ? AND dues_id = ? AND status = 'success' LIMIT 1",
        [$userId, $duesId]
    );
}

function default_card(int $userId): ?array
{
    return q_one(
        'SELECT * FROM payment_methods WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1',
        [$userId]
    );
}

function card_expired(array $card): bool
{
    $lastDay = mktime(23, 59, 59, (int) $card['exp_month'] + 1, 0, (int) $card['exp_year']);
    return $lastDay < time();
}

/**
 * Create a pending payment row, refusing if the student already paid or has a
 * charge in flight. Runs in a transaction with the user row locked so a double
 * click (or two SMS) can't create two charges.
 */
function create_pending_payment(array $user, array $dues, string $channel): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        q('SELECT id FROM users WHERE id = ? FOR UPDATE', [$user['id']]);

        // Checkouts left open for 2+ hours were abandoned. (A late webhook can still mark them paid.)
        q(
            "UPDATE payments SET status = 'abandoned', gateway_response = 'Checkout not completed'
             WHERE user_id = ? AND status = 'pending' AND created_at < (NOW() - INTERVAL 2 HOUR)",
            [$user['id']]
        );

        if (successful_payment((int) $user['id'], (int) $dues['id'])) {
            throw new DomainException('You have already paid these dues.');
        }
        if ($channel !== 'checkout') {
            $inFlight = q_value(
                "SELECT reference FROM payments
                 WHERE user_id = ? AND dues_id = ? AND status = 'pending' AND channel <> 'checkout'
                   AND created_at > (NOW() - INTERVAL 10 MINUTE) LIMIT 1",
                [$user['id'], $dues['id']]
            );
            if ($inFlight) {
                throw new DomainException('A payment is already being processed. Check back in a few minutes.');
            }
        }

        $reference = new_reference();
        q(
            'INSERT INTO payments (user_id, dues_id, reference, amount_kobo, channel) VALUES (?, ?, ?, ?, ?)',
            [$user['id'], $dues['id'], $reference, $dues['amount_kobo'], $channel]
        );
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    return q_one('SELECT * FROM payments WHERE reference = ?', [$reference]);
}

function payment_metadata(array $user, array $dues): array
{
    return [
        'user_id'       => (int) $user['id'],
        'dues_id'       => (int) $dues['id'],
        'custom_fields' => [
            ['display_name' => 'Student', 'variable_name' => 'student', 'value' => $user['first_name'] . ' ' . $user['last_name']],
            ['display_name' => 'Reg. number', 'variable_name' => 'reg_number', 'value' => (string) $user['reg_number']],
            ['display_name' => 'Dues', 'variable_name' => 'dues', 'value' => $dues['title'] . ' ' . $dues['session_label']],
        ],
    ];
}

/** New card: send the student to Paystack's hosted checkout. Returns the URL to redirect to. */
function start_checkout(array $user, array $dues): string
{
    $payment = create_pending_payment($user, $dues, 'checkout');
    try {
        $data = paystack_initialize(
            $user['email'],
            (int) $payment['amount_kobo'],
            $payment['reference'],
            url('payment-callback.php'),
            payment_metadata($user, $dues)
        );
    } catch (PaystackException $ex) {
        q("UPDATE payments SET status = 'failed', gateway_response = ? WHERE id = ?", [mb_substr($ex->getMessage(), 0, 255), $payment['id']]);
        throw $ex;
    }
    return (string) $data['authorization_url'];
}

/**
 * Saved card: charge the stored authorization, then verify.
 * Returns the payment row after processing ('success', 'failed' or still 'pending').
 */
function charge_saved_card(array $user, array $dues, array $card, string $channel = 'saved_card'): array
{
    $payment = create_pending_payment($user, $dues, $channel);
    try {
        paystack_charge_authorization(
            $card['authorization_code'],
            $user['email'],
            (int) $payment['amount_kobo'],
            $payment['reference'],
            payment_metadata($user, $dues)
        );
        apply_transaction(paystack_verify($payment['reference']));
    } catch (PaystackException $ex) {
        q(
            "UPDATE payments SET status = 'failed', gateway_response = ? WHERE id = ? AND status = 'pending'",
            [mb_substr($ex->getMessage(), 0, 255), $payment['id']]
        );
    }
    return q_one('SELECT * FROM payments WHERE id = ?', [$payment['id']]);
}

/**
 * Apply a verified Paystack transaction (the `data` object from /transaction/verify).
 * Idempotent: safe to call from the callback, the webhook and retries.
 * Returns a short outcome string for logs.
 */
function apply_transaction(array $tx): string
{
    $reference = (string) ($tx['reference'] ?? '');
    $payment = q_one('SELECT * FROM payments WHERE reference = ?', [$reference]);
    if ($payment === null) {
        return 'unknown reference';
    }
    if ($payment['status'] === 'success') {
        return 'already processed';
    }

    $status = (string) ($tx['status'] ?? '');
    $auth = is_array($tx['authorization'] ?? null) ? $tx['authorization'] : [];
    $gatewayResponse = mb_substr((string) ($tx['gateway_response'] ?? $status), 0, 255);

    if ($status !== 'success') {
        $final = in_array($status, ['failed', 'reversed'], true) ? 'failed' : ($status === 'abandoned' ? 'abandoned' : null);
        if ($final !== null) {
            q("UPDATE payments SET status = ?, gateway_response = ? WHERE id = ? AND status = 'pending'", [$final, $gatewayResponse, $payment['id']]);
        }
        return 'not successful: ' . $status;
    }

    // Never trust the amount from the browser or the webhook alone: compare with what we asked for.
    if ((int) ($tx['amount'] ?? 0) !== (int) $payment['amount_kobo'] || ($tx['currency'] ?? '') !== $payment['currency']) {
        q("UPDATE payments SET gateway_response = ? WHERE id = ?", ['AMOUNT MISMATCH: received ' . ($tx['amount'] ?? '?') . ' ' . ($tx['currency'] ?? ''), $payment['id']]);
        app_log('payments', 'amount mismatch', ['reference' => $reference, 'tx_amount' => $tx['amount'] ?? null]);
        return 'amount mismatch';
    }

    $paidAt = isset($tx['paid_at']) ? date('Y-m-d H:i:s', strtotime((string) $tx['paid_at'])) : date('Y-m-d H:i:s');
    try {
        q(
            "UPDATE payments SET status = 'success', paystack_id = ?, gateway_response = ?, card_last4 = ?, paid_at = ?
             WHERE id = ? AND status <> 'success'",
            [$tx['id'] ?? null, $gatewayResponse, $auth['last4'] ?? null, $paidAt, $payment['id']]
        );
    } catch (PDOException $ex) {
        if ($ex->getCode() !== '23000') {
            throw $ex;
        }
        // Student already had a successful payment for these dues: money was taken twice.
        q("UPDATE payments SET gateway_response = 'DUPLICATE: refund this payment in Paystack' WHERE id = ?", [$payment['id']]);
        app_log('payments', 'duplicate successful payment', ['reference' => $reference]);
        return 'duplicate payment — needs refund';
    }

    save_authorization((int) $payment['user_id'], $auth);
    return 'marked paid';
}

/** Store the reusable card token Paystack gave us (never card numbers). */
function save_authorization(int $userId, array $auth): void
{
    if (empty($auth['authorization_code']) || empty($auth['reusable']) || ($auth['channel'] ?? 'card') !== 'card') {
        return;
    }
    $signature = (string) ($auth['signature'] ?? $auth['authorization_code']);
    q('UPDATE payment_methods SET is_default = 0 WHERE user_id = ?', [$userId]);
    q(
        'INSERT INTO payment_methods (user_id, authorization_code, signature, card_type, bank, last4, exp_month, exp_year, is_default)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE authorization_code = VALUES(authorization_code), exp_month = VALUES(exp_month),
             exp_year = VALUES(exp_year), bank = VALUES(bank), is_default = 1',
        [
            $userId,
            $auth['authorization_code'],
            $signature,
            trim((string) ($auth['card_type'] ?? '')) ?: null,
            $auth['bank'] ?? null,
            (string) ($auth['last4'] ?? '0000'),
            str_pad((string) ($auth['exp_month'] ?? '01'), 2, '0', STR_PAD_LEFT),
            (string) ($auth['exp_year'] ?? '2000'),
        ]
    );
}

function status_label(string $status): string
{
    return match ($status) {
        'success'   => 'Paid',
        'pending'   => 'Processing',
        'failed'    => 'Failed',
        'abandoned' => 'Not completed',
        default     => ucfirst($status),
    };
}

/**
 * Ask Paystack about payments still marked 'pending' and apply the answer.
 *
 * This is the fallback for hosts that block incoming webhooks (InfinityFree and
 * other free hosts only let real browsers reach the site). Outgoing calls to
 * Paystack still work there, so we "pull" instead of waiting for a "push".
 * Called when a student opens their dashboard and from the admin overview.
 *
 * @return int number of payments whose status changed
 */
function reconcile_pending(?int $userId = null, int $limit = 5): int
{
    $sql = "SELECT reference, status FROM payments
            WHERE status = 'pending' AND created_at > (NOW() - INTERVAL 2 DAY)"
        . ($userId !== null ? ' AND user_id = ?' : '')
        . ' ORDER BY created_at DESC LIMIT ' . max(1, $limit);
    $rows = q($sql, $userId !== null ? [$userId] : [])->fetchAll();

    $changed = 0;
    foreach ($rows as $row) {
        try {
            apply_transaction(paystack_verify($row['reference']));
        } catch (PaystackException $ex) {
            // "Transaction reference not found": the student never reached Paystack's page.
            continue;
        }
        $now = q_value('SELECT status FROM payments WHERE reference = ?', [$row['reference']]);
        if ($now !== $row['status']) {
            $changed++;
        }
    }
    return $changed;
}
