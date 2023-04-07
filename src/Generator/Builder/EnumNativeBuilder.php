<?php

namespace Klkvsk\DtoGenerator\Generator\Builder;

use Klkvsk\DtoGenerator\Schema\Enum;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\PhpNamespace;

class EnumNativeBuilder implements EnumBuilderInterface
{
    public function build(Enum $enum, PhpNamespace $ns): EnumType|ClassType
    {
        $class = $ns->addEnum($enum->getShortName())
            ->setType($enum->getBackedType());

        foreach ($enum->getCases() as $case => $value) {
            $class->addCase($case, $value);
        }

        return $class;
    }

}
