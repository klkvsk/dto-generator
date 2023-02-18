<?php

namespace Klkvsk\DtoGenerator\Schema;

class Type
{
    public function __construct(
        public readonly string $phpType,
        public readonly ?\Closure $importer,
        public readonly bool $sameNamespace = false,
        public readonly ?string $phpTypeHint = null,
    ) {
    }

    public static function int()
    {
        return new static('int', static fn ($i) => intval($i));
    }

    public static function string()
    {
        return new static('string', static fn ($s) => strval($s));
    }

    public static function float()
    {
        return new static('float', static fn ($f) => floatval($f));
    }

    public static function bool()
    {
        return new static('bool', static fn ($b) => boolval($b));
    }

    public static function mixed()
    {
        return new static(null, null);
    }

    public static function dateTime()
    {
        return new static(\DateTimeInterface::class, static fn ($d) => new \DateTimeImmutable($d));
    }

    /**
     * @param class-string<DTO>|DTO $class
     * @return static
     */
    public static function dto($dto)
    {
        $className = $dto instanceof DTO ? $dto->name : (string)$dto;
        $importer = static fn (array $data) => call_user_func([ $className, 'create' ], $data);
        return new static($className, $importer, sameNamespace: true);
    }

    public static function enum(Enum $enum)
    {
        $className = $enum->name;
        $importer = static fn (array $value) => call_user_func([ $className, 'from' ], $value);
        return new static($className, $importer, sameNamespace: true);
    }
    public static function arrayOf(Type $type)
    {
        $subImporter = $type->importer;
        $importer = static fn (array $array) => array_map(
            $subImporter,
            $array
        );
        return new static('array', $importer, phpTypeHint: $type->phpType . '[]');
    }

}