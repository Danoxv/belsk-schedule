<?php
declare(strict_types=1);

use Src\Config\AppConfig;

$config = AppConfig::getInstance();
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Просмотр расписания</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php require ROOT . '/src/pages/components/common-js-css.php' ?>
    <style>
        #main-container {
            padding-top: 6px;
            padding-bottom: 6px;
        }
    </style>
    <script>
      let debouncedApplyHtmlFromMarkdown = debounce(applyHtmlFromMarkdown, 300);

      function applyHtmlFromMarkdown(md = null) {
        const
          htmlOutput = document.getElementById('html-output'),
          htmlOutputVisual = document.getElementById('html-output-visual');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/utils/markdown-visualizer/visualize', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange = function() {
          if(xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
            htmlOutput.value = this.responseText;
            htmlOutputVisual.innerHTML = this.responseText;
          }
        }

        const markdownContent = md || document.getElementById('md-input').value;

        if (!markdownContent) {
          htmlOutput.value = '';
          htmlOutputVisual.innerHTML = '';
          return;
        }

        xhr.send('markdownContent=' + markdownContent);
      }

      function applyMarkdownExample() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '/utils/markdown-visualizer/get-example', true);

        xhr.onload = function () {
          document.getElementById('md-input').value = this.responseText;
          applyHtmlFromMarkdown(this.responseText);
        };

        xhr.send(null);
      }
    </script>
</head>
<body>
<div class="container" id="main-container">
    <?php require ROOT . '/src/pages/components/dark-mode.php' ?>
    <h3>Markdown visualizer</h3>
    <div class="row">
        <div class="col">
            <div class="form-group">
                <label for="md-input">Markdown (<a href="#" onclick="applyMarkdownExample()">вставить пример</a>)</label>
                <textarea oninput="debouncedApplyHtmlFromMarkdown()" class="form-control" id="md-input" rows="20"></textarea>
            </div>
        </div>
        <div class="col">
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true">
                        Просмотр
                    </button>
                    <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                        HTML
                    </button>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                    <div id="html-output-visual"></div>
                </div>
                <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                    <textarea id="html-output" class="form-control" rows="19"></textarea>
                </div>
            </div>
        </div>
    </div>
    <br />
    <a class="btn btn-primary" href="/utils" role="button">Утилиты</a>
    <a class="btn btn-primary" href="/" role="button">На главную</a>
</div>
</body>
</html>