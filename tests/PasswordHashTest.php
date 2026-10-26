<?php

declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../prescia/plugins/bi_auth/passwords.php';

final class PasswordHashTest extends TestCase
{
    public function testPasswordIsStoredAsModernHash(): void
    {
        $hash = \presciaPasswordHash('correct horse battery staple');

        self::assertNotSame('correct horse battery staple', $hash);
        self::assertStringStartsWith('$', $hash);
        self::assertTrue(\presciaPasswordVerify('correct horse battery staple', $hash));
        self::assertFalse(\presciaPasswordVerify('wrong password', $hash));
    }

    public function testLegacyPlaintextValueNeedsMigration(): void
    {
        self::assertFalse(\presciaPasswordVerify('legacy-password', 'legacy-password'));
        self::assertTrue(\presciaPasswordIsLegacy('legacy-password'));
        self::assertTrue(\presciaPasswordNeedsRehash('legacy-password'));
    }

    public function testModernHashDoesNotNeedRehashImmediately(): void
    {
        $hash = \presciaPasswordHash('a password');

        self::assertFalse(\presciaPasswordNeedsRehash($hash));
        self::assertFalse(\presciaPasswordIsLegacy($hash));
    }
}
