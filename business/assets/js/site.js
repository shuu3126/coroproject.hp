(() => {
  const header = document.getElementById('site-header');
  const reveals = document.querySelectorAll('.reveal');
  const onScroll = () => {
    if (header) header.classList.toggle('is-scrolled', window.scrollY > 10);
  };
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });
  reveals.forEach((el) => io.observe(el));
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
})();