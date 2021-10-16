<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
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

$groupNames = [];
foreach ($links as $link) {
    $scheduleLink = Security::sanitizeString($link['uri'], true);
    $scheduleLink = Helpers::sanitizeScheduleLink($scheduleLink);

    $data = Helpers::httpGet($scheduleLink);

    if (empty($data)) {
        continue;
    }

    $temp = tmpfile();
    fwrite($temp, $data);

    $filePath = stream_get_meta_data($temp)['uri'];

    try {
        $reader = IOFactory::createReaderForFile($filePath)
            ->setReadDataOnly(true)
        ;

        $spreadsheet = $reader->load($filePath);
    } catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        continue;
    }

    foreach ($spreadsheet->getAllSheets() as $worksheet) {
        $sheet = new Sheet($worksheet, new SheetProcessingConfig([
            'processGroups' => false,
        ]));

        /** @var Group $group */
        foreach ($sheet->getGroups() as $group) {
            $groupNames[] = $group->getName();
        }
    }
}

$groupListFile = ROOT . '/src/Config/group-list.php';

$existingGroups = require $groupListFile;

$groupNames = array_filter($groupNames);
$groupNames = array_unique($groupNames);
sort($groupNames);
$groupNames = array_values($groupNames);

file_put_contents(
    $groupListFile,
    str_replace([
        '{{groups}}',
    ], [
        var_export($groupNames, true)
    ], $contentTemplate)
);