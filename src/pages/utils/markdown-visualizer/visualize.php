<?php
declare(strict_types=1);

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Src\Support\Security;
use Src\Support\Str;

$markdownContent = Security::filterInputString(INPUT_POST, 'markdownContent');

$markdownSpecialSymbols = ["\n", "\r", '#', '*', '_', '~', '`', '{', '}', '[', ']', '(', ')', '+', '-', '!'];

if ($markdownContent === '' || !Str::contains($markdownContent, $markdownSpecialSymbols)) {
    echo $markdownContent;
    die(0);
}

$converter = new GithubFlavoredMarkdownConverter([
    'html_input' => 'strip',
    'allow_unsafe_links' => false,
]);

echo $converter->convert($markdownContent);
die(0);