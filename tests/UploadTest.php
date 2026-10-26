<?php

declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../prescia/lib/storeFile.php';

final class UploadTest extends TestCase
{
    public function testVirtualUploadUsesAllowlistedExtension(): void
    {
        $root = sys_get_temp_dir() . '/prescia-upload-' . bin2hex(random_bytes(6));
        mkdir($root, 0750, true);
        $source = $root . '/source.txt';
        $destination = $root . '/stored';
        file_put_contents($source, 'safe upload');

        try {
            $file = [
                'error' => UPLOAD_ERR_OK,
                'tmp_name' => $source,
                'name' => 'document.txt',
                'virtual' => true,
            ];

            self::assertSame(0, \storeFile($file, $destination, 'udef:txt'));
            self::assertSame($root . '/stored.txt', $destination);
            self::assertSame('safe upload', file_get_contents($destination));
        } finally {
            @unlink($destination);
            @unlink($source);
            @rmdir($root);
        }
    }

    public function testDisallowedExtensionIsRejected(): void
    {
        $root = sys_get_temp_dir() . '/prescia-upload-' . bin2hex(random_bytes(6));
        mkdir($root, 0750, true);
        $source = $root . '/source.php';
        $destination = $root . '/stored';
        file_put_contents($source, '<?php echo "no";');

        try {
            $file = [
                'error' => UPLOAD_ERR_OK,
                'tmp_name' => $source,
                'name' => 'script.php',
                'virtual' => true,
            ];

            self::assertSame(5, \storeFile($file, $destination, 'image'));
            self::assertFileDoesNotExist($destination . '.php');
        } finally {
            @unlink($source);
            @rmdir($root);
        }
    }

    public function testSuccessfulUploadWithoutTemporaryFileIsRejected(): void
    {
        $destination = sys_get_temp_dir() . '/prescia-upload-missing';
        $file = [
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => '',
            'name' => 'document.txt',
            'virtual' => true,
        ];

        self::assertSame(3, \storeFile($file, $destination, 'udef:txt'));
    }

    public function testUploadFailureDoesNotPublishPartialDestination(): void
    {
        $root = sys_get_temp_dir() . '/prescia-upload-' . bin2hex(random_bytes(6));
        mkdir($root, 0750, true);
        $source = $root . '/source.txt';
        $destination = $root . '/readonly/stored';
        mkdir($root . '/readonly', 0500, true);
        file_put_contents($source, 'cannot publish');

        try {
            $file = [
                'error' => UPLOAD_ERR_OK,
                'tmp_name' => $source,
                'name' => 'document.txt',
                'virtual' => true,
            ];

            self::assertSame(3, \storeFile($file, $destination, 'udef:txt'));
            self::assertFileDoesNotExist($destination . '.txt');
        } finally {
            @chmod($root . '/readonly', 0700);
            @unlink($destination . '.txt');
            @unlink($source);
            @rmdir($root . '/readonly');
            @rmdir($root);
        }
    }
}
