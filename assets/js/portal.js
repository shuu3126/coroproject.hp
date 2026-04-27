(function () {
  const header = document.querySelector('.site-header');
  const toggle = document.querySelector('[data-menu-toggle]');
  const mobileNav = document.querySelector('[data-mobile-nav]');
  const revealItems = document.querySelectorAll('.reveal');

  function updateHeader() {
    if (!header) return;
    header.classList.toggle('is-scrolled', window.scrollY > 40);
  }

  if (toggle && mobileNav) {
    toggle.addEventListener('click', function () {
      mobileNav.classList.toggle('is-open');
    });

    mobileNav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        mobileNav.classList.remove('is-open');
      });
    });
  }

  const observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });

  revealItems.forEach(function (item) {
    observer.observe(item);
  });

  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });
})();
