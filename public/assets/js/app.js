// Small progressive enhancements. Every page works without JavaScript.
document.addEventListener('DOMContentLoaded', () => {
  // Print buttons
  document.querySelectorAll('[data-print]').forEach((btn) => {
    btn.addEventListener('click', () => window.print());
  });

  // Confirm before charging a card or removing one; then block double submits.
  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const message = form.getAttribute('data-confirm');
      if (message && !window.confirm(message)) {
        event.preventDefault();
        return;
      }
      if (form.method.toLowerCase() === 'post') {
        // Defer so the clicked button's name/value is still sent with the form.
        setTimeout(() => {
          form.querySelectorAll('button[type="submit"]').forEach((b) => {
            b.disabled = true;
            b.setAttribute('aria-busy', 'true');
          });
        }, 0);
      }
    });
  });

  // Mobile menu
  const toggle = document.querySelector('.nav-toggle');
  const nav = document.getElementById('site-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open));
      nav.classList.toggle('is-open', !open);
    });
  }
});
