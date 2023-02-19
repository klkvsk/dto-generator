<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema\Types;

class DtoType extends GeneratedType
{
    protected function getCreatorMethod(): string
    {
        return 'create';
    }
}