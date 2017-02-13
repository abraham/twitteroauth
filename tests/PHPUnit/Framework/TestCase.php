<?php

namespace \PHPUnit\Framework;

if (!class_exists('\PHPUnit\Framework\TestCase')) {
    /**
     * Compatibility with PHPUnit 4.
     */
    class TestCase extends PHPUnit_Framework_TestCase {
    }
}
