<?php

namespace Klkvsk\DtoGenerator\Generator\Builder\Class;

use Klkvsk\DtoGenerator\Schema\Dto;
use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class RequiredMethodBuilder implements ClassMembersBuilderInterface
{
    const METHOD_NAME = 'required';

    public function __construct(
        protected bool $withPublicAccess = false
    )
    {
    }

    public function build(Dto $object, PhpNamespace $ns, ClassType $class): void
    {
        $requiredArray = [];
        foreach ($object->fields as $field) {
            if ($field->required) {
                $requiredArray[] = $field->name;
            }
        }

        if (empty($requiredArray)) {
            return;
        }

        $method = $class->addMethod(static::METHOD_NAME)
            ->setVisibility($this->withPublicAccess ? ClassLike::VisibilityPublic : ClassLike::VisibilityProtected)
            ->setStatic()
            ->setReturnType('array');

        if ($object->extends) {
            $method->addBody('$required = ?;', [ $requiredArray ]);
            $method->addBody('foreach (class_parents($this) as $parent) {');
            $method->addBody('    if (method_exists($parent, ?)) {', [ self::METHOD_NAME ]);
            $method->addBody('        return array_merge(call_user_func([$parent, ?]), $required);', [ self::METHOD_NAME ]);
            $method->addBody('    }');
            $method->addBody('}');
            $method->addBody('return $required;');
        } else {
            $method->addBody('return ?;', [ $requiredArray ]);
        }
    }
}
