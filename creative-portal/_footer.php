    </section>
  </main>
</div>
<script>
(function () {
  var token = window.CORO_CREATIVE_PORTAL_CSRF || '';
  if (!token || !window.HTMLFormElement) return;
  function addCsrf(form) {
    if (!form || String(form.method || '').toLowerCase() !== 'post') return;
    var field = form.querySelector('input[name="_csrf"]');
    if (!field) {
      field = document.createElement('input');
      field.type = 'hidden';
      field.name = '_csrf';
      form.appendChild(field);
    }
    field.value = token;
  }
  document.addEventListener('submit', function (event) {
    addCsrf(event.target);
  }, true);
  document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(addCsrf);
})();
</script>
</body>
</html>
