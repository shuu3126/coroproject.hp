(function () {
  const script = document.currentScript;
  const endpoint = script && script.dataset.socialEndpoint;
  if (!endpoint) return;

  const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  }[char]));

  const linkHtml = (link) => {
    const target = link.external ? ' target="_blank" rel="noopener"' : '';
    return `<li><a href="${escapeHtml(link.url)}"${target}>${escapeHtml(link.label)}</a></li>`;
  };

  const iconHtml = (link) => {
    const target = link.external ? ' target="_blank" rel="noopener"' : '';
    const icon = link.icon || link.label;
    return `<a href="${escapeHtml(link.url)}" aria-label="${escapeHtml(link.label)}"${target}>${escapeHtml(icon)}</a>`;
  };

  const isSocialHeading = (el) => el && el.textContent.trim().toLowerCase() === 'social';

  const applyLinks = (data) => {
    const links = Array.isArray(data.social_links) ? data.social_links : [];
    const xLink = links.find((link) => link.key === 'x');
    const mailLink = links.find((link) => link.key === 'mail');
    const mailAddress = data.mail || (mailLink && String(mailLink.url || '').replace(/^mailto:/, ''));

    document.querySelectorAll('.footer-socials').forEach((container) => {
      container.innerHTML = links.map(iconHtml).join('');
    });

    document.querySelectorAll('footer h4').forEach((heading) => {
      if (!isSocialHeading(heading)) return;
      const list = heading.parentElement && heading.parentElement.querySelector('ul');
      if (list) {
        list.innerHTML = links.map(linkHtml).join('');
      }
    });

    if (xLink && xLink.url) {
      document.querySelectorAll('a[href*="x.com/CoroProjectJP"], a[href*="twitter.com/CoroProjectJP"]').forEach((anchor) => {
        anchor.href = xLink.url;
        anchor.target = '_blank';
        anchor.rel = 'noopener';
      });
    }

    if (mailLink && mailLink.url) {
      document.querySelectorAll('a[href^="mailto:"]').forEach((anchor) => {
        anchor.href = mailLink.url;
        if (mailAddress && /info@coroproject\.jp/i.test(anchor.textContent.trim())) {
          anchor.textContent = mailAddress;
        }
      });
    }

    document.querySelectorAll('a').forEach((anchor) => {
      const label = anchor.textContent.trim().toLowerCase();
      const aria = String(anchor.getAttribute('aria-label') || '').toLowerCase();
      if (label === 'youtube' || label === 'twitch' || aria === 'youtube' || aria === 'twitch') {
        const item = anchor.closest('li');
        if (item) item.remove();
        else anchor.remove();
      }
    });
  };

  fetch(endpoint, { credentials: 'same-origin' })
    .then((response) => (response.ok ? response.json() : null))
    .then((data) => {
      if (data && data.ok) applyLinks(data);
    })
    .catch(() => {});
}());
