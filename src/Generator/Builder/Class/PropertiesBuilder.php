<?php

namespace Klkvsk\DtoGenerator\Generator\Builder\Class;

use Klkvsk\DtoGenerator\Exception\GeneratorException;
use Klkvsk\DtoGenerator\Generator\CodeStyle;
use Klkvsk\DtoGenerator\Generator\ClosurePrinter;
use Klkvsk\DtoGenerator\Schema\Dto;
use Klkvsk\DtoGenerator\Schema\Types\ListType;
use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PromotedParameter;

class PropertiesBuilder implements ClassMembersBuilderInterface
{
    public function __construct(
        public readonly bool $withPromotedParameters = true,
        public readonly bool $withPublicParameters = false,
        public readonly bool $withReadonlyParameters = false,
        public readonly bool $withGetters = true,
        public readonly bool $withSetters = false,
        public readonly bool $withListTypeChecks = true,
        public readonly bool $withReturnStatic = true,
        public readonly bool $withForcedPhpDoc = false,
        public readonly bool $withFirstClassCallableSyntax = false,
    )
    {
    }

    public function build(Dto $object, PhpNamespace $ns, ClassType $class): void
    {
        $constructor = $class->addMethod('__construct');
        $constructor->setPublic();

        if ($object->extends) {
            $parent = $object->schema->findObject($object->extends);
            if ($parent instanceof Dto) {
                $parentClass = new ClassType();
                $this->build($parent, $ns, $parentClass);
                $parentConstructor = $parentClass->getMethod('__construct');
                $constructor->setComment($parentConstructor->getComment());
                $constructor->setParameters(
                    array_map(
                        $this->depromoteParameter(...),
                        $parentConstructor->getParameters()
                    )
                );
                $parentConstructorPass = array_map(
                    fn(Parameter $p) => new Literal("\${$p->getName()}"),
                    $parentConstructor->getParameters()
                );

                $constructor->addBody('parent::__construct(...?);', [$parentConstructorPass]);
            }
        }

        foreach ($object->fields as $field) {
            $phpType = $field->type->buildTypeId($object->schema);
            $phpTypeHint = $field->type->buildTypeHint($object->schema);
            $buildVarDoc = ($phpTypeHint != $phpType) || $this->withForcedPhpDoc;

            $phpTypeHint = $ns->simplifyType($phpTypeHint);
            if ($field->isNullable()) {
                $phpTypeHint = "?$phpTypeHint";
            }

            if ($field->type instanceof ListType && $this->withListTypeChecks) {
                $constructor->addBody(
                    sprintf(
                        '(function(%s ...$_) {})( ...%s);',
                        $ns->simplifyType($field->type->elementType->buildTypeId($object->schema)),
                        "$$field->name"
                    )
                );
            }

            if ($this->withPromotedParameters) {
                $parameter = $constructor->addPromotedParameter($field->name);
                $parameter->setVisibility(
                    $this->withPublicParameters
                        ? ClassLike::VisibilityPublic
                        : ClassLike::VisibilityProtected
                );
                $parameter->setReadOnly($this->withReadonlyParameters);

                if ($buildVarDoc) {
                    $constructor->addComment("@param $phpTypeHint \$$field->name");
                }
                $property = null;
            } else {
                $parameter = $constructor->addParameter($field->name);
                $property = $class->addProperty($field->name)->setProtected();
                $property->setVisibility(
                    $this->withPublicParameters
                        ? ClassLike::VisibilityPublic
                        : ClassLike::VisibilityProtected
                );
                $property->setReadOnly($this->withReadonlyParameters);
                if ($buildVarDoc) {
                    $property->addComment("@var $phpTypeHint \$$field->name");
                }
                $constructor->addBody("\$this->$field->name = \$$field->name;");
            }

            $parameter->setNullable($field->isNullable())->setType($phpType);
            $property?->setNullable($field->isNullable())->setType($phpType);

            if ($field->default !== null || $field->isNullable()) {
                $parameter->setDefaultValue($field->default);
            }
            if ($field->default === null && $field->type instanceof ListType) {
                $parameter->setDefaultValue([]);
            }

            if ($this->withGetters) {
                $getter = $class->addMethod('get' . ucfirst($field->name))
                    ->setPublic()
                    ->setReturnNullable($field->isNullable())
                    ->setReturnType($phpType)
                    ->setBody("return \$this->$field->name;");

                if ($buildVarDoc) {
                    $getter->addComment("@return $phpTypeHint");
                }
            }

            if ($this->withSetters && !$this->withReadonlyParameters) {
                $setter = $class->addMethod('set' . ucfirst($field->name))
                    ->setPublic()
                    ->setReturnType($this->withReturnStatic ? 'static' : 'self')
                    ->addBody("\$this->$field->name = \$$field->name;")
                    ->addBody("return \$this;");

                $setter->addParameter($field->name)
                    ->setType($phpType)
                    ->setNullable($field->isNullable());

                if ($buildVarDoc || $this->withReturnStatic) {
                    $setter->addComment("@param $phpTypeHint \$$field->name");
                    $setter->addComment("@return \$this");
                }
            }
        }

        $constructorParameters = $constructor->getParameters();
        usort($constructorParameters, fn (Parameter $a, Parameter $b) => $a->isNullable() <=> $b->isNullable());
        $constructor->setParameters($constructorParameters);
    }

    protected function depromoteParameter(Parameter $parameter): Parameter
    {
        if (!$parameter instanceof PromotedParameter) {
            return clone $parameter;
        }

        $copy = new Parameter($parameter->getName());
        $copy->setNullable($parameter->isNullable());
        if ($parameter->hasDefaultValue()) {
            $copy->setDefaultValue($parameter->getDefaultValue());
        }
        $copy->setType((string)$parameter->getType() ?: null);

        return $copy;
    }

}
