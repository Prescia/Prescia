<?php
declare(strict_types=1);

/** Resolve only JS/CSS assets contained in the published asset directory. */
function presciaResolveAsset(string $directory, mixed $requested, string $extension): ?string
{
    if (!in_array($extension, ['js', 'css'], true) || !is_string($requested)
        || !preg_match('~\A[A-Za-z0-9_-][A-Za-z0-9_.-]*(?:/[A-Za-z0-9_-][A-Za-z0-9_.-]*)*\z~D', $requested)
        || strtolower(pathinfo($requested, PATHINFO_EXTENSION)) !== $extension) {
        return null;
    }
    $root = realpath($directory);
    if ($root === false) {
        return null;
    }
    if ($requested === 'jquery.js' && $extension === 'js') {
        $requested = 'jquery-3.7.1.min.js';
    }
    $path = realpath($root . DIRECTORY_SEPARATOR . $requested);
    if ($path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR)
        || !is_file($path) || !is_readable($path)
        || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== $extension) {
        return null;
    }
    return $path;
}
