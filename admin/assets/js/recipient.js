/* Recipient chip input + contacts picker modal
   Requires: window._MAIL_CONTACTS set before DOMContentLoaded
   Format: [{name, email, company, source_label}, ...]
*/
(function () {
  'use strict';

  function esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function makeChipInput(wrap) {
    var hidden = wrap.querySelector('input[type="hidden"]');
    var textIn = wrap.querySelector('.recipient-text-input');
    var drop   = wrap.querySelector('.recipient-ac-dropdown');
    var selected = [];
    var focusIdx = -1;

    // Pre-populate from existing hidden value (e.g. reply-to)
    var init = (hidden.value || '').trim();
    if (init) {
      init.split(',').map(function(s) { return s.trim(); }).filter(Boolean).forEach(function(addr) {
        var m = addr.match(/^(.*?)\s*<([^>]+)>$/);
        if (m) pushChip(m[1].trim() || m[2].trim(), m[2].trim());
        else if (addr.indexOf('@') !== -1) pushChip(addr, addr);
      });
    }

    function render() {
      wrap.querySelectorAll('.recipient-chip').forEach(function(c) { c.remove(); });
      selected.forEach(function(item, i) {
        var chip = document.createElement('span');
        chip.className = 'recipient-chip';
        chip.title = item.email;
        chip.innerHTML =
          '<span>' + esc(item.label) + '</span>' +
          '<button type="button" class="recipient-chip-x" data-i="' + i + '">×</button>';
        wrap.insertBefore(chip, textIn);
      });
      hidden.value = selected.map(function(s) { return s.email; }).join(', ');
    }

    function pushChip(label, email) {
      if (!email || email.indexOf('@') === -1) return;
      if (selected.some(function(s) { return s.email.toLowerCase() === email.toLowerCase(); })) return;
      selected.push({ label: label || email, email: email });
      render();
      textIn.value = '';
      closeDrop();
    }

    function removeChip(i) {
      selected.splice(i, 1);
      render();
    }

    function showDrop(list) {
      drop.innerHTML = '';
      focusIdx = -1;
      if (!list.length) { closeDrop(); return; }
      list.forEach(function(c) {
        var el = document.createElement('div');
        el.className = 'recipient-ac-item';
        el.innerHTML =
          '<span class="recipient-ac-name">' + esc(c.name || c.email) + '</span>' +
          '<span class="recipient-ac-email">' + esc(c.email) + '</span>' +
          (c.source_label ? '<span class="recipient-ac-source">' + esc(c.source_label) + '</span>' : '');
        el.addEventListener('mousedown', function(e) {
          e.preventDefault();
          pushChip(c.name || c.email, c.email);
        });
        drop.appendChild(el);
      });
      drop.classList.add('open');
    }

    function closeDrop() {
      drop.classList.remove('open');
      focusIdx = -1;
    }

    function moveFocus(dir) {
      var els = drop.querySelectorAll('.recipient-ac-item');
      if (!els.length) return;
      if (focusIdx >= 0) els[focusIdx].classList.remove('focused');
      focusIdx = Math.max(0, Math.min(els.length - 1, focusIdx + dir));
      els[focusIdx].classList.add('focused');
      els[focusIdx].scrollIntoView({ block: 'nearest' });
    }

    textIn.addEventListener('input', function() {
      var q = textIn.value.toLowerCase().trim();
      if (!q) { closeDrop(); return; }
      var CONTACTS = window._MAIL_CONTACTS || [];
      var matches = CONTACTS.filter(function(c) {
        return (c.name && c.name.toLowerCase().indexOf(q) !== -1) ||
               c.email.toLowerCase().indexOf(q) !== -1 ||
               (c.company && c.company.toLowerCase().indexOf(q) !== -1);
      }).slice(0, 12);
      showDrop(matches);
    });

    textIn.addEventListener('keydown', function(e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); moveFocus(1); return; }
      if (e.key === 'ArrowUp')   { e.preventDefault(); moveFocus(-1); return; }
      if (e.key === 'Escape')    { closeDrop(); return; }
      if (e.key === 'Enter' || e.key === ',') {
        var focused = drop.querySelector('.recipient-ac-item.focused');
        if (focused) {
          e.preventDefault();
          focused.dispatchEvent(new MouseEvent('mousedown'));
          return;
        }
        if (e.key === ',') {
          var raw = textIn.value.replace(',', '').trim();
          if (raw) { e.preventDefault(); pushChip(raw, raw); }
        }
      }
      if (e.key === 'Backspace' && textIn.value === '' && selected.length) {
        selected.pop(); render();
      }
    });

    textIn.addEventListener('blur', function() {
      setTimeout(closeDrop, 160);
      var raw = textIn.value.trim();
      if (raw && raw.indexOf('@') !== -1) pushChip(raw, raw);
    });

    wrap.addEventListener('click', function(e) {
      var x = e.target.closest('.recipient-chip-x');
      if (x) { removeChip(parseInt(x.dataset.i, 10)); return; }
      textIn.focus();
    });

    // Public API for picker modal and openCompose()
    wrap._addEmail = function(name, email) { pushChip(name || email, email); };
    wrap._hasEmail = function(email) {
      return selected.some(function(s) { return s.email.toLowerCase() === email.toLowerCase(); });
    };
    wrap._clear = function() { selected = []; render(); };
    wrap._setOne = function(addr) {
      selected = [];
      var m = addr.trim().match(/^(.*?)\s*<([^>]+)>$/);
      if (m) pushChip(m[1].trim() || m[2].trim(), m[2].trim());
      else if (addr.indexOf('@') !== -1) pushChip(addr, addr);
    };
  }

  // ── Contacts picker modal ────────────────────────────────────
  function initPicker() {
    var modal = document.getElementById('contacts-picker-modal');
    if (!modal) return;

    var listEl   = modal.querySelector('.contacts-picker-list');
    var searchEl = modal.querySelector('.contacts-picker-search input');
    var countEl  = modal.querySelector('.contacts-picker-selected-count');
    var targetWrap = null;

    window._openContactsPicker = function(wrap) {
      targetWrap = wrap;
      searchEl.value = '';
      renderList('');
      modal.classList.add('open');
      setTimeout(function() { searchEl.focus(); }, 50);
    };

    function closePicker() { modal.classList.remove('open'); }

    modal.addEventListener('click', function(e) {
      if (e.target === modal) closePicker();
    });
    modal.querySelectorAll('.contacts-picker-close-btn').forEach(function(btn) {
      btn.addEventListener('click', closePicker);
    });

    var addBtn = modal.querySelector('.contacts-picker-add-btn');
    if (addBtn) {
      addBtn.addEventListener('click', function() {
        if (!targetWrap) return;
        listEl.querySelectorAll('input[type="checkbox"]:checked').forEach(function(cb) {
          targetWrap._addEmail(cb.dataset.name, cb.dataset.email);
        });
        closePicker();
      });
    }

    searchEl.addEventListener('input', function() {
      renderList(searchEl.value.trim());
    });
    listEl.addEventListener('change', updateCount);

    function renderList(q) {
      var CONTACTS = window._MAIL_CONTACTS || [];
      var list = q
        ? CONTACTS.filter(function(c) {
            var lq = q.toLowerCase();
            return (c.name && c.name.toLowerCase().indexOf(lq) !== -1) ||
                   c.email.toLowerCase().indexOf(lq) !== -1 ||
                   (c.company && c.company.toLowerCase().indexOf(lq) !== -1);
          })
        : CONTACTS;

      listEl.innerHTML = '';
      if (!list.length) {
        listEl.innerHTML = '<div style="padding:24px;text-align:center;color:var(--sub);font-size:.85em;">一致する宛先がありません</div>';
        updateCount();
        return;
      }
      list.forEach(function(c) {
        var row = document.createElement('label');
        row.className = 'contacts-picker-row';
        var already = targetWrap && targetWrap._hasEmail && targetWrap._hasEmail(c.email);
        row.innerHTML =
          '<input type="checkbox" data-name="' + esc(c.name || '') + '" data-email="' + esc(c.email) + '"' + (already ? ' checked' : '') + '>' +
          '<div class="contacts-picker-row-info">' +
            '<div class="contacts-picker-name">' + esc(c.name || c.email) +
              (c.company ? ' <span style="font-weight:400;color:var(--sub)">— ' + esc(c.company) + '</span>' : '') +
            '</div>' +
            '<div class="contacts-picker-email">' + esc(c.email) + '</div>' +
            (c.source_label ? '<div class="contacts-picker-source">' + esc(c.source_label) + '</div>' : '') +
          '</div>';
        listEl.appendChild(row);
      });
      updateCount();
    }

    function updateCount() {
      if (!countEl) return;
      var n = listEl.querySelectorAll('input[type="checkbox"]:checked').length;
      countEl.textContent = n ? n + '件選択中' : '';
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.recipient-wrap').forEach(makeChipInput);
    initPicker();
  });
})();
