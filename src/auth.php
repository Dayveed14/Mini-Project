<?php
declare(strict_types=1);

const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_WINDOW_MINUTES = 15;

/** The signed-in user, re-read from the database on each request so role changes apply at once. */
function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $user = q_one(
                'SELECT id, first_name, last_name, email, phone, reg_number, role, sms_pin_hash, created_at
                 FROM users WHERE id = ? AND is_active = 1',
                [$_SESSION['user_id']]
            );
            if ($user === null) {
                unset($_SESSION['user_id']);
            }
        }
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        flash('info', 'Sign in to continue.');
        redirect('login.php');
    }
    return $user;
}

/** Server-side role check. Every admin page calls require_role('admin'). */
function require_role(string $role): array
{
    $user = require_login();
    if ($user['role'] !== $role) {
        abort(403);
    }
    return $user;
}

function home_for(array $user): string
{
    return $user['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php';
}

function too_many_attempts(string $identifier): bool
{
    $since = date('Y-m-d H:i:s', time() - LOGIN_WINDOW_MINUTES * 60);
    $byIdentifier = (int) q_value(
        'SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND attempted_at > ?',
        [$identifier, $since]
    );
    $byIp = (int) q_value(
        'SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > ?',
        [client_ip(), $since]
    );
    return $byIdentifier >= MAX_LOGIN_ATTEMPTS || $byIp >= MAX_LOGIN_ATTEMPTS * 4;
}

/**
 * Check credentials. Returns the user row on success, null on failure.
 * Accepts email or registration number as the identifier.
 */
function attempt_login(string $identifier, string $password): ?array
{
    $identifier = strtolower(trim($identifier));
    $user = q_one(
        'SELECT id, role, password_hash FROM users
         WHERE (LOWER(email) = ? OR LOWER(reg_number) = ?) AND is_active = 1 LIMIT 1',
        [$identifier, $identifier]
    );

    // Verify against a dummy hash when the user doesn't exist, so timing doesn't reveal accounts.
    $hash = $user['password_hash'] ?? '$2y$10$AUXATPQILdUBn7FLt57hBOFZIuqh9R.kYKLFkxsYe4VRAdiC5BOTW';
    $ok = password_verify($password, $hash) && $user !== null;

    if (!$ok) {
        q('INSERT INTO login_attempts (identifier, ip_address) VALUES (?, ?)', [$identifier, client_ip()]);
        return null;
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }
    q('DELETE FROM login_attempts WHERE identifier = ?', [$identifier]);
    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);       // prevent session fixation
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    q('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Returns an error message, or null if the password is acceptable. */
function password_problem(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Use at least 8 characters for your password.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'Use at least one letter and one number in your password.';
    }
    return null;
}
