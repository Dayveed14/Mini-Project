<?php /** @var array $user */ ?>
<div class="wrap page narrow">
  <header class="page-head">
    <h1 class="page-title">Account</h1>
    <p class="muted">
      <?= e($user['first_name'] . ' ' . $user['last_name']) ?>,
      <?= $user['reg_number'] ? e($user['reg_number']) . ', ' : '' ?><?= e($user['email']) ?>
    </p>
  </header>

  <section class="panel">
    <h2 class="panel-title">Phone number</h2>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="phone">
      <div class="field">
        <label for="phone">Mobile number</label>
        <input id="phone" name="phone" type="tel" required value="<?= e(format_phone($user['phone'])) ?>">
        <?php if ($user['role'] === 'student'): ?><p class="hint">PAY texts are only accepted from this number.</p><?php endif; ?>
      </div>
      <div class="field">
        <label for="cp-phone">Current password</label>
        <input id="cp-phone" name="current_password" type="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-primary" type="submit">Save phone number</button>
    </form>
  </section>

  <?php if ($user['role'] === 'student'): ?>
  <section class="panel" id="sms-pin">
    <h2 class="panel-title">SMS PIN</h2>
    <p class="muted">
      Paying by text needs your PIN, so nobody can charge your card by faking your number.
      <?= $user['sms_pin_hash'] ? 'You have a PIN set.' : 'You haven\'t set a PIN yet.' ?>
    </p>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="pin">
      <div class="field-row">
        <div class="field">
          <label for="pin">New 4-digit PIN</label>
          <input id="pin" name="pin" type="password" inputmode="numeric" pattern="\d{4}" maxlength="4" autocomplete="off" required>
        </div>
        <div class="field">
          <label for="pin_confirm">Repeat PIN</label>
          <input id="pin_confirm" name="pin_confirm" type="password" inputmode="numeric" pattern="\d{4}" maxlength="4" autocomplete="off" required>
        </div>
      </div>
      <div class="field">
        <label for="cp-pin">Current password</label>
        <input id="cp-pin" name="current_password" type="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-primary" type="submit"><?= $user['sms_pin_hash'] ? 'Change PIN' : 'Set PIN' ?></button>
    </form>
    <?php if ($user['sms_pin_hash']): ?>
      <details class="disclose">
        <summary>Turn off paying by SMS</summary>
        <form method="post" class="form form-inline">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="pin_remove">
          <div class="field">
            <label for="cp-rm">Current password</label>
            <input id="cp-rm" name="current_password" type="password" autocomplete="current-password" required>
          </div>
          <button class="btn btn-quiet danger" type="submit">Remove PIN</button>
        </form>
      </details>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <section class="panel">
    <h2 class="panel-title">Password</h2>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="password">
      <div class="field">
        <label for="cp-pw">Current password</label>
        <input id="cp-pw" name="current_password" type="password" autocomplete="current-password" required>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="new_password">New password</label>
          <input id="new_password" name="new_password" type="password" autocomplete="new-password" required minlength="8">
        </div>
        <div class="field">
          <label for="new_password_confirm">Repeat new password</label>
          <input id="new_password_confirm" name="new_password_confirm" type="password" autocomplete="new-password" required>
        </div>
      </div>
      <button class="btn btn-primary" type="submit">Change password</button>
    </form>
  </section>
</div>
