<?php

use Src\Config\AppConfig;
use Src\Config\SheetProcessingConfig;
use Src\Models\Group;
use Src\Models\Sheet;
use Src\Support\Helpers;
use Src\Support\Security;

$contentTemplate =
'<?php
// Auto-generated file by src/scripts/generate-group-list.php
// Regenerate with:
// $ php public/index.php generate-group-list.php 
return {{groupNames}};
';

$links = Helpers::getScheduleFilesLinks();

$config = AppConfig::getInstance();
foreach ($config->samples as $sample) {
    $samplePath = ROOT . '/public/samples/' . $sample;
    $links[] = [
        'uri' => $samplePath,
    ];
}

$sheetProcessingConfig = new SheetProcessingConfig([
    'processGroups' => false,
]);

$groupNames = [];
foreach ($links as $link) {
    if (Helpers::isExternalLink($link['uri'])) {
        $scheduleLink = Security::sanitizeString($link['uri'], true);

        $data = Helpers::httpGet($scheduleLink);

        if (empty($data)) {
            continue;
        }

        $temp = tmpfile();
        fwrite($temp, $data);

        $filePath = stream_get_meta_data($temp)['uri'];
    } else {
        $filePath = $link['uri'];
    }

    try {
        $spreadsheet = Sheet::createSpreadsheet($filePath, $sheetProcessingConfig);
    } catch (Exception $e) {
        continue;
    }

    var_dump("Process [{$link['uri']}]...");

    foreach ($spreadsheet->getAllSheets() as $worksheet) {
        $sheet = new Sheet($worksheet, $sheetProcessingConfig);

        /** @var Group $group */
        foreach ($sheet->getGroups() as $group) {
            $groupNames[] = $group->getName();
        }
    }

    var_dump('...done');
}

var_dump('All links and files processed');

$groupListFile = ROOT . '/src/Config/group-list.php';

$existingGroups = require $groupListFile;
$groupNames = array_merge($groupNames, $existingGroups);

$groupNames = array_filter($groupNames);
$groupNames = array_unique($groupNames);
sort($groupNames);
$groupNames = array_values($groupNames);

$written = file_put_contents(
    $groupListFile,
    str_replace('{{groupNames}}', var_export($groupNames, true), $contentTemplate)
);

if ($written) {
    var_dump('File successfully generated.');
} else {
    var_dump('FILE GENERATION ERROR!');
}