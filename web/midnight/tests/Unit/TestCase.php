<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base test case for unit tests.
 *
 * This class provides common functionality and helpers for all unit tests
 * in the Midnight Laravel package.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Assert that a value is readonly.
     *
     * @param object $object The object to check
     * @param string $property The property name
     */
    protected function assertPropertyIsReadonly(object $object, string $property): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $this->assertTrue(
            $reflection->isReadOnly(),
            "Property {$property} should be readonly"
        );
    }

    /**
     * Assert that a class is final.
     *
     * @param string|object $class The class name or object
     */
    protected function assertClassIsFinal(string|object $class): void
    {
        $reflection = new \ReflectionClass($class);
        $this->assertTrue(
            $reflection->isFinal(),
            "Class should be final"
        );
    }

    /**
     * Assert that a class is readonly.
     *
     * @param string|object $class The class name or object
     */
    protected function assertClassIsReadonly(string|object $class): void
    {
        $reflection = new \ReflectionClass($class);
        $this->assertTrue(
            $reflection->isReadOnly(),
            "Class should be readonly"
        );
    }
}
