<?php
declare(strict_types=1);

use Src\Config\AppConfig;
use Src\Support\Helpers;
use Src\Support\Session;

$config = AppConfig::getInstance();

$allowedExtensionsAsString = implode(', ', $config->allowedExtensions);

$pageWithFiles = $config->pageWithScheduleFiles;

$links = Helpers::getScheduleFilesLinks($linksGettingCurlError);

$session = new Session();
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Просмотр расписания</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-U1DAWAznBHeqEIlVSCgzq+c9gqGAJn5c/t99JyeKa9xxaYpSvHU5awsuZVVFIhvj" crossorigin="anonymous"></script>
    <script src="/js/common.js?v=<?= $config->version['number'] ?>"></script>
    <script src="/js/schedule-pages-functions.js?v=<?= $config->version['number'] ?>"></script>
    <style>
        #main-container {
            padding-top: 6px;
            padding-bottom: 6px;
        }
        a[target="_blank"]::after {
            content: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAQElEQVR42qXKwQkAIAxDUUdxtO6/RBQkQZvSi8I/pL4BoGw/XPkh4XigPmsUgh0626AjRsgxHTkUThsG2T/sIlzdTsp52kSS1wAAAABJRU5ErkJggg==);
            margin: 3px 3px 0 5px;
        }
    </style>
</head>
<body>
    <div class="container" id="main-container">
        <?php require ROOT . '/src/pages/components/dark-mode.php' ?>
        <form method="post" action="/schedule-file" enctype="multipart/form-data">
            <div class="row">
                <div class="col">
                    <div class="mb-3">
                        <div><b>1. Выберите из списка:</b></div>
                        <?php if (!empty($links)): ?>
                            <?php foreach ($links as $linkIdx => $link): ?>
                                <div class="form-check">
                                    <input name="scheduleLink" onchange="onScheduleLinkChange()" <?= ($linkIdx === 0 || $session->get('scheduleLink') === $link['uri']) ? 'checked' : '' ?> value="<?=$link['uri']?>" class="form-check-input" type="radio" id="scheduleLink<?=$linkIdx?>">
                                    <label class="form-check-label" for="scheduleLink<?=$linkIdx?>">
                                        <?= $link['text'] ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <div class="form-text">(получено с <a target="_blank" href="<?= $pageWithFiles ?>"><?= $pageWithFiles ?></a>)</div>
                        <?php else: ?>
                            <div class="form-text">
                                <p>Не найдено ссылок на странице <a target="_blank" href="<?= $pageWithFiles ?>"><?= $pageWithFiles ?></a>.</p>
                                <p>
                                    Причина:
                                    <?php if ($linksGettingCurlError): ?>
                                        страница недоступна. <?= $linksGettingCurlError ?>
                                    <?php else: ?>
                                        отсутствуют ссылки на поддерживаемые файлы (<?= $allowedExtensionsAsString ?>)
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-1">
                    <div class="d-flex justify-content-center" style="height: 90%">
                        <div class="vr"></div>
                    </div>
                </div>
                <div class="col">
                    <div class="mb-3">
                        <label for="scheduleFile" class="form-label">Либо <b>загрузите свой файл</b> расписания (<?= $allowedExtensionsAsString ?>):</label>
                        <input name="scheduleFile" onchange="onScheduleFileChange()" class="form-control" type="file" accept="<?= implode(',', $config->allowedMimes) ?>" id="scheduleFile" aria-describedby="scheduleFileHelp">
                        <div id="scheduleFileHelp" class="form-text">
                            <?php if (!empty($config->samples)): ?>
                                Скачать примеры:
                                <?php foreach ($config->samples as $sample): ?>
                                    <a href="/samples/<?= $sample ?>"><?= $sample ?></a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="row"><hr /></div> -->
            <div class="row">
                <div class="mb-3">
                    <label for="group" class="form-label"><b>2. Группа:</b></label>
                    <select name="group" class="form-select" aria-label="Выберите группу" id="group" aria-describedby="groupHelp">
                        <!-- <option value="" selected disabled>Выберите...</option> -->
                        <?php foreach ($config->groupsList as $group): ?>
                            <option value="<?= $group ?>" <?= $session->get('group') === $group ? ' selected ' : '' ?>><?= $group ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div id="groupHelp" class="form-text">Выберите учебную группу, для которой хотите получить расписание.</div>
                </div>
            </div>
            <div class="row">
                <div class="mb-3">
                    <b>3. Настройки:</b>
                    <div class="form-check">
                        <input name="detectMendeleeva4" class="form-check-input" type="checkbox" value="1" <?= $session->get('detectMendeleeva4', true) === true  ? ' checked ' : '' ?> id="detectMendeleeva4" aria-describedby="detectMendeleeva4Help">
                        <label class="form-check-label" for="detectMendeleeva4">
                            Выделять пары, проходящие на Менделеева, 4
                        </label>
                        <div id="detectMendeleeva4Help" class="form-text">Такие пары будут выделены зелёным цветом. Может работать некорректно.</div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Отправить</button>
        </form>
    </div>
</body>
</html>