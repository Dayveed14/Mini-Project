<?php
/** @var string $content */
$user = current_user();
$pageTitle = isset($title) ? $title . ' — ' . env('APP_NAME', 'UniUyo Dues') : env('APP_NAME', 'UniUyo Dues');
$bodyClass = $bodyClass ?? '';
$active = $active ?? '';
$chrome = $chrome ?? 'app';   // app | bare
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="Pay University of Uyo Students' Union dues online with your card or by SMS.">
  <link rel="icon" type="image/png" href="<?= e(asset('img/favicon.png')) ?>">
  <link rel="preload" href="<?= e(url('assets/fonts/bricolage-grotesque-latin-wght-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#main">Skip to content</a>

<?php if ($chrome === 'app' && $user): ?>
  <header class="topbar">
    <div class="topbar-inner">
      <a class="brand" href="<?= e(url(home_for($user))) ?>">
        <img src="<?= e(asset('img/seal.png')) ?>" alt="" width="36" height="38">
        <span>UniUyo Dues</span>
      </a>
      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">Menu</button>
      <nav id="site-nav" class="nav" aria-label="Main">
        <?php if ($user['role'] === 'admin'): ?>
          <a href="<?= e(url('admin/index.php')) ?>" <?= $active === 'overview' ? 'aria-current="page"' : '' ?>>Overview</a>
          <a href="<?= e(url('admin/payments.php')) ?>" <?= $active === 'payments' ? 'aria-current="page"' : '' ?>>Payments</a>
          <a href="<?= e(url('admin/students.php')) ?>" <?= $active === 'students' ? 'aria-current="page"' : '' ?>>Students</a>
          <a href="<?= e(url('admin/dues.php')) ?>" <?= $active === 'dues' ? 'aria-current="page"' : '' ?>>Dues</a>
        <?php else: ?>
          <a href="<?= e(url('dashboard.php')) ?>" <?= $active === 'dashboard' ? 'aria-current="page"' : '' ?>>My dues</a>
        <?php endif; ?>
        <a href="<?= e(url('account.php')) ?>" <?= $active === 'account' ? 'aria-current="page"' : '' ?>>Account</a>
        <form method="post" action="<?= e(url('logout.php')) ?>" class="nav-signout">
          <?= csrf_field() ?>
          <button type="submit">Sign out</button>
        </form>
      </nav>
    </div>
  </header>
<?php endif; ?>

<main id="main">
  <?php if (empty($inlineFlashes)) { require __DIR__ . '/partials/flashes.php'; } ?>
  <?= $content ?>
</main>
</body>
</html>
<?php forget_input(); ?>
