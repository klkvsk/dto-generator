<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Generator\Builder\Class;

use Klkvsk\DtoGenerator\Exception\GeneratorException;
use Klkvsk\DtoGenerator\Generator\CodeStyle;
use Klkvsk\DtoGenerator\Generator\PrintableClosure;
use Klkvsk\DtoGenerator\Schema\Dto;
use Klkvsk\DtoGenerator\Schema\Field;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class ProcessorsMethodBuilder implements ClassMembersBuilderInterface
{
    const METHOD_NAME = 'processors';

    public function __construct(
        protected bool $withFirstClassCallableSyntax = true
    )
    {

    }

    /** @throws GeneratorException */
    public function build(Dto $object, PhpNamespace $ns, ClassType $class): void
    {
        $body = $this->buildBody($object);
        if (empty($body)) {
            return;
        }
        $method = $class->addMethod(static::METHOD_NAME);
        $method
            ->setProtected()
            ->setStatic()
            ->setReturnType('\Generator')
            ->addBody($body);

        $method->addParameter('key')
            ->setType('string');
    }

    /** @throws GeneratorException */
    protected function buildBody(Dto $object): string
    {
        $cases = [];
        foreach ($object->fields as $field) {
            $case = $this->buildCase($field);
            if ($case) {
                $cases[$case] = array_merge($cases[$case] ?? [], [$field->name]);
            }
        }

        if (empty($cases)) {
            return '';
        }

        $switchBody = '';
        foreach ($cases as $case => $caseFields) {
            foreach ($caseFields as $caseField) {
                $switchBody .= "case \"$caseField\":\n";
            }
            $switchBody .= trim($case) . "\n";
            $switchBody .= "return;\n\n";
        }

        $body = "switch (\$key) {\n" . trim($switchBody) . "\n}\n";

        if ($object->extends) {
            $parentMethodName = self::METHOD_NAME;
            $body .= "if (method_exists(parent::class, '$parentMethodName')) {\n";
            $body .= "    yield from parent::$parentMethodName(\$key);\n";
            $body .= "}\n";
        }

        return CodeStyle::indent($body);
    }

    /** @throws GeneratorException */
    protected function buildCase(Field $field): ?string
    {
        $importer = $field->type->buildImporter($field->object->schema);

        if (!$importer && !$field->filters && !$field->validators) {
            return null;
        }

        $yields = '';

        foreach ($field->filters as $filter) {
            $yields .= "yield 'filter' => {$this->buildClosure($filter)};\n";
        }

        if ($importer) {
            $yields .= "yield 'importer' => {$this->buildClosure($importer)};\n";
        }

        foreach ($field->validators as $validator) {
            $yields .= "yield 'validator' => {$this->buildClosure($validator)};\n";
        }

        return $yields;
    }

    /** @throws GeneratorException */
    protected function buildClosure(\Closure $closure): string
    {
        try {
            return (string)(new PrintableClosure($closure, $this->withFirstClassCallableSyntax));
        } catch (\ReflectionException $e) {
            throw new GeneratorException('Failed to print closure', 0, $e);
        }
    }
}
