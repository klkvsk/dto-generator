<?php

namespace Klkvsk\DtoGenerator\Schema;

use Klkvsk\DtoGenerator\Schema\Type;

class Field
{
    public function __construct(
        public readonly string $name,
        public readonly Type   $type,
        public readonly bool   $required = false,
        public readonly mixed  $default = null,

        /** @var \Closure[] */
        public readonly array  $filters = [],
        /** @var \Closure[] */
        public readonly array  $validators = [],
    )
    {
    }


}
