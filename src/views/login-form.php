<h1 class="page-title">Sign in</h1>
<p class="muted">Students and union officers use the same sign-in.</p>

<form method="post" action="<?= e(url('login.php')) ?>" class="form" novalidate>
  <?= csrf_field() ?>
  <div class="field">
    <label for="identifier">Registration number or email</label>
    <input id="identifier" name="identifier" type="text" autocomplete="username" required value="<?= old('identifier') ?>" autofocus>
  </div>
  <div class="field">
    <label for="password">Password</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
  </div>
  <button class="btn btn-primary btn-block" type="submit">Sign in</button>
</form>

<p class="form-switch">New here? <a href="<?= e(url('register.php')) ?>">Create a student account</a></p>
