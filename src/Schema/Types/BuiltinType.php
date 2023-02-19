<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema\Types;

use Klkvsk\DtoGenerator\Schema\Schema;

class BuiltinType extends Type
{
    protected \Closure $importer;

    public function __construct(
        protected string $phpType,
        callable $importer,
    ) {
        $this->importer = $importer instanceof \Closure ? $importer : $importer(...);
    }

    public function buildTypeId(Schema $schema): string
    {
        return $this->phpType;
    }

    public function buildImporter(Schema $schema): ?\Closure
    {
        return $this->importer;
    }
}
