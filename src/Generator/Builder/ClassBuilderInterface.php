<?php

namespace Klkvsk\DtoGenerator\Generator\Builder;

use Klkvsk\DtoGenerator\Schema\Dto;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

interface ClassBuilderInterface
{
    public function build(Dto $object, PhpNamespace $ns): ClassType;
}
