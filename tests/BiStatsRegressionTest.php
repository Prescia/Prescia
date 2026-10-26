<?php

declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

final class BiStatsRegressionTest extends TestCase
{
    private const ROOT = __DIR__ . '/..';

    public function testAnalyticsRenderersIterateAccumulatedRows(): void
    {
        $analytics = (string) file_get_contents(self::ROOT . '/prescia/plugins/bi_stats/payload/content/stats_analytics.php');
        $pathAjax = (string) file_get_contents(self::ROOT . '/prescia/plugins/bi_stats/payload/content/stats_pathajax.php');
        $references = (string) file_get_contents(self::ROOT . '/prescia/plugins/bi_stats/payload/content/stats_ref.php');

        self::assertSame(3, substr_count($analytics, 'for($c=0;$c<count($refs);$c++)'));
        self::assertSame(1, substr_count($analytics, 'for ($c=0;$c<count($res);$c++)'));
        self::assertSame(1, substr_count($analytics, 'for ($c=0;$c<count($langs);$c++)'));
        self::assertSame(2, substr_count($pathAjax, 'for($c=0;$c<count($pages);$c++)'));
        self::assertSame(3, substr_count($references, 'for($c=0;$c<count($refs);$c++)'));
        self::assertSame(1, substr_count($references, 'for ($c=0;$c<count($query);$c++)'));
    }

    public function testIpFilterTreatsPositionZeroAsAValidMatch(): void
    {
        $module = (string) file_get_contents(self::ROOT . '/prescia/plugins/bi_stats/module.php');

        self::assertStringContainsString('strpos(CONS_IP,trim($ip)) !== false', $module);
        self::assertStringNotContainsString('strpos(CONS_IP,trim($ip))!==-1', $module);
    }

    public function testPreparedQueryContractDocumentsOutputParametersForBothDrivers(): void
    {
        $baseDriver = (string) file_get_contents(self::ROOT . '/prescia/lib/dbo/cdbo.php');
        $mysqliDriver = (string) file_get_contents(self::ROOT . '/prescia/lib/dbo/mysqli.php');

        foreach ([$baseDriver, $mysqliDriver] as $driver) {
            self::assertStringContainsString('@param-out mixed $result', $driver);
            self::assertStringContainsString('@param-out int $numrows', $driver);
        }

        self::assertStringContainsString('$numrows = max(0, (int)$result->num_rows);', $mysqliDriver);
    }
}
