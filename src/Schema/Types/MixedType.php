<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema\Types;

use Klkvsk\DtoGenerator\DtoGenerator;
use Klkvsk\DtoGenerator\Schema\Schema;

class MixedType extends Type
{
    public function buildImporter(Schema $schema): ?\Closure
    {
        return null;
    }

    public function buildTypeId(Schema $schema): string
    {
        return DtoGenerator::$useMixedType ? 'mixed' : '';
    }

    public function buildTypeHint(Schema $schema): string
    {
        return 'mixed';
    }

}
