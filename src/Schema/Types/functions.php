<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema\Types;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Klkvsk\DtoGenerator\Schema\Dto;
use Klkvsk\DtoGenerator\Schema\Enum;

function int(): BuiltinType
{
    return new BuiltinType('int', 'intval');
}

function float(): BuiltinType
{
    return new BuiltinType('float', 'floatval');
}

function string(): BuiltinType
{
    return new BuiltinType('string', 'strval');
}

function bool(): BuiltinType
{
    return new BuiltinType('bool', 'boolval');
}

/**
 * @param DateTimeZone|null $dateTimeZone
 * @param string|null $format
 * @return Type
 */
function date(DateTimeZone $dateTimeZone = null, string $format = null): Type
{
    if ($format && $dateTimeZone) {
        $importer = static fn ($d) => DateTimeImmutable::createFromFormat($format, $d, $dateTimeZone);
    } else if ($format) {
        $importer = static fn ($d) => DateTimeImmutable::createFromFormat($format, $d);
    } else if ($dateTimeZone) {
        $importer = static fn ($d) => new DateTimeImmutable($d, $dateTimeZone);
    } else {
        $importer = static fn ($d) => new DateTimeImmutable($d);
    }
    return external(DateTimeInterface::class, $importer);
}

/**
 * @param string|Dto $dto
 * @return DtoType
 */
function object(Dto|string $dto): DtoType
{
    $dtoName = $dto instanceof Dto ? $dto->name : $dto;
    return new DtoType($dtoName);
}


/**
 * @param string|Enum $enum
 * @return EnumType
 */
function enum(string|Enum $enum): EnumType
{
    $enumName = $enum instanceof Enum ? $enum->name : $enum;
    return new EnumType($enumName);
}

/**
 * @param Type|null $elementType sub-type of array elements (t\mixed if null)
 * @return ListType
 */
function list_(Type $elementType = null): ListType
{
    return new ListType($elementType ?: mixed());
}

function mixed(): MixedType
{
    return new MixedType();
}

/**
 * @param string $className
 * @param callable $importer
 * @return Type
 */
function external(string $className, callable $importer): Type
{
    return new ExternalType($className, $importer);
}
