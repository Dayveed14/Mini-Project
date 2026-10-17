<?php
/*
 * Create (or reset) a union officer's admin account. Admins can't sign up through the website.
 *
 *   php bin/create-admin.php
 *
 * On XAMPP for Windows:  C:\xampp\php\php.exe bin\create-admin.php
 */
if (PHP_SAPI !== 'cli') {
    exit("Run this from the command line.\n");
}
require __DIR__ . '/../src/bootstrap.php';

function ask(string $prompt, bool $hidden = false): string
{
    echo $prompt;
    if ($hidden && DIRECTORY_SEPARATOR === '/') {
        system('stty -echo');
        $value = trim((string) fgets(STDIN));
        system('stty echo');
        echo "\n";
        return $value;
    }
    return trim((string) fgets(STDIN));
}

$opts = getopt('', ['first:', 'last:', 'email:', 'phone:', 'password:']);

$first = $opts['first'] ?? ask('First name: ');
$last = $opts['last'] ?? ask('Last name: ');
$email = strtolower($opts['email'] ?? ask('Email: '));
$phone = normalize_phone($opts['phone'] ?? ask('Phone (e.g. 08031234567): '));
$password = $opts['password'] ?? ask('Password (min 8 chars, letter + number): ', true);

if ($first === '' || $last === '') {
    exit("First and last name are required.\n");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("That email address isn't valid.\n");
}
if ($phone === null) {
    exit("Enter a Nigerian mobile number.\n");
}
if ($problem = password_problem($password)) {
    exit($problem . "\n");
}

$existing = q_one('SELECT id, role FROM users WHERE email = ?', [$email]);
if ($existing) {
    q("UPDATE users SET role = 'admin', password_hash = ?, is_active = 1 WHERE id = ?", [password_hash($password, PASSWORD_DEFAULT), $existing['id']]);
    echo "Updated {$email}: now an admin with the new password.\n";
} else {
    q(
        "INSERT INTO users (first_name, last_name, email, phone, role, password_hash) VALUES (?, ?, ?, ?, 'admin', ?)",
        [$first, $last, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]
    );
    echo "Created admin account for {$email}. Sign in with that email.\n";
}
