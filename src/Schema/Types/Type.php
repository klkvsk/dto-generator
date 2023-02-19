<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema\Types;

use Klkvsk\DtoGenerator\Schema\Schema;

abstract class Type
{
    abstract public function buildImporter(Schema $schema): ?\Closure;

    abstract public function buildTypeId(Schema $schema): string;

    public function buildTypeHint(Schema $schema): string
    {
        return $this->buildTypeId($schema);
    }
}
