<?php

namespace Phpactor\WorseReflection\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\SourceCodeLocator;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

class ReflectorBuilderTest extends TestCase
{
    public function testBuildWithDefaults()
    {
        $reflector = ReflectorBuilder::create()->build();
        $this->assertInstanceOf(Reflector::class, $reflector);
    }

    public function testReplacesLogger()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $reflector = ReflectorBuilder::create()
            ->withLogger($logger->reveal())
            ->build();

        $this->assertInstanceOf(Reflector::class, $reflector);
    }

    public function testHasOneLocator()
    {
        $locator = $this->prophesize(SourceCodeLocator::class);
        $reflector = ReflectorBuilder::create()
            ->addLocator($locator->reveal())
            ->build();

        $this->assertInstanceOf(Reflector::class, $reflector);
    }

    public function testHasManyLocators()
    {
        $locator = $this->prophesize(SourceCodeLocator::class);
        $reflector = ReflectorBuilder::create()
            ->addLocator($locator->reveal())
            ->addLocator($locator->reveal())
            ->build();

        $this->assertInstanceOf(Reflector::class, $reflector);
    }

    public function testHighestPriorityLocatorWins()
    {
        $locator1 = $this->prophesize(SourceCodeLocator::class);
        $locator2 = $this->prophesize(SourceCodeLocator::class);
        $locator3 = $this->prophesize(SourceCodeLocator::class);

        $reflector = ReflectorBuilder::create()
            ->addLocator($locator1->reveal(), 0)
            ->addLocator($locator2->reveal(), 10)
            ->addLocator($locator3->reveal(), -10)
            ->build();

        $locator1->locate(Argument::any())->shouldNotBeCalled();
        $locator2->locate(Argument::any())->willReturn(SourceCode::fromString(file_get_contents(__FILE__)));
        $locator3->locate(Argument::any())->shouldNotBeCalled();

        $this->assertInstanceOf(Reflector::class, $reflector);
        $reflector->reflectClass(__CLASS__);
    }

    public function testWithSource()
    {
        $reflector = ReflectorBuilder::create()
            ->addSource('<?php class Foobar {}')
            ->build();

        $class = $reflector->reflectClass('Foobar');
        $this->assertEquals('Foobar', $class->name()->__toString());
        $this->assertInstanceOf(Reflector::class, $reflector);
    }

    public function testEnableCache()
    {
        $reflector = ReflectorBuilder::create()
            ->enableCache()
            ->build();

        $this->assertInstanceOf(Reflector::class, $reflector);
    }

    public function testEnableContextualSourceLocation()
    {
        $reflector = ReflectorBuilder::create()
            ->enableContextualSourceLocation()
            ->build();

        $this->assertInstanceOf(Reflector::class, $reflector);
    }
}
