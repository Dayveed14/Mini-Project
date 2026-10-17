<?php
declare(strict_types=1);

/** HTML-escape. Use for every value printed into a page. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(): string
{
    return rtrim((string) env('APP_URL', ''), '/');
}

/** Absolute URL to a path inside /public. */
function url(string $path = ''): string
{
    return app_url() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ROOT_DIR . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return url('assets/' . ltrim($path, '/')) . '?v=' . $version;
}

function redirect(string $path): never
{
    $target = preg_match('#^https?://#', $path) ? $path : url($path);
    header('Location: ' . $target, true, 303);
    exit;
}

function abort(int $status, string $message = ''): never
{
    http_response_code($status);
    $title = match ($status) {
        403 => 'You don\'t have access to this page',
        404 => 'Page not found',
        405 => 'Method not allowed',
        419 => 'This form expired',
        429 => 'Too many attempts',
        default => 'Something went wrong',
    };
    render('error', ['title' => $title, 'status' => $status, 'message' => $message]);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function require_post(): void
{
    if (!is_post()) {
        abort(405);
    }
    csrf_verify();
}

/** Render src/views/{name}.php inside the shared layout. */
function render(string $view, array $data = [], string $layout = 'layout'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require ROOT_DIR . '/src/views/' . $view . '.php';
    $content = ob_get_clean();
    if ($layout === '') {
        echo $content;
        return;
    }
    require ROOT_DIR . '/src/views/' . $layout . '.php';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Remember submitted form values (never passwords) for re-display after a redirect. */
function remember_input(array $fields): void
{
    $_SESSION['old'] = array_intersect_key($_POST, array_flip($fields));
}

function old(string $field, string $default = ''): string
{
    return e($_SESSION['old'][$field] ?? $default);
}

function forget_input(): void
{
    unset($_SESSION['old']);
}

function naira(int $kobo): string
{
    $naira = $kobo / 100;
    return '₦' . number_format($naira, fmod($naira, 1.0) === 0.0 ? 0 : 2);
}

/**
 * Normalise a Nigerian mobile number to E.164 (+234XXXXXXXXXX).
 * Returns null if it doesn't look like a Nigerian mobile number.
 */
function normalize_phone(string $raw): ?string
{
    $digits = preg_replace('/\D+/', '', $raw);
    if (str_starts_with($digits, '234')) {
        $digits = substr($digits, 3);
    } elseif (str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }
    return preg_match('/^[789][01]\d{8}$/', $digits) ? '+234' . $digits : null;
}

function format_phone(string $e164): string
{
    return preg_replace('/^\+234(\d{3})(\d{3})(\d{4})$/', '0$1 $2 $3', $e164) ?? $e164;
}

function client_ip(): string
{
    // Only REMOTE_ADDR is trustworthy unless you sit behind a proxy you control.
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function new_reference(string $prefix = 'UYO'): string
{
    return $prefix . '-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(6)));
}

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    // form-action must allow Paystack: Chrome applies it to the redirect after our POST.
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; "
        . "style-src 'self'; font-src 'self'; "
        . "script-src 'self'; frame-ancestors 'none'; base-uri 'self'; "
        . "form-action 'self' https://checkout.paystack.com");
    if (env('APP_ENV') === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function start_secure_session(bool $secure): void
{
    session_name('uyodues');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => parse_url(app_url(), PHP_URL_PATH) ?: '/',
        'secure'   => $secure || (($_SERVER['HTTPS'] ?? '') === 'on'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();

    // Sign out after 30 minutes of inactivity.
    $now = time();
    if (isset($_SESSION['user_id'], $_SESSION['last_seen']) && $now - $_SESSION['last_seen'] > 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('info', 'You were signed out after 30 minutes of inactivity.');
    }
    $_SESSION['last_seen'] = $now;
}

function app_log(string $channel, string $message, array $context = []): void
{
    $line = sprintf("[%s] %s: %s %s\n", date('c'), $channel, $message, $context ? json_encode($context) : '');
    @file_put_contents(ROOT_DIR . '/storage/logs/app.log', $line, FILE_APPEND | LOCK_EX);
}

/** Current page URL with some query parameters changed (for filters and pagination). */
function query_url(array $overrides): string
{
    $params = array_filter(array_merge($_GET, $overrides), static fn ($v) => $v !== null && $v !== '');
    $path = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    return $path . ($params ? '?' . http_build_query($params) : '');
}
