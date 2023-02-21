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
            ->setType($enum->backedType);

        foreach ($enum->cases as $value => $case) {
            $class->addCase($case, $value);
        }

        return $class;
    }

}
