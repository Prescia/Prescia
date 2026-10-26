<?php

declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../prescia/lib/serialization.php';

final class SerializationTest extends TestCase
{
    public function testArraysAreDecoded(): void
    {
        self::assertSame(['safe' => true], \presciaSafeUnserialize(serialize(['safe' => true])));
    }

    public function testObjectsAreNotInstantiatedByDefault(): void
    {
        $value = \presciaSafeUnserialize(serialize(new \stdClass()));

        self::assertInstanceOf(\__PHP_Incomplete_Class::class, $value);
    }

    public function testMalformedPayloadReturnsFalseWithoutWarning(): void
    {
        self::assertFalse(\presciaSafeUnserialize('a:2:{s:3:"bad";'));
        self::assertFalse(\presciaSafeUnserialize(''));
    }

    public function testAllowedClassesMustBeExplicit(): void
    {
        $value = \presciaSafeUnserialize(serialize(new \stdClass()), []);

        self::assertInstanceOf(\__PHP_Incomplete_Class::class, $value);
    }
}
