<?php

namespace Klkvsk\DtoGenerator\Generator\Builder\Class;

use Klkvsk\DtoGenerator\Schema\Dto;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

interface ClassMembersBuilderInterface
{
    public function build(Dto $object, PhpNamespace $ns, ClassType $class): void;
}
