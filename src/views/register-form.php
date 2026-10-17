<h1 class="page-title">Create your student account</h1>
<p class="muted">Use the phone number you'll pay from by SMS. You can change it later.</p>

<form method="post" action="<?= e(url('register.php')) ?>" class="form" novalidate>
  <?= csrf_field() ?>
  <div class="field-row">
    <div class="field">
      <label for="first_name">First name</label>
      <input id="first_name" name="first_name" type="text" autocomplete="given-name" required maxlength="60" value="<?= old('first_name') ?>">
    </div>
    <div class="field">
      <label for="last_name">Last name</label>
      <input id="last_name" name="last_name" type="text" autocomplete="family-name" required maxlength="60" value="<?= old('last_name') ?>">
    </div>
  </div>
  <div class="field">
    <label for="reg_number">Registration number</label>
    <input id="reg_number" name="reg_number" type="text" required maxlength="30" placeholder="e.g. 21/SC/CS/123" value="<?= old('reg_number') ?>">
  </div>
  <div class="field">
    <label for="email">Email</label>
    <input id="email" name="email" type="email" autocomplete="email" required maxlength="190" value="<?= old('email') ?>">
    <p class="hint">Paystack sends your payment receipt here.</p>
  </div>
  <div class="field">
    <label for="phone">Phone number</label>
    <input id="phone" name="phone" type="tel" autocomplete="tel" required placeholder="0803 123 4567" value="<?= old('phone') ?>">
  </div>
  <div class="field-row">
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8">
      <p class="hint">At least 8 characters, with a letter and a number.</p>
    </div>
    <div class="field">
      <label for="password_confirm">Confirm password</label>
      <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required>
    </div>
  </div>
  <button class="btn btn-primary btn-block" type="submit">Create account</button>
</form>

<p class="form-switch">Already registered? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
