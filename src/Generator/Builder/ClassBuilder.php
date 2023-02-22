<?php

namespace Klkvsk\DtoGenerator\Generator\Builder;

use Klkvsk\DtoGenerator\Generator\Builder\Class\ClassMembersBuilderInterface;
use Klkvsk\DtoGenerator\Schema\Dto;
use Klkvsk\DtoGenerator\Schema\Field;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class ClassBuilder implements ClassBuilderInterface
{
    /** @var ClassMembersBuilderInterface[] */
    protected array $membersBuilders = [];

    public function addMembersBuilder(ClassMembersBuilderInterface $membersBuilder): static
    {
        $this->membersBuilders[] = $membersBuilder;
        return $this;
    }

    public function build(Dto $object, PhpNamespace $ns): ClassType
    {
        $class = $ns->addClass($object->getShortName());

        foreach ($object->implements as $interface) {
            $class->addImplement($interface);
        }
        foreach ($object->uses as $trait) {
            $class->addTrait($trait);
        }
        if ($object->extends) {
            $parentObject = $object->schema->findObject($object->extends);
            $extends = $parentObject ? $parentObject->name : $object->extends;
            $class->setExtends($extends);
        }

        foreach ($this->membersBuilders as $membersBuilder) {
            $membersBuilder->build($object, $ns, $class);
        }

        return $class;
    }


}
