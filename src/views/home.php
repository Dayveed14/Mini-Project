<?php /** @var array|null $dues */ ?>
<section class="hero">
  <img class="hero-photo" src="<?= e(asset('img/hero.webp')) ?>" alt="Students outside the University of Uyo Town Campus entrance" width="1600" height="900">
  <div class="hero-shade" aria-hidden="true"></div>

  <div class="hero-inner">
    <header class="hero-bar">
      <a class="brand brand-light" href="<?= e(url()) ?>">
        <img src="<?= e(asset('img/seal.png')) ?>" alt="" width="40" height="43">
        <span>UniUyo Dues</span>
      </a>
      <a class="link-light" href="<?= e(url('login.php')) ?>">Sign in</a>
    </header>

    <div class="hero-grid">
      <div class="hero-copy">
        <h1>Pay your union dues without the queue.</h1>
        <p class="lede">
          Pay once with your card through Paystack and get a receipt you can print or show on your phone.
          After that, a text message is enough.
        </p>
        <div class="actions">
          <a class="btn btn-primary btn-lg" href="<?= e(url('register.php')) ?>">Create a student account</a>
          <a class="btn btn-ghost-light btn-lg" href="<?= e(url('login.php')) ?>">Sign in</a>
        </div>
      </div>

      <?php if ($dues): ?>
        <div class="hero-ticket">
          <?php $ticketDues = $dues; $ticketPayment = null; $ticketUser = null; require __DIR__ . '/partials/ticket.php'; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="steps" aria-labelledby="how">
  <div class="wrap">
    <h2 id="how" class="section-title">How paying works</h2>
    <ol class="step-list">
      <li>
        <h3>Create your account</h3>
        <p>Use your registration number and the phone number you'll text from.</p>
      </li>
      <li>
        <h3>Pay with your card</h3>
        <p>You enter card details on Paystack's secure page, not on this site. Your receipt is ready as soon as the payment clears.</p>
      </li>
      <li>
        <h3>Next time, text PAY</h3>
        <p>Set a 4-digit SMS PIN, then send <strong>PAY</strong> and your PIN from your registered number. We charge your saved card and text you back.</p>
      </li>
    </ol>
  </div>
</section>

<section class="band">
  <div class="wrap band-grid">
    <img class="band-photo" src="<?= e(asset('img/sculpture.webp')) ?>" alt="A sculpture in front of faculty buildings at the University of Uyo" width="900" height="1200" loading="lazy">
    <div class="band-copy">
      <h2 class="section-title">Your card details stay with Paystack</h2>
      <p>This portal never sees or stores your card number or CVV. Paystack, a licensed Nigerian payment processor, keeps the card and gives the union a token that can only charge the dues you approve.</p>
      <p>Every payment is checked with Paystack before it is marked paid, so a receipt here means the money really arrived.</p>
      <p class="band-admin">Union officers: sign in with your admin account to see who has paid and export records.</p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
