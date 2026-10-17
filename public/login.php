<?php
require __DIR__ . '/../src/bootstrap.php';

if ($user = current_user()) {
    redirect(home_for($user));
}

if (is_post()) {
    csrf_verify();
    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    remember_input(['identifier']);

    if ($identifier === '' || $password === '') {
        flash('error', 'Enter your registration number or email, and your password.');
        redirect('login.php');
    }
    if (too_many_attempts(strtolower($identifier))) {
        flash('error', 'Too many failed attempts. Wait 15 minutes, then try again.');
        redirect('login.php');
    }

    $user = attempt_login($identifier, $password);
    if ($user === null) {
        flash('error', 'That registration number, email or password is wrong.');
        redirect('login.php');
    }

    login_user($user);
    forget_input();
    redirect(home_for($user));
}

render('auth-shell', [
    'title'         => 'Sign in',
    'chrome'        => 'bare',
    'inlineFlashes' => true,
    'bodyClass'     => 'page-auth',
    'photo'         => 'gate.webp',
    'photoAlt'      => 'The arched entrance gate of the University of Uyo',
    'photoCaption'  => 'University of Uyo entrance gate',
    'formView'      => 'login-form',
]);
