<?php
declare(strict_types=1);

use Src\Exceptions\TerminateException;
use Src\Support\Security;

$inputLink = Security::filterInputString(INPUT_POST, 'link');
if ($inputLink === '') {
    throw new TerminateException('Введите ссылку');
}

$matches = [];
preg_match('/id=(\d+)/', $inputLink, $matches);

$bookId = $matches[1] ?? '';

if ($bookId === '') {
    throw new TerminateException('ID книги не распознано');
}

getFromLoveread($bookId);

/**
 * Book ID on loveread.ec
 * For example, for
 * http://loveread.ec/read_book.php?id=2555&p=1
 * ID is 2555.
 *
 * @param int $id
 */
function getFromLoveread(int $id)
{
    $htmlFirstPage = iconv('windows-1251', 'utf-8', file_get_contents("http://loveread.ec/read_book.php?id=$id&p=1"));
    @preg_match("~&#8230;<a href='read_book.php\?id=$id&p=[0-9]+~", $htmlFirstPage, $pagesCount);
    $pagesCount = ltrim(substr($pagesCount[0], -3), '=');
    if (!is_numeric($pagesCount) || empty($pagesCount)) {
        $pagesCount = ltrim(substr($pagesCount[0], -4), '=');
    }
    if ($pagesCount) {
        $patterns = ['~(\<(/?[^>]+)>)~is', '~&#769;~', '~&#039;~', '~&#252;~'];

        /* Finding the page title tag */
        preg_match('/<title>(.*?)<\/title>/', $htmlFirstPage, $output);
        $pageTitle = $output[1] ?? '';

        /* Removing LoveRead.ec message */
        $bookTitle = str_replace(' | LoveRead.ec - читать книги онлайн бесплатно', '', $pageTitle);
        /* For some reason, the '|' symbol is not treated correctly when downloading the file, so replacing it with '-' */
        $bookTitle = str_replace('|', '-', $bookTitle);

        /* Finally, downloading the book with its title */
        header('Content-type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $bookTitle . '.txt"');
        for ($p = 1; $p <= $pagesCount; $p++) {
            $url = "http://loveread.ec/read_book.php?id=$id&p=$p";
            $html = file_get_contents($url);
            $html = iconv('windows-1251', 'utf-8', $html);
            preg_match('~<p.*class=MsoNormal>(.*?)</p>~is', $html, $matches);
            $content = preg_replace($patterns, '', $matches[0]);
            echo $content;
        }
        exit(0);
    }
}