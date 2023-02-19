<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema;

use Klkvsk\DtoGenerator\Schema\Types\Type;

function schema(?string $namespace = null, ?string $outputDir = null, array $objects = []): Schema
{
    return new Schema($namespace, $outputDir, $objects);
}

function enum(string $name, array $cases, EnumValues $enumKeys = EnumValues::AUTO, ?string $backedType = null): Enum
{
    return new Enum($name, $cases, $enumKeys, $backedType);
}

function object(string $name, iterable $fields): Dto
{
    return new Dto($name, $fields);
}

function field(
    string $name, Type $type, bool $required = false, mixed $default = null,
    array  $filters = [], array $validators = []
): Field
{
    return new Field($name, $type, $required, $default, $filters, $validators);
}
