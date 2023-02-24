<?php

namespace Klkvsk\DtoGenerator\Generator\Builder;

use Klkvsk\DtoGenerator\Schema\Enum;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;

class EnumLegacyBuilder implements EnumBuilderInterface
{
    public function build(Enum $enum, PhpNamespace $ns): EnumType|ClassType
    {
        $class = $ns->addClass($enum->getShortName())->setFinal();

        $class->addProperty('map')->setType('array')->setStatic()->setNullable();
        $class->addProperty('name')->setType('string');
        $class->addProperty('value')->setType($enum->backedType);

        $enumCtor = $class->addMethod('__construct')
            ->setPrivate();
        $enumCtor->addParameter('name')
            ->setType('string');
        $enumCtor->addParameter('value')
            ->setType($enum->backedType);
        $enumCtor
            ->addBody('$this->name = $name;')
            ->addBody('$this->value = $value;');

        $casesMethod = $class->addMethod('cases')
            ->setComment('@return static[]')
            ->setStatic()
            ->setPublic()
            ->setReturnType('array');

        $class
            ->addMethod('name')
            ->setPublic()
            ->setReturnType('string')
            ->setBody('return $this->name;');
        $class
            ->addMethod('value')
            ->setPublic()
            ->setReturnType($enum->backedType)
            ->setBody('return $this->value;');
        $tryFromMethod = $class
            ->addMethod('tryFrom')
            ->setStatic()
            ->setPublic()
            ->setReturnType('self')
            ->setReturnNullable()
            ->addBody('$cases = self::cases();')
            ->addBody('return $cases[$value] ?? null;');
        $tryFromMethod
            ->addParameter('value')
            ->setType($enum->backedType);

        $fromMethod = $class
            ->addMethod('from')
            ->setStatic()
            ->setPublic()
            ->setReturnType('self')
            ->addBody('$case = self::tryFrom($value);')
            ->addBody('if (!$case) {')
            ->addBody('    throw new \ValueError(sprintf(')
            ->addBody('        "%s is not a valid backing value for enum %s",')
            ->addBody('        var_export($value, true), self::class')
            ->addBody('    ));')
            ->addBody('}')
            ->addBody('return $case;');

        $fromMethod
            ->addParameter('value')
            ->setType($enum->backedType);

        $casesMap = [];
        foreach ($enum->cases as $value => $case) {
            $class->addMethod($case)
                ->setStatic()
                ->setPublic()
                ->setReturnType('self')
                ->addBody('return self::from(?);', [$value]);

            $casesMap[$value] = new Literal('new self(?, ?)', [$case, $value]);
        }

        $casesMethod->addBody('return self::$map = self::$map \?\? ?;', [$casesMap]);

        $class->addImplement('\\JsonSerializable');
        $class->addMethod('jsonSerialize')
            ->setReturnType('array')
            ->addBody('return $this->value;');

        $class->addMethod('__toString')
            ->setPublic()
            ->addBody('return $this->value;');

        return $class;
    }

}
