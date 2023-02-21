<?php

namespace Klkvsk\DtoGenerator\Generator\Builder;

use Klkvsk\DtoGenerator\Schema\Enum;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\PhpNamespace;

interface EnumBuilderInterface
{
    public function build(Enum $enum, PhpNamespace $ns): EnumType|ClassType;
}
