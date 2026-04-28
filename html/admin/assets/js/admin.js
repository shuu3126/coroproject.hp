document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('submit', (e) => {
      const msg = el.getAttribute('data-confirm') || 'この操作を実行しますか？';
      if (!window.confirm(msg)) {
        e.preventDefault();
      }
    });
  });
});
