<?php

declare(strict_types=1);

/** Hash and verify passwords using the PHP password API. */
function presciaPasswordHash(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function presciaPasswordVerify(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function presciaPasswordNeedsRehash(string $hash): bool
{
    return password_needs_rehash($hash, PASSWORD_DEFAULT);
}

function presciaPasswordIsLegacy(string $storedValue): bool
{
    $info = password_get_info($storedValue);
    return empty($info['algo']);
}
