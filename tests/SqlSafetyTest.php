<?php
declare(strict_types=1);

namespace Prescia\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

if (!defined('CONS_SQL_QUOTE')) {
    define('CONS_SQL_QUOTE', '`');
}
require_once __DIR__ . '/../prescia/lib/dbo/cdbo.php';
require_once __DIR__ . '/../prescia/lib/autoClean.php';

final class SqlSafetyTest extends TestCase
{
    public function testQuoteIdentifierAcceptsMetadataNamesAndRejectsSqlFragments(): void
    {
        $dbo = new \CDBO();

        self::assertSame('`stats_hits`', $dbo->quoteIdentifier('stats_hits'));
        self::assertSame('_private9', preg_replace('/^`|`$/', '', $dbo->quoteIdentifier('_private9')));

        $this->expectException(InvalidArgumentException::class);
        $dbo->quoteIdentifier('stats_hits; DROP TABLE users');
    }

    public function testAutocleanCompilesExistingMetadataFormatWithTypedInterval(): void
    {
        $module = (object) array(
            'fields' => array('data' => array('type' => 'date')),
        );
        $dbo = new \CDBO();

        $compiled = \CPresciaAutoClean::compile('data < NOW() - INTERVAL 29 DAY', $module, $dbo);

        self::assertSame('`data` < NOW() - INTERVAL 29 DAY', $compiled['sql']);
        self::assertSame('', $compiled['types']);
        self::assertSame(array(), $compiled['params']);
    }

    public function testAutocleanRejectsFreeFormSqlAndUndeclaredFields(): void
    {
        $module = (object) array(
            'fields' => array('data' => array('type' => 'date')),
        );
        $dbo = new \CDBO();

        $this->expectException(InvalidArgumentException::class);
        \CPresciaAutoClean::compile('data < NOW() - INTERVAL 1 DAY; DELETE FROM users', $module, $dbo);
    }

    public function testAutocleanRejectsFieldsOutsideModuleMetadata(): void
    {
        $module = (object) array(
            'fields' => array('created_at' => array('type' => 'date')),
        );
        $dbo = new \CDBO();

        $this->expectException(InvalidArgumentException::class);
        \CPresciaAutoClean::compile('data < NOW() - INTERVAL 1 DAY', $module, $dbo);
    }
}
