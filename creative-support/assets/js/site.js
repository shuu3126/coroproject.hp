(() => {
  const header = document.getElementById('site-header');
  const toggle = document.querySelector('[data-menu-toggle]');
  const mobileNav = document.querySelector('[data-mobile-nav]');
  const reveals = document.querySelectorAll('.reveal');
  const faqButtons = document.querySelectorAll('[data-faq]');

  const updateHeader = () => {
    if (header) {
      header.classList.toggle('is-scrolled', window.scrollY > 14);
    }
  };

  if (toggle && mobileNav) {
    toggle.addEventListener('click', () => {
      mobileNav.classList.toggle('is-open');
    });

    mobileNav.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        mobileNav.classList.remove('is-open');
      });
    });
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  reveals.forEach((item) => observer.observe(item));

  faqButtons.forEach((button) => {
    button.addEventListener('click', () => {
      button.classList.toggle('is-open');
      const icon = button.querySelector('b');
      if (icon) icon.textContent = button.classList.contains('is-open') ? '-' : '+';
    });
  });

  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });
})();
