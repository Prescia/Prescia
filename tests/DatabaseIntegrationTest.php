<?php

declare(strict_types=1);

namespace {
    if (!function_exists('getmicrotime')) {
        function getmicrotime(): float
        {
            return microtime(true);
        }
    }

    require_once __DIR__ . '/../prescia/lib/dbo/cdbo.php';
    require_once __DIR__ . '/../prescia/lib/dbo/mysqli.php';
}

namespace Prescia\Tests {

use PHPUnit\Framework\TestCase;

final class DatabaseIntegrationTest extends TestCase
{
    private ?\mysqli $connection = null;

    protected function setUp(): void
    {
        if (getenv('PRESCIA_DB_HOST') === false) {
            self::markTestSkipped('MySQL integration environment is not configured.');
        }

        $this->connection = new \mysqli(
            (string) getenv('PRESCIA_DB_HOST'),
            (string) getenv('PRESCIA_DB_USER'),
            (string) getenv('PRESCIA_DB_PASSWORD'),
            (string) getenv('PRESCIA_DB_NAME'),
            (int) (getenv('PRESCIA_DB_PORT') ?: 3306)
        );
        self::assertNull($this->connection->connect_error);
        $this->connection->query('CREATE TABLE prepared_regression (id INT PRIMARY KEY AUTO_INCREMENT, value_text VARCHAR(255), value_num INT, value_nullable VARCHAR(255) NULL)');
    }

    protected function tearDown(): void
    {
        if ($this->connection instanceof \mysqli) {
            $this->connection->query('DROP TABLE IF EXISTS prepared_regression');
            $this->connection->close();
        }
    }

    public function testPreparedCrudPreservesQuotesSlashesUnicodeAndNull(): void
    {
        $value = "O'Reilly \\ café ☃'; DROP TABLE prepared_regression;--";
        $insert = $this->connection->prepare('INSERT INTO prepared_regression (value_text, value_num, value_nullable) VALUES (?, ?, ?)');
        self::assertNotFalse($insert);
        $nullable = null;
        $number = 42;
        $insert->bind_param('sis', $value, $number, $nullable);
        self::assertTrue($insert->execute());
        $id = $insert->insert_id;
        $insert->close();

        $select = $this->connection->prepare('SELECT value_text, value_num, value_nullable FROM prepared_regression WHERE id=?');
        self::assertNotFalse($select);
        $select->bind_param('i', $id);
        self::assertTrue($select->execute());
        $result = $select->get_result()->fetch_assoc();
        $select->close();

        self::assertSame($value, $result['value_text']);
        self::assertSame((string) $number, (string) $result['value_num']);
        self::assertNull($result['value_nullable']);
        self::assertSame('1', (string) $this->connection->query('SELECT COUNT(*) AS total FROM prepared_regression')->fetch_assoc()['total']);
    }

    public function testDriverInitializesOutputsForEmptyAndFailedPreparedQueries(): void
    {
        $driver = new \CDBO_mysqli(
            (string) getenv('PRESCIA_DB_HOST'),
            (string) getenv('PRESCIA_DB_USER'),
            (string) getenv('PRESCIA_DB_PASSWORD'),
            (string) getenv('PRESCIA_DB_NAME')
        );
        self::assertTrue($driver->connect());
        $result = 'stale';
        $rows = 99;
        self::assertTrue($driver->queryPrepared('SELECT value_text FROM prepared_regression WHERE id=?', 'i', [999], $result, $rows));
        self::assertSame(0, $rows);
        self::assertInstanceOf(\mysqli_result::class, $result);
        $result->free();

        $result = 'stale';
        $rows = 99;
        self::assertFalse($driver->queryPrepared('SELECT missing_column FROM prepared_regression', '', [], $result, $rows));
        self::assertFalse($result);
        self::assertSame(0, $rows);
    }
}

}
