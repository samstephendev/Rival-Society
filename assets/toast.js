/**
 * THE RIVAL SOCIETY — Toast Notification Component
 * Reusable, accessible (aria-live="polite"), stacking, auto-dismiss ~4s, mobile-friendly
 */

(function () {
  'use strict';

  function getToastContainer() {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.setAttribute('aria-live', 'polite');
      container.setAttribute('aria-atomic', 'false');
      container.setAttribute('role', 'region');
      container.setAttribute('aria-label', 'Notifications');
      document.body.appendChild(container);
    }
    return container;
  }

  function showToast(message, type = 'info', duration = 4000) {
    if (!message) return null;

    const container = getToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

    const iconSvg = type === 'error' 
      ? '<svg class="toast-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>'
      : '<svg class="toast-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="9 11 12 14 22 4"></polyline></svg>';

    const content = document.createElement('div');
    content.className = 'toast-content';
    content.innerHTML = `${iconSvg}<span>${escapeHtml(message)}</span>`;

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'toast-close';
    closeBtn.setAttribute('aria-label', 'Close notification');
    closeBtn.innerHTML = '&times;';

    toast.appendChild(content);
    toast.appendChild(closeBtn);
    container.appendChild(toast);

    // Trigger enter animation
    requestAnimationFrame(() => {
      toast.classList.add('toast-visible');
    });

    let dismissTimer = null;
    if (duration > 0) {
      dismissTimer = setTimeout(() => {
        dismissToast(toast);
      }, duration);
    }

    closeBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (dismissTimer) clearTimeout(dismissTimer);
      dismissToast(toast);
    });

    return toast;
  }

  function dismissToast(toast) {
    if (!toast || toast.classList.contains('toast-hiding')) return;
    toast.classList.add('toast-hiding');
    toast.classList.remove('toast-visible');
    setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 250);
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  window.showToast = showToast;
})();
