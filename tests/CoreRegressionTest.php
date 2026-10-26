<?php

declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

final class CoreRegressionTest extends TestCase
{
    private const CORE = __DIR__ . '/../prescia/core.php';

    public function testAddLinkChecksResolvedJavascriptAndStylesheetFiles(): void
    {
        $core = (string) file_get_contents(self::CORE);
        $javascriptError = <<<'PHP'
'Javascript not found',$file,"addLink"
PHP;
        $styleError = <<<'PHP'
'Style not found',$file,"addLink"
PHP;
        $genericError = <<<'PHP'
'File not found',$file,"addLink"
PHP;

        self::assertStringContainsString("else if (!is_file(\$file)) {", $core);
        self::assertStringContainsString("else if (!is_file(\$tfile)) {", $core);
        self::assertStringContainsString($javascriptError, $core);
        self::assertStringContainsString($styleError, $core);
        self::assertStringNotContainsString($genericError, $core);
    }

    public function testMetadataReadsMetaXmlOnlyWhenTheFileExists(): void
    {
        $core = (string) file_get_contents(self::CORE);

        self::assertStringContainsString(
            "if (is_file(CONS_PATH_PAGES.\$_SESSION['CODE'].\"/template/_meta.xml\"))",
            $core
        );
        self::assertStringNotContainsString(
            "if (CONS_PATH_PAGES.\$_SESSION['CODE'].\"/template/_meta.xml\")",
            $core
        );
    }
}
