<?php

namespace Klkvsk\DtoGenerator\Generator\Builder\Class;

use Klkvsk\DtoGenerator\Schema\Dto;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class ExportMethodsBuilder implements ClassMembersBuilderInterface
{
    public function __construct(
        protected bool $toArray = true,
        protected bool $jsonSerialize = true,
    )
    {
    }

    public function build(Dto $object, PhpNamespace $ns, ClassType $class): void
    {
        if ($this->toArray) {
            $class->addMethod('toArray')
                ->setPublic()
                ->addBody('$array = [];')
                ->addBody('foreach (get_mangled_object_vars($this) as $var => $value) {')
                ->addBody('    $var = preg_replace("/.+\0/", "", $var);')
                ->addBody('    if (is_object($value) && method_exists($value, "toArray")) {')
                ->addBody('        $value = call_user_func([$value, "toArray"]);')
                ->addBody('    }')
                ->addBody('    $array[$var] = $value;')
                ->addBody('}')
                ->addBody('return $array;')
                ->setReturnType('array');
        }

        if ($this->jsonSerialize) {
            $class->addImplement('\\JsonSerializable');
            $class->addMethod('jsonSerialize')
                ->setPublic()
                ->addBody('$array = [];')
                ->addBody('foreach (get_mangled_object_vars($this) as $var => $value) {')
                ->addBody('    $var = preg_replace("/.+\0/", "", $var);')
                ->addBody('    if (is_object($value) && $value instanceof \\JsonSerializable) {')
                ->addBody('        $value = $value->jsonSerialize();')
                ->addBody('    }')
                ->addBody('    $array[$var] = $value;')
                ->addBody('}')
                ->addBody('return $array;')
                ->addAttribute('ReturnTypeWillChange');
        }
    }
}

