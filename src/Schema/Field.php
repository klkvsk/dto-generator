<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema;

use Klkvsk\DtoGenerator\Schema\Types\ListType;
use Klkvsk\DtoGenerator\Schema\Types\Type;
use Spatie\Cloneable\Cloneable;

class Field
{
    use Cloneable;

    public readonly ?Dto $object;

    public function __construct(
        public readonly string $name,
        public readonly Type   $type,
        public readonly bool   $required = false,
        public readonly mixed  $default = null,

        /** @var \Closure[] */
        public readonly array  $filters = [],
        /** @var \Closure[] */
        public readonly array  $validators = [],
    ) {
        $this->object = null;
    }

    public function isNullable(): bool
    {
        return $this->required === false;
    }

    public function withObject(Dto $object): static
    {
        return $this->with(object: $object);
    }
}
