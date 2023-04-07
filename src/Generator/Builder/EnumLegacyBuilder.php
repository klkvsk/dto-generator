<?php

namespace Klkvsk\DtoGenerator\Generator\Builder;

use Klkvsk\DtoGenerator\Schema\Enum;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;

class EnumLegacyBuilder implements EnumBuilderInterface
{
    public function build(Enum $enum, PhpNamespace $ns): EnumType|ClassType
    {
        $class = $ns->addClass($enum->getShortName())->setFinal();

        $class
            ->addComment("@property-read string \$name")
            ->addComment("@property-read {$enum->getBackedType()} \$value");

        $class->addProperty('map')
            ->setStatic()
            ->setPrivate()
            ->setNullable()
            ->setType('array');

        $class->addProperty('name')
            ->setPrivate()
            ->setType('string');

        $class->addProperty('value')
            ->setPrivate()
            ->setType($enum->getBackedType());

        $enumCtor = $class->addMethod('__construct')
            ->setPrivate();
        $enumCtor->addParameter('name')
            ->setType('string');
        $enumCtor->addParameter('value')
            ->setType($enum->getBackedType());
        $enumCtor
            ->addBody('$this->name = $name;')
            ->addBody('$this->value = $value;');

        $casesMethod = $class->addMethod('cases')
            ->setComment('@return static[]')
            ->setStatic()
            ->setPublic()
            ->setReturnType('array');

        $class
            ->addMethod('__get')
            ->setParameters([ new Parameter('propertyName') ])
            ->setPublic()
            ->addBody('switch ($propertyName) {')
            ->addBody('    case "name":')
            ->addBody('        return $this->name;')
            ->addBody('    case "value":')
            ->addBody('        return $this->value;')
            ->addBody('    default:')
            ->addBody('        trigger_error("Undefined property: ' . $enum->getShortName() . '::$propertyName");')
            ->addBody('        return null;')
            ->addBody('}');

        // ::tryFrom(value)
        $class->addMethod('tryFrom')
            ->setStatic()
            ->setPublic()
            ->setReturnType('self')
            ->setReturnNullable()
            ->setParameters([
                (new Parameter('value'))->setType($enum->getBackedType())
            ])
            ->addBody('$cases = self::cases();')
            ->addBody('return $cases[$value] ?? null;');

        // ::from($value)
        $class->addMethod('from')
            ->setStatic()
            ->setPublic()
            ->setReturnType('self')
            ->setParameters([
                (new Parameter('value'))->setType($enum->getBackedType())
            ])
            ->addBody('$case = self::tryFrom($value);')
            ->addBody('if (!$case) {')
            ->addBody('    throw new \ValueError(sprintf(')
            ->addBody('        "%s is not a valid backing value for enum %s",')
            ->addBody('        var_export($value, true), self::class')
            ->addBody('    ));')
            ->addBody('}')
            ->addBody('return $case;');

        $casesMap = [];
        foreach ($enum->getCases() as $case => $value) {
            $class->addMethod($case)
                ->setStatic()
                ->setPublic()
                ->setReturnType('self')
                ->addBody('return self::from(?);', [$value]);

            $casesMap[] = new Literal('new self(?, ?)', [$case, $value]);
        }

        $casesMethod->addBody('return self::$map = self::$map \?\? ?;', [$casesMap]);

        $class->addImplement('\\JsonSerializable');
        $class->addMethod('jsonSerialize')
            ->setReturnType($enum->getBackedType())
            ->addBody('return $this->value;');

        $class->addMethod('__toString')
            ->setPublic()
            ->addBody('return $this->value;');

        return $class;
    }

}
