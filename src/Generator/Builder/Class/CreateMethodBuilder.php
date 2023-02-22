<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Generator\Builder\Class;

use Klkvsk\DtoGenerator\Schema\Dto;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class CreateMethodBuilder implements ClassMembersBuilderInterface
{
    const METHOD_NAME = 'create';

    public function __construct(
        protected bool $withCreatorVariadic = false
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

        if ($class->hasMethod(ProcessorsMethodBuilder::METHOD_NAME)) {
            $creator
                ->addBody('// process')
                ->addBody('foreach ($data as $key => &$value) {')
                ->addBody('    foreach (static::?($key) as $type => $processor) if ($value !== null) {', [ProcessorsMethodBuilder::METHOD_NAME])
                ->addBody('        if ($type === "validator" && call_user_func($processor, $value) === false) {')
                ->addBody('            throw new \\InvalidArgumentException("invalid value at key: $key");')
                ->addBody('        } else {')
                ->addBody('            $value = call_user_func($processor, $value);')
                ->addBody('        }')
                ->addBody('    }')
                ->addBody('}')
                ->addBody('');
        }

        $creator->addBody('// create');
        if ($this->withCreatorVariadic) {
            $creator->addBody('return new static(...$data);');
        } else {
            $creator->addBody('return new static(');
            $constructor = $class->getMethod('__construct');
            foreach ($constructor->getParameters() as $constructorParameter) {
                $creator->addBody('    $data[?],', [$constructorParameter->getName()]);
            }
            $creator->addBody(');');
        }
    }
}
