<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema\Types;

use Closure;
use Klkvsk\DtoGenerator\Schema\Schema;

class ListType extends Type
{
    public function __construct(
        public readonly Type $elementType,
    ) {

    }

    public function buildTypeId(Schema $schema): string
    {
        return 'array';
    }

    public function buildTypeHint(Schema $schema): string
    {
        $subtype = $this->elementType->buildTypeHint($schema);
        return "list<$subtype>";
    }

    public function buildImporter(Schema $schema): ?Closure
    {
        $subImporter = $this->elementType->buildImporter($schema);
        if ($subImporter === null) {
            return static fn ($array) => (array)$array;
        }

        return fn ($array) => array_map(
            $subImporter,
            (array)$array
        );
    }


}