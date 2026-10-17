<?php
require __DIR__ . '/../src/bootstrap.php';

if ($user = current_user()) {
    redirect(home_for($user));
}

render('home', [
    'chrome'    => 'bare',
    'bodyClass' => 'page-home',
    'dues'      => active_dues(),
]);
