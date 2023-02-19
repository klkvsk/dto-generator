<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema\Types;

use Klkvsk\DtoGenerator\Schema\Schema;

class ExternalType extends Type
{
    public function __construct(
        protected string $className,
        protected \Closure $importer
    ) {
    }

    public function buildTypeId(Schema $schema): string
    {
        return $this->className;
    }

    public function buildImporter(Schema $schema): \Closure
    {
        return $this->importer;
    }
}
