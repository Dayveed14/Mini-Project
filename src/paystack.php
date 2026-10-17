<?php
declare(strict_types=1);

/*
 * Thin Paystack client. Docs: https://paystack.com/docs/api/
 *
 * Card details never touch this server: first payments go through Paystack's
 * hosted checkout, and repeat payments use the reusable authorization_code
 * Paystack returns after a successful card payment.
 */

final class PaystackException extends RuntimeException
{
}

function paystack_secret(): string
{
    $key = (string) env('PAYSTACK_SECRET_KEY', '');
    if ($key === '' || str_contains($key, 'replace_me')) {
        throw new PaystackException('PAYSTACK_SECRET_KEY is not set in .env');
    }
    return $key;
}

function paystack_request(string $method, string $path, ?array $body = null): array
{
    $url = rtrim((string) env('PAYSTACK_BASE_URL', 'https://api.paystack.co'), '/') . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . paystack_secret(),
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
    }

    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        app_log('paystack', 'network error', ['path' => $path, 'error' => $error]);
        throw new PaystackException('Could not reach Paystack. Check your internet connection and try again.');
    }

    $json = json_decode((string) $raw, true);
    if (!is_array($json)) {
        app_log('paystack', 'invalid JSON', ['path' => $path, 'status' => $status]);
        throw new PaystackException('Paystack returned an unexpected response.');
    }
    if ($status >= 400 || ($json['status'] ?? false) !== true) {
        app_log('paystack', 'API error', ['path' => $path, 'status' => $status, 'message' => $json['message'] ?? '']);
        throw new PaystackException((string) ($json['message'] ?? 'Paystack declined the request.'));
    }
    return $json['data'] ?? [];
}

/** Start a hosted checkout. Returns ['authorization_url' => ..., 'reference' => ...]. */
function paystack_initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $metadata): array
{
    return paystack_request('POST', '/transaction/initialize', [
        'email'        => $email,
        'amount'       => $amountKobo,
        'currency'     => 'NGN',
        'reference'    => $reference,
        'callback_url' => $callbackUrl,
        'channels'     => ['card'],   // card only, so we get a reusable authorization
        'metadata'     => $metadata,
    ]);
}

/** The source of truth for whether money moved. */
function paystack_verify(string $reference): array
{
    return paystack_request('GET', '/transaction/verify/' . rawurlencode($reference));
}

/** Charge a card the student saved earlier. */
function paystack_charge_authorization(string $authorizationCode, string $email, int $amountKobo, string $reference, array $metadata): array
{
    return paystack_request('POST', '/transaction/charge_authorization', [
        'authorization_code' => $authorizationCode,
        'email'              => $email,
        'amount'             => $amountKobo,
        'currency'           => 'NGN',
        'reference'          => $reference,
        'metadata'           => $metadata,
    ]);
}

/** Webhooks are signed with HMAC-SHA512 of the raw body using the secret key. */
function paystack_signature_valid(string $rawBody, string $signature): bool
{
    if ($signature === '') {
        return false;
    }
    return hash_equals(hash_hmac('sha512', $rawBody, paystack_secret()), $signature);
}
