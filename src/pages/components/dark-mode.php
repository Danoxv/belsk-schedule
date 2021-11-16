<!-- Dark mode -->
<script>
  // Auto-switch to dark mode if already switched
  window.addEventListener('DOMContentLoaded', function() {
    let darkModeToggle = O('darkModeToggle');
    if (isDarkModeEnabled()) {
      enableDarkMode();
      darkModeToggle.checked = true;
    } else {
      darkModeToggle.checked = false;
    }
  });

  function enableDarkMode() {
    let htmlTag = getHtmlTag();

    htmlTag.style['filter'] = 'invert(90%)';
    localStorage.setItem('darkModeEnabled', '1');
  }

  function disableDarkMode() {
    let htmlTag = getHtmlTag();

    htmlTag.style['filter'] = '';
    localStorage.setItem('darkModeEnabled', '');
  }

  function isDarkModeEnabled() {
    return !!localStorage.getItem('darkModeEnabled');
  }

  function switchDarkMode() {
    if (isDarkModeEnabled()) {
      disableDarkMode();
    } else {
      enableDarkMode();
    }
  }
</script>
<div class="sticky-sm-top clearfix">
    <div class="form-check form-switch float-end">
        <input id="darkModeToggle" onchange="switchDarkMode()" class="form-check-input" type="checkbox" role="switch">
        <label class="form-check-label" for="darkModeToggle">Dark</label>
    </div>
</div>
<!-- /Dark mode -->