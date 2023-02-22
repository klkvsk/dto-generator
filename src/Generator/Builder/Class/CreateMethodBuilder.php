<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Generator\Builder\Class;

use Klkvsk\DtoGenerator\Schema\Dto;
use Klkvsk\DtoGenerator\Schema\ExtraFieldsPolicy;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;

class CreateMethodBuilder implements ClassMembersBuilderInterface
{
    const METHOD_NAME = 'create';

    public function __construct(
        protected bool $withCreatorVariadic = false,
        protected ExtraFieldsPolicy $extraFieldsPolicy = ExtraFieldsPolicy::IGNORE
    )
    {

    }

    public function build(Dto $object, PhpNamespace $ns, ClassType $class): void
    {
        $creator = $class->addMethod(self::METHOD_NAME);
        $creator
            ->setStatic()
            ->setPublic()
            ->setComment('@return static')
            ->setReturnType('self');

        $creator->addParameter('data')
            ->setType('array');

        if ($class->hasMethod(DefaultsMethodBuilder::METHOD_NAME)) {
            $creator
                ->addBody('// defaults')
                ->addBody('$data += static::?();', [DefaultsMethodBuilder::METHOD_NAME])
                ->addBody('');
        }

        if ($class->hasMethod(RequiredMethodBuilder::METHOD_NAME)) {
            $creator
                ->addBody('// check required')
                ->addBody('if ($diff = array_diff(static::?(), array_keys($data))) {', [RequiredMethodBuilder::METHOD_NAME])
                ->addBody('    throw new \\InvalidArgumentException("missing keys: " . implode(", ", $diff));')
                ->addBody('}')
                ->addBody('');
        }

        $creator
            ->addBody('// import')
            ->addBody('$constructorParams = [];');
        if ($this->extraFieldsPolicy != ExtraFieldsPolicy::IGNORE) {
            $creator
                ->addBody('$extraFields = [];');
        }
        $creator
            ->addBody('foreach ($data as $key => $value) {');
        if ($class->hasMethod(ProcessorsMethodBuilder::METHOD_NAME)) {
            $creator
                ->addBody('    foreach (static::?($key) as $type => $processor) if ($value !== null) {', [ProcessorsMethodBuilder::METHOD_NAME])
                ->addBody('        if ($type === "validator" && call_user_func($processor, $value) === false) {')
                ->addBody('            throw new \\InvalidArgumentException("invalid value at key: $key");')
                ->addBody('        } else {')
                ->addBody('            $value = call_user_func($processor, $value);')
                ->addBody('        }')
                ->addBody('    }');
        }
        $creator
            ->addBody('    if (property_exists(static::class, $key)) {')
            ->addBody('        $constructorParams[$key] = $value;');
        if ($this->extraFieldsPolicy != ExtraFieldsPolicy::IGNORE) {
            $creator
                ->addBody('    } else {')
                ->addBody('        $extraFields[$key] = $value;');
        }

        $creator
            ->addBody('    }')
            ->addBody('}')
            ->addBody('');

        $constructor = $class->getMethod('__construct');
        $constructorParams = array_map(
            fn(Parameter $p) => new Literal("\$constructorParams[\"{$p->getName()}\"]"),
            $constructor->getParameters()
        );

        $creator
            ->addBody('// create');

        if ($this->extraFieldsPolicy === ExtraFieldsPolicy::THROW) {
            $creator
                ->addBody('if (!empty($extraFields)) {')
                ->addBody('    throw new \\InvalidArgumentException("found extra fields: " . json_encode($extraFields));')
                ->addBody('}');
        }

        if ($this->withCreatorVariadic) {
            $newStatement = 'new static(...$constructorParams);';
        } else {
            $newStatement = "new static(\n";
            foreach ($constructorParams as $constructorParamLiteral) {
                $newStatement .= "    $constructorParamLiteral,\n";
            }
            $newStatement = rtrim($newStatement, ",\n") . "\n";
            $newStatement .= ");";
        }

        if ($this->extraFieldsPolicy === ExtraFieldsPolicy::COLLECT) {
            $creator
                ->addBody('$self = ' . $newStatement)
                ->addBody('(self::$extraFields ??= new \WeakMap())->offsetSet($self, $extraFields);')
                ->addBody('return $self;');

            $class->addProperty('extraFields')->setProtected()->setStatic()->setType('\\WeakMap');
            $class->addMethod('extra')->setPublic()->setReturnType('array')
                ->addBody('return self::$extraFields[$this] ?? [];');
        } else {
            $creator
                ->addBody('return ' . $newStatement);
        }


    }
}
