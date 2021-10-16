<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use Src\Config\Config;
use Src\Config\SheetProcessingConfig;
use Src\Models\Group;
use Src\Models\Sheet;
use Src\Support\Helpers;
use Src\Support\Security;

$contentTemplate = '
<?php

return {{groups}};
';

$links = Helpers::getScheduleFilesLinks();

$config = Config::getInstance();
foreach ($config->samples as $sample) {
    $samplePath = ROOT . '/src/samples/' . $sample;
    $links[] = [
        'uri' => $samplePath,
    ];
}

$groupNames = [];
foreach ($links as $link) {
    if (Helpers::isExternalLink($link['uri'])) {
        $scheduleLink = Security::sanitizeString($link['uri'], true);
        $scheduleLink = Helpers::sanitizeScheduleLink($scheduleLink);

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
        $reader = IOFactory::createReaderForFile($filePath)
            ->setReadDataOnly(true)
        ;

        $spreadsheet = $reader->load($filePath);
    } catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        continue;
    }

    var_dump("Process [{$link['uri']}]...");

    foreach ($spreadsheet->getAllSheets() as $worksheet) {
        $sheet = new Sheet($worksheet, new SheetProcessingConfig([
            'processGroups' => false,
        ]));

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

$groupNames = array_filter($groupNames);
$groupNames = array_unique($groupNames);
sort($groupNames);
$groupNames = array_values($groupNames);

$written = file_put_contents(
    $groupListFile,
    str_replace([
        '{{groups}}',
    ], [
        var_export($groupNames, true)
    ], $contentTemplate)
);

if ($written) {
    var_dump('File successfully generated.');
} else {
    var_dump('GENERATION FILE ERROR!');
}