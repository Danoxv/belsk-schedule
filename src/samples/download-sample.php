<?php

use Src\Exceptions\TerminateException;

$fileName = safeFilterInput(INPUT_GET, 'f');

if (empty($fileName)) {
    throw new TerminateException('Please pass "f" param with filename for opening. Ex: [...]/download-sample?f=1.xls');
}
$config = require ROOT . '/src/config.php';

$allowedFiles = $config['samples'] ?? [];

if (!in_array($fileName, $allowedFiles, true)) {
    throw new TerminateException('Hack attempt', TerminateException::TYPE_DANGER);
}

$fileName = ROOT . '/src/samples/' . $fileName;

if (!file_exists($fileName) || !is_file($fileName)) {
    throw new TerminateException('File not exists');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($fileName));
readfile($fileName);