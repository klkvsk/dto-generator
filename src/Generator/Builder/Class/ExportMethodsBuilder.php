<?php

namespace Klkvsk\DtoGenerator\Generator\Builder\Class;

use Klkvsk\DtoGenerator\Schema\Dto;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class ExportMethodsBuilder implements ClassMembersBuilderInterface
{
    const TO_ARRAY_METHOD_NAME = 'toArray';

    public function __construct(
        protected bool    $toArray = true,
        protected bool    $jsonSerialize = true,
        protected ?string $dateFormat = null,
    )
    {
    }

    public function build(Dto $object, PhpNamespace $ns, ClassType $class): void
    {
        if ($this->toArray) {
            $toArrayMethod = $class->addMethod('toArray')
                ->setPublic()
                ->addBody('$array = [];')
                ->addBody('foreach (get_mangled_object_vars($this) as $var => $value) {')
                ->addBody('    $var = preg_replace("/.+\0/", "", $var);');

            if ($this->dateFormat) {
                $toArrayMethod
                    ->addBody('    if ($value instanceof \\DateTimeInterface) {')
                    ->addBody('        $value = $value->format(?);', [$this->dateFormat])
                    ->addBody('    }');
            }

            $toArrayMethod
                ->addBody('    if (is_object($value) && method_exists($value, ?)) {', [self::TO_ARRAY_METHOD_NAME])
                ->addBody('        $value = $value->?();', [self::TO_ARRAY_METHOD_NAME])
                ->addBody('    }')
                ->addBody('    $array[$var] = $value;')
                ->addBody('}')
                ->addBody('return $array;')
                ->setReturnType('array');
        }

        if ($this->jsonSerialize) {
            $class->addImplement('\\JsonSerializable');
            $jsonSerializeMethod = $class->addMethod('jsonSerialize')
                ->setPublic()
                ->addBody('$array = [];')
                ->addBody('foreach (get_mangled_object_vars($this) as $var => $value) {')
                ->addBody('    $var = preg_replace("/.+\0/", "", $var);');

            if ($this->dateFormat) {
                $jsonSerializeMethod
                    ->addBody('    if ($value instanceof \\DateTimeInterface) {')
                    ->addBody('        $value = $value->format(?);', [$this->dateFormat])
                    ->addBody('    }');
            }

            $jsonSerializeMethod
                ->addBody('    if ($value instanceof \\JsonSerializable) {')
                ->addBody('        $value = $value->jsonSerialize();')
                ->addBody('    }')
                ->addBody('    $array[$var] = $value;')
                ->addBody('}')
                ->addBody('return $array;')
                ->addAttribute('ReturnTypeWillChange');
        }
    }
}

