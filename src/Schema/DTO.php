<?php

namespace Klkvsk\DtoGenerator\Schema;

class DTO extends AbstractObject
{
    /**
     * @param string $name
     * @param array<Field> $fields
     */
    public function __construct(
        string                $name,
        public readonly array $fields,
    )
    {
        parent::__construct($name);
    }
}