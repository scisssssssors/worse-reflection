<?php

namespace Phpactor\WorseReflection\Tests\Unit\Core\SourceCodeLocator;

use PHPUnit\Framework\TestCase;
use Phpactor\WorseReflection\Core\Exception\SourceNotFound;
use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\SourceCodeLocator\ReflectionFunctionSourceLocator;

class ReflectionFunctionSourceLocatorTest extends TestCase
{
    /**
     * @var ReflectionFunctionSourceLocator
     */
    private $locator;

    public function setUp()
    {
        $this->locator = new ReflectionFunctionSourceLocator();
    }

    public function testLocatesAFunction()
    {
        $location = $this->locator->locate(Name::fromString(__NAMESPACE__ . '\\test_function'));
        $this->assertEquals(__FILE__, $location->path());
        $this->assertEquals(file_get_contents(__FILE__), $location->__toString());
    }

    public function testThrowsExceptionWhenSourceNotFound()
    {
        $this->expectException(SourceNotFound::class);
        $this->locator->locate(Name::fromString(__NAMESPACE__ . '\\not_existing'));
    }
}

function test_function()
{
}
