<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema;

use ArrayObject;
use Klkvsk\DtoGenerator\Exception\SchemaException;

class Schema
{
    /**
     * @var ArrayObject<string, Enum>
     */
    public readonly ArrayObject $enums;
    /**
     * @var ArrayObject<string, Dto>
     */
    public readonly ArrayObject $dtos;

    /**
     * @param string|null $namespace
     * @param string|null $outputDir
     * @param iterable<AbstractObject> $objects
     * @throws SchemaException
     */
    public function __construct(
        public readonly ?string $namespace = null,
        public readonly ?string $outputDir = null,
        iterable $objects = [],
    ) {
        $this->enums = new ArrayObject();
        $this->dtos = new ArrayObject();

        foreach ($objects as $object) {
            if ($object instanceof Dto) {
                $this->dto($object);
            } elseif ($object instanceof Enum) {
                $this->enum($object);
            } else {
                throw new SchemaException("Can not add this type: " . get_debug_type($object));
            }
        }
    }

    /**
     * @throws SchemaException
     */
    public function enum(Enum $enum): static
    {
        $this->enums->offsetSet($enum->name, $enum->withSchema($this));
        return $this;
    }

    /**
     * @throws SchemaException
     */
    public function dto(Dto $dto): static
    {
        $this->dtos->offsetSet($dto->name, $dto->withSchema($this));
        return $this;
    }

    public function findObject(string $name): ?AbstractObject
    {
        $object = $this->dtos[$name] ?? $this->enums[$name] ?? null;
        if ($object) {
            return $object;
        } elseif ($name[0] !== '\\') {
            return $this->findObject("\\$this->namespace\\$name");
        } else {
            return null;
        }
    }
}
