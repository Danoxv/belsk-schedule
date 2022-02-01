<?php
declare(strict_types=1);

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Src\Support\Security;
use Src\Support\Str;

$markdownContent = Security::filterInputString(INPUT_POST, 'markdownContent');

if ($markdownContent === '') {
    echo '';
    die(0);
}

$markdownSpecialSymbols = [
    "\n", "\r", '#', '*', '_', '~', '`', '{', '}', '[', ']', '(', ')', '+', '-', '!',  '>',
];

if (!Str::contains($markdownContent, $markdownSpecialSymbols)) {
    echo "<p>$markdownContent</p>";
    die(0);
}

$converter = new GithubFlavoredMarkdownConverter([
    'html_input'            => 'strip',
    'allow_unsafe_links'    => false,
    'max_nesting_level'     => 15,
]);

echo $converter->convert($markdownContent);
die(0);