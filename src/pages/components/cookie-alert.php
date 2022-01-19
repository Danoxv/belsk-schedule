<!-- Cookie alert -->
<script>
  window.addEventListener('DOMContentLoaded', function() {
    if (!localStorage.getItem('cookieAccepted')) {
      showCookieAlert();
    }
  });

  function closeCookieAlert() {
    localStorage.setItem('cookieAccepted', '1');
  }

  function showCookieAlert() {
    S('_cookie-msg').display = 'block';
  }
</script>

<div id="_cookie-msg" style="display:none;" class='alert alert-info alert-dismissible fade show fixed-bottom' role='alert'>
    Сайт использует Cookies. Продолжая использовать сайт, вы соглашаетесь с <a href="/terms-and-conditions">условиями</a>
    <button onclick="closeCookieAlert()" data-bs-dismiss='alert' type="button" class="btn btn-primary btn-sm ms-2">
        OK
    </button>
</div>
<!-- /Cookie alert -->
