<?php

namespace Klkvsk\DtoGenerator\Schema;

use Klkvsk\DtoGenerator\Exception\SchemaException;

class Schema
{
    public readonly \SplObjectStorage $enums;
    public readonly \SplObjectStorage $dtos;
    /**
     * @param string $namespace
     * @param array<AbstractObject> $objects
     */
    public function __construct(
        public readonly ?string $namespace = null,
        public readonly ?string $outputDir = null,
        array $objects = [],
    )
    {
        $this->enums = new \SplObjectStorage();
        $this->dtos = new \SplObjectStorage();

        foreach ($objects as $object) {
            if ($object instanceof DTO) {
                $this->dto($object);
            } else if ($object instanceof Enum) {
                $this->enum($object);
            } else {
                throw new SchemaException("Can not add this type: " . get_debug_type($object));
            }
        }
    }

    public function enum(Enum $enum)
    {
        $this->enums->attach($enum);
        return $this;
    }


    public function dto(DTO $dto)
    {
        $this->dtos->attach($dto);
        return $this;
    }

}