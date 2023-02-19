<?php

namespace Klkvsk\DtoGenerator\Test\Virtualizer;

use Klkvsk\DtoGenerator\Generator\Virtualizer;
use Klkvsk\DtoGenerator\Schema as dto;
use Klkvsk\DtoGenerator\DtoGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers Virtualizer
 */
class VirtualizerTest extends TestCase
{
    public function testA() {
        $ns = (new DtoGenerator)->build(
            dto\schema(
                namespace: 'Test_Foo',
                objects: [ dto\object(name: 'Bar') ]
            )
        );

        Virtualizer::register(...$ns);

        $bar = new \Test_Foo\Bar;
        $this->assertInstanceOf('\\Test_Foo\\Bar', $bar);

    }
}
