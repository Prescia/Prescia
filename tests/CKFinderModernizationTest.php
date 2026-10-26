<?php
declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class CKFinderModernizationTest extends TestCase
{
    public function testOnlyModernConnectorRuntimeIsPresent(): void
    {
        $root = realpath(__DIR__ . '/../pages/_js/ckfinder');
        self::assertNotFalse($root);

        $legacyPaths = array();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            if (preg_match('~(^|/)(php4|php5)(/|$)|ckfinder_php[45]\.php$~i', $relative) === 1) {
                $legacyPaths[] = $relative;
            }
        }

        self::assertSame(array(), $legacyPaths);
        self::assertFileExists($root . '/core/ckfinder.php');
        self::assertFileExists($root . '/core/connector/php/modern/Core/Connector.php');
    }

    public function testConnectorSelectsModernLibraryWithoutLegacyFallback(): void
    {
        $constants = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/constants.php');
        $bootstrap = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/ckfinder.php');
        $entrypoint = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/ckfinder.php');

        self::assertStringContainsString("define('CKFINDER_CONNECTOR_LIB_DIR', \"./modern\");", $constants);
        self::assertStringNotContainsString('ckfinder_php4.php', $constants . $bootstrap . $entrypoint);
        self::assertStringNotContainsString('ckfinder_php5.php', $constants . $bootstrap . $entrypoint);
        self::assertStringNotContainsString("CKFINDER_CONNECTOR_PHP4", $constants . $bootstrap . $entrypoint);
        self::assertStringNotContainsString("CKFINDER_CONNECTOR_PHP5", $constants . $bootstrap . $entrypoint);
    }
}
