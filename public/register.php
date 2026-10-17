<?php
require __DIR__ . '/../src/bootstrap.php';

if ($user = current_user()) {
    redirect(home_for($user));
}

if (is_post()) {
    csrf_verify();
    $fields = ['first_name', 'last_name', 'reg_number', 'email', 'phone'];
    remember_input($fields);

    $first = trim((string) ($_POST['first_name'] ?? ''));
    $last = trim((string) ($_POST['last_name'] ?? ''));
    $reg = strtoupper(preg_replace('/\s+/', '', (string) ($_POST['reg_number'] ?? '')));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = normalize_phone((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');

    $errors = [];
    if ($first === '' || $last === '' || mb_strlen($first) > 60 || mb_strlen($last) > 60) {
        $errors[] = 'Enter your first and last name.';
    }
    if (!preg_match('#^[A-Z0-9/\-]{4,30}$#', $reg)) {
        $errors[] = 'Enter your registration number using letters, numbers and slashes, e.g. 21/SC/CS/123.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($phone === null) {
        $errors[] = 'Enter a Nigerian mobile number, e.g. 0803 123 4567.';
    }
    if ($problem = password_problem($password)) {
        $errors[] = $problem;
    }
    if ($password !== $confirm) {
        $errors[] = 'The two passwords don\'t match.';
    }

    if (!$errors) {
        $taken = q_one(
            'SELECT email, phone, reg_number FROM users WHERE email = ? OR phone = ? OR reg_number = ? LIMIT 1',
            [$email, $phone, $reg]
        );
        if ($taken) {
            $errors[] = 'An account already uses that registration number, email or phone number. Sign in instead, or contact the union office.';
        }
    }

    if ($errors) {
        foreach ($errors as $err) {
            flash('error', $err);
        }
        redirect('register.php');
    }

    // Role is always 'student' here. Admin accounts are created with bin/create-admin.php.
    q(
        "INSERT INTO users (first_name, last_name, email, phone, reg_number, role, password_hash)
         VALUES (?, ?, ?, ?, ?, 'student', ?)",
        [$first, $last, $email, $phone, $reg, password_hash($password, PASSWORD_DEFAULT)]
    );
    $user = q_one('SELECT id, role FROM users WHERE id = ?', [db()->lastInsertId()]);
    login_user($user);
    forget_input();
    flash('success', 'Your account is ready. Pay your dues below.');
    redirect('dashboard.php');
}

render('auth-shell', [
    'title'         => 'Create account',
    'chrome'        => 'bare',
    'inlineFlashes' => true,
    'bodyClass'     => 'page-auth',
    'photo'         => 'students.webp',
    'photoAlt'      => 'Nigerian university students seated together at a library event',
    'photoCaption'  => 'Students at a library event, University of Ibadan',
    'formView'      => 'register-form',
]);
