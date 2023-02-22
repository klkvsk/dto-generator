<?php

namespace Klkvsk\DtoGenerator\Generator\Builder\Class;

use Klkvsk\DtoGenerator\Schema\Dto;
use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class DefaultsMethodBuilder implements ClassMembersBuilderInterface
{
    const METHOD_NAME = 'defaults';

    public function __construct(
        protected bool $withPublicAccess = false
    )
    {
    }

    public function build(Dto $object, PhpNamespace $ns, ClassType $class): void
    {
        $defaultsArray = [];
        foreach ($object->fields as $field) {
            if ($field->default !== null) {
                $defaultsArray[$field->name] = $field->default;
            }
        }

        if (empty($defaultsArray) && ! $object->extends) {
            return;
        }

        $method = $class->addMethod(static::METHOD_NAME)
            ->setVisibility($this->withPublicAccess ? ClassLike::VisibilityPublic : ClassLike::VisibilityProtected)
            ->setStatic()
            ->setReturnType('array');

        if ($object->extends) {
            $method->addBody('return array_merge(');
            $method->addBody('    method_exists(parent::class, "' . self::METHOD_NAME . '") ? parent::' . self::METHOD_NAME . '() : [],');
            $method->addBody('    ?', [ $defaultsArray ]);
            $method->addBody(');');
        } else {
            $method->addBody('return ?;', [ $defaultsArray ]);
        }
    }


}
