<?php
declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../prescia/lib/zipfile.php';

final class ZipfileTest extends TestCase
{
    public function testFileHeaderUsesPackedDosTimestampWithoutEval(): void
    {
        $source = (string) file_get_contents(__DIR__ . '/../prescia/lib/zipfile.php');
        self::assertStringNotContainsString('eval(', $source);

        $archive = new \zipfile();
        $timestamp = 1704067200;
        $archive->addFile('payload', 'payload.txt', $timestamp);
        $zip = $archive->file();

        self::assertSame("PK\x03\x04", substr($zip, 0, 4));
        self::assertSame(pack('V', $archive->unix2DosTime($timestamp)), substr($zip, 10, 4));
        self::assertSame('payload.txt', substr($zip, 30, 11));
        $compressedLength = unpack('V', substr($zip, 18, 4))[1];
        self::assertSame('payload', gzinflate(substr($zip, 41, $compressedLength)));
    }
}
