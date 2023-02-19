<?php

namespace Klkvsk\DtoGenerator\Test\Schema;

use Klkvsk\DtoGenerator\Schema\Schema;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klkvsk\DtoGenerator\Schema\Schema
 */
class SchemaTest extends TestCase
{
    /**
     * @dataProvider namespaces
     */
    public function testNewWithNamespace($namespace)
    {
        $schema = new Schema($namespace);
        $this->assertEquals($namespace, $schema->namespace);
    }

    public static function namespaces(): array
    {
        return [ [null], ["Foo"], ["\\Foo"], ["Foo\\Bar"], ["\\Foo\\Bar"] ];
    }
}
