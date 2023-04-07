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
            ->addComment('Readonly properties:')
            ->addComment("@property-read string \$name")
            ->addComment("@property-read {$enum->getBackedType()} \$value")
            ->addComment("")
            ->addComment("Cases:");


        $class->addProperty('instances')
            ->setStatic()
            ->setPrivate()
            ->setType('array')
            ->setValue([]);

        $class->addProperty('cases')
            ->setStatic()
            ->setPrivate()
            ->setType('array')
            ->setValue($enum->getCases());

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
            ->setParameters([ new Parameter('name') ])
            ->setPublic()
            ->addBody('switch ($name) {')
            ->addBody('    case "name":')
            ->addBody('        return $this->name;')
            ->addBody('    case "value":')
            ->addBody('        return $this->value;')
            ->addBody('    default:')
            ->addBody('        trigger_error("Undefined property: ' . $enum->getShortName() . '::$name", E_USER_WARNING);')
            ->addBody('        return null;')
            ->addBody('}');

        $class
            ->addMethod('__callStatic')
            ->setParameters([ new Parameter('name'), new Parameter('args') ])
            ->setStatic()
            ->setPublic()
            ->addBody('$instance = self::$instances[$name] ?? null;')
            ->addBody('if ($instance === null) {')
            ->addBody('    if (!array_key_exists($name, self::$cases)) {')
            ->addBody('        throw new \ValueError("unknown case ?");', [ new Literal("'{$enum->getShortName()}::\$name'") ])
            ->addBody('    }')
            ->addBody('    self::$instances[$name] = $instance = new self($name, self::$cases[$name]);')
            ->addBody('}')
            ->addBody('return $instance;');

        $class->addMethod('tryFrom')
            ->setStatic()
            ->setPublic()
            ->setReturnType('self')
            ->setReturnNullable()
            ->setParameters([
                (new Parameter('value'))->setType($enum->getBackedType())
            ])
            ->addBody('$case = array_search($value, self::$cases, true);')
            ->addBody('return $case ? self::$case() : null;');

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
            $class->addComment("@method static {$enum->getShortName()} $case");
            $casesMap[] = new Literal("self::$case()");
        }

        $casesMethod->addBody('return ?;', [$casesMap]);

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
