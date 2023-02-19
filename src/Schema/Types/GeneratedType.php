<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema\Types;

use Klkvsk\DtoGenerator\Schema\Schema;

abstract class GeneratedType extends Type
{
    public function __construct(protected readonly string $objectName) {
    }

    abstract protected function getCreatorMethod(): string;

    public function buildTypeId(Schema $schema): string
    {
        $object = $schema->findObject($this->objectName);
        return $object->name;
    }

    public function buildImporter(Schema $schema): ?\Closure
    {
        $className = $this->buildTypeId($schema);
        $methodName = $this->getCreatorMethod();
        return fn (array $data) => call_user_func([ $className, $methodName ]);
    }
}