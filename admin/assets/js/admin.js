document.addEventListener('DOMContentLoaded', () => {
  const sessionConfig = window.CORO_ADMIN_SESSION || {};

  if (sessionConfig.timeoutMs && sessionConfig.logoutUrl) {
    const timeoutMs = Math.max(Number(sessionConfig.timeoutMs) || 0, 1000);
    const touchIntervalMs = Math.min(5 * 60 * 1000, Math.max(60 * 1000, Math.floor(timeoutMs / 4)));
    let logoutTimer = null;
    let lastTouchAt = 0;
    let touchInFlight = false;

    const logout = () => {
      window.location.href = sessionConfig.logoutUrl;
    };

    const resetLogoutTimer = () => {
      window.clearTimeout(logoutTimer);
      logoutTimer = window.setTimeout(logout, timeoutMs);
    };

    const touchSession = () => {
      const now = Date.now();
      if (!sessionConfig.touchUrl || touchInFlight || now - lastTouchAt < touchIntervalMs) {
        return;
      }

      lastTouchAt = now;
      touchInFlight = true;

      window.fetch(sessionConfig.touchUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      })
        .then((response) => {
          if (!response.ok) {
            logout();
            return null;
          }
          const contentType = response.headers.get('content-type') || '';
          return contentType.includes('application/json') ? response.json() : null;
        })
        .then((data) => {
          if (data && data.ok === false) {
            logout();
          }
        })
        .catch(() => {})
        .finally(() => {
          touchInFlight = false;
        });
    };

    const markActivity = () => {
      resetLogoutTimer();
      touchSession();
    };

    ['click', 'keydown', 'mousemove', 'scroll', 'touchstart', 'focus'].forEach((eventName) => {
      window.addEventListener(eventName, markActivity, { passive: true });
    });

    resetLogoutTimer();
  }

  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('submit', (e) => {
      const msg = el.getAttribute('data-confirm') || 'この操作を実行しますか？';
      if (!window.confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  document.querySelectorAll('.nav-section-toggle').forEach((btn) => {
    btn.addEventListener('click', () => {
      const items = btn.nextElementSibling;
      if (!items || !items.classList.contains('nav-section-items')) {
        return;
      }
      const isOpen = items.classList.contains('open');
      document.querySelectorAll('.nav-section-items').forEach((el) => el.classList.remove('open'));
      document.querySelectorAll('.nav-section-toggle').forEach((el) => el.classList.remove('open'));
      if (!isOpen) {
        items.classList.add('open');
        btn.classList.add('open');
      }
    });
  });
});
