<?php
require __DIR__ . '/../src/bootstrap.php';

require_post();          // POST + CSRF, so a link or image can't sign people out
logout_user();
session_start();
flash('info', 'You\'re signed out.');
redirect('login.php');
