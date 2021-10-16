<?php

namespace Src\Support;

/**
 * Source: @link https://github.com/thephpleague/flysystem/blob/2.x/src/WhitespacePathNormalizer.php
 */
class Path
{
    /**
     * @param string $path
     * @return string
     */
    public static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        self::rejectFunkyWhiteSpace($path);

        return self::normalizeRelativePath($path);
    }

    private static function rejectFunkyWhiteSpace(string $path): void
    {
        if (preg_match('#\p{C}+#u', $path)) {
            throw new \RuntimeException("Corrupted path detected: " . $path);
        }
    }

    private static function normalizeRelativePath(string $path): string
    {
        $parts = [];

        foreach (explode('/', $path) as $part) {
            switch ($part) {
                case '':
                case '.':
                    break;

                case '..':
                    if (empty($parts)) {
                        throw new \RuntimeException("Path traversal detected: {$path}");
                    }
                    array_pop($parts);
                    break;

                default:
                    $parts[] = $part;
                    break;
            }
        }

        return implode('/', $parts);
    }
}