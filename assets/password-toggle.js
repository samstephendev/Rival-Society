/**
 * THE RIVAL SOCIETY — Accessible Password Visibility Toggle
 * Progressive enhancement for all password inputs.
 * Features:
 * - Keyboard accessible
 * - Inline SVGs (zero external font dependencies)
 * - aria-label & aria-pressed state management
 * - Preserves native attributes (name, id, pattern, required, autocomplete)
 * - Auto-reverts to type="password" on form submit and window pageshow
 */

(function () {
  'use strict';

  const EYE_ICON = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
  const EYE_SLASH_ICON = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';

  function initPasswordToggles() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');

    passwordInputs.forEach((input) => {
      // Avoid duplicate enhancement
      if (input.dataset.hasPasswordToggle === 'true') {
        return;
      }
      input.dataset.hasPasswordToggle = 'true';

      // Create wrapper
      const wrapper = document.createElement('div');
      wrapper.className = 'password-field-wrapper';
      input.parentNode.insertBefore(wrapper, input);
      wrapper.appendChild(input);

      // Create toggle button
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'password-toggle-btn';
      button.setAttribute('aria-label', 'Show password');
      button.setAttribute('aria-pressed', 'false');
      button.innerHTML = EYE_ICON;

      wrapper.appendChild(button);

      button.addEventListener('click', (e) => {
        e.preventDefault();
        const isCurrentlyPassword = input.type === 'password';
        input.type = isCurrentlyPassword ? 'text' : 'password';
        button.setAttribute('aria-pressed', isCurrentlyPassword ? 'true' : 'false');
        button.setAttribute('aria-label', isCurrentlyPassword ? 'Hide password' : 'Show password');
        button.innerHTML = isCurrentlyPassword ? EYE_SLASH_ICON : EYE_ICON;
        input.focus();
      });

      // Reset to password on form submit
      const form = input.closest('form');
      if (form) {
        form.addEventListener('submit', () => {
          if (input.type !== 'password') {
            input.type = 'password';
            button.setAttribute('aria-pressed', 'false');
            button.setAttribute('aria-label', 'Show password');
            button.innerHTML = EYE_ICON;
          }
        });
      }
    });

    // Reset all password fields on back/forward cache navigation
    window.addEventListener('pageshow', () => {
      document.querySelectorAll('input[data-has-password-toggle="true"]').forEach((input) => {
        if (input.type !== 'password') {
          input.type = 'password';
          const btn = input.parentNode.querySelector('.password-toggle-btn');
          if (btn) {
            btn.setAttribute('aria-pressed', 'false');
            btn.setAttribute('aria-label', 'Show password');
            btn.innerHTML = EYE_ICON;
          }
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPasswordToggles);
  } else {
    initPasswordToggles();
  }
})();
