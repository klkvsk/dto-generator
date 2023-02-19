<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema\Types;

class EnumType extends GeneratedType
{
    protected function getCreatorMethod(): string
    {
        return 'from';
    }

}