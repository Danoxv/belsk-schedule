<?php
$config = require ROOT . '/src/config.php';

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;

$pageWithFiles = $config['pageWithScheduleFiles'];
$html = @file_get_contents($pageWithFiles);

$links = [];

if ($html !== false) {
    $crawler = new Crawler($html, getHost($pageWithFiles));

    /** @var Link[] $crawlerLinks */
    $crawlerLinks = $crawler->filterXPath('//body//a')->links();

    foreach ($crawlerLinks as $link) {
        $linkUri = $link->getUri();

        if (!strEndsWith($linkUri, $config['allowedExtensions'])) {
            continue;
        }

        $linkUri = sanitize($linkUri);

        $node = $link->getNode();
        $linkText = sanitize($node->textContent);

        $links[] = [
            'uri' => $linkUri,
            'text' => $linkText,
        ];
    }
}

?>

<!doctype html>
<html lang="ru">
<head>
    <title>Просмотр расписания</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-U1DAWAznBHeqEIlVSCgzq+c9gqGAJn5c/t99JyeKa9xxaYpSvHU5awsuZVVFIhvj" crossorigin="anonymous"></script>
    <style>
        a[target="_blank"]::after {
            content: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAQElEQVR42qXKwQkAIAxDUUdxtO6/RBQkQZvSi8I/pL4BoGw/XPkh4XigPmsUgh0626AjRsgxHTkUThsG2T/sIlzdTsp52kSS1wAAAABJRU5ErkJggg==);
            margin: 3px 3px 0 5px;
        }
        .main-container {
            padding-top: 6px;
            padding-bottom: 6px;
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <form method="post" action="process-schedule-file" enctype="multipart/form-data">
            <div class="row">
                <div class="col">
                    <div class="mb-3">
                        <div><b>Выберите из списка:</b></div>
                        <?php foreach ($links as $linkIdx => $link): ?>
                            <div class="form-check">
                                <input name="scheduleLink" value="<?=$link['uri']?>" class="form-check-input" type="radio" id="scheduleLink<?=$linkIdx?>">
                                <label class="form-check-label" for="scheduleLink<?=$linkIdx?>">
                                    <?= $link['text'] ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <div class="form-text">(получено с <a target="_blank" href="<?= $pageWithFiles ?>"><?= $pageWithFiles ?></a>)</div>
                    </div>
                </div>
                <div class="col-1">
                    <div class="d-flex justify-content-center" style="height: 90%">
                        <div class="vr"></div>
                    </div>
                </div>
                <div class="col">
                    <div class="mb-3">
                        <label for="scheduleFile" class="form-label">Либо <b>загрузите свой файл</b> расписания:</label>
                        <input name="scheduleFile" class="form-control" type="file" accept="<?= implode(',', $config['allowedMimes']) ?>" id="scheduleFile" aria-describedby="scheduleFileHelp">
                        <div id="scheduleFileHelp" class="form-text">
                            Выберите Excel-файл (XLS/XLSX).
                            <?php if (!empty($config['samples'])): ?>
                                Скачать примеры:<br />
                                <?php foreach ($config['samples'] as $sample): ?>
                                    <a href="download-sample?f=<?= $sample ?>"><?= $sample ?></a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="row"><hr /></div> -->
            <div class="row">
                <div class="mb-3">
                    <label for="group" class="form-label"><b>Группа:</b></label>
                    <select name="group" class="form-select" aria-label="Выберите группу" id="group" aria-describedby="groupHelp">
                        <!-- <option value="" selected disabled>Выберите...</option> -->
                        <?php foreach ($config['groupsList'] as $group): ?>
                            <option value="<?= $group ?>"><?= $group ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div id="groupHelp" class="form-text">Выберите учебную группу, для которой хотите получить расписание.</div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Отправить</button>
        </form>
    </div>
</body>
</html>