<?php
declare(strict_types=1);

use Src\Exceptions\TerminateException;
use Src\Support\Security;
use Src\Support\Str;

$inputLink = Security::filterInputString(INPUT_POST, 'link');
$inputLink = Str::stripWhitespace($inputLink);

if ($inputLink === '') {
    throw new TerminateException('Введите ссылку');
}

$bookId = null;

$matches = [];
if (preg_match('/id=(\d+)/', $inputLink, $matches)) {
    // Handle inputs like
    // 'http://loveread.ec/view_global.php?id=2555&p=1'
    // 'loveread.ec/view_global.php?id=2555'
    // 'id=2555'

    $bookId = (int) $matches[1];
} else {
    // Handle inputs like
    // 'd=2555'
    // ' axa2 55 5xaxa'
    // '2 5 5 5'
    // '2555'

    $bookId = Str::toInt(preg_replace('/\D+/', '', $inputLink));
}

if ($bookId === null) {
    throw new TerminateException('ID книги не распознано');
}

$error = '';
downloadFromLoveread($bookId, $error);

if ($error) {
    throw new TerminateException($error);
}

/**
 * Book ID on loveread.ec
 * For example, for
 * http://loveread.ec/read_book.php?id=2555&p=1
 * ID is 2555.
 *
 * @param int $id
 * @param string $error Contains the error message or '' (the empty string) if no error occurred.
 */
function downloadFromLoveread(int $id, string &$error = '')
{
    $domain = 'http://loveread.ec';

    $firstPageHtml = @Str::fileGetContents(
        "$domain/read_book.php?id=$id&p=1",
        true,
        'windows-1251'
    );

    if (empty($firstPageHtml)) {
        $error = "Возможно, книга не существует (404) или сайт $domain недоступен";
        return;
    }

    $pagesCountMatches = [];
    preg_match("~&#8230;<a href='read_book.php\?id=$id&p=[0-9]+~", $firstPageHtml, $pagesCountMatches);
    if (empty($pagesCountMatches)) {
        $error = 'Не распознано количество страниц книги (ошибка 1)';
        return;
    }

    $pagesCount = Str::ltrim(Str::substr($pagesCountMatches[0], -3), '=');

    if (empty($pagesCount) || !is_numeric($pagesCount)) {
        $pagesCount = Str::ltrim(Str::substr($pagesCountMatches[0], -4), '=');
    }
    $pagesCount = Str::trim($pagesCount);
    $pagesCount = Str::toInt($pagesCount);

    if (empty($pagesCount)) {
        $error = 'Не распознано количество страниц книги (ошибка 2)';
        return;
    }

    /* Finding the page title tag */
    preg_match('/<title>(.*?)<\/title>/', $firstPageHtml, $output);
    $pageTitle = $output[1] ?? '';

    /* Removing LoveRead.ec message */
    $bookTitle = Str::replace('LoveRead.ec - читать книги онлайн бесплатно', '', $pageTitle);
    $bookTitle = Security::normalizeFilename($bookTitle);

    if ($bookTitle === '') {
        $bookTitle = 'loveread-book-'.$id;
    }

    /* Finally, downloading the book with its title */
    header('Content-type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $bookTitle . '.txt"');

    $stripPatterns = ['~(\<(/?[^>]+)>)~is', '~&#769;~', '~&#039;~', '~&#252;~'];

    $contentIsFound = false;
    for ($p = 1; $p <= $pagesCount; $p++) {
        $url = "$domain/read_book.php?id=$id&p=$p";
        $html = Str::fileGetContents($url, true, 'windows-1251');

        if (preg_match('~<p.*class=MsoNormal>(.*?)</p>~is', $html, $matches)) {
            $contentIsFound = true;
            $content = preg_replace($stripPatterns, '', $matches[0]);
            echo $content;
        }
    }

    if (!$contentIsFound) {
        $error = 'Контент книги на найден';
        return;
    }

    exit(0);
}