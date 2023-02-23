<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Generator\Builder\Class;

use Klkvsk\DtoGenerator\Exception\GeneratorException;
use Klkvsk\DtoGenerator\Generator\CodeStyle;
use Klkvsk\DtoGenerator\Generator\ClosurePrinter;
use Klkvsk\DtoGenerator\Schema\Dto;
use Klkvsk\DtoGenerator\Schema\Field;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class ImportersMethodBuilder implements ClassMembersBuilderInterface
{
    const METHOD_NAME = 'importers';

    public function __construct(
        protected ClosurePrinter $closurePrinter,
        protected bool $withMatchSyntax = true,
    )
    {

    }

    /** @throws GeneratorException */
    public function build(Dto $object, PhpNamespace $ns, ClassType $class): void
    {
        $map = $this->buildMap($object);

        $body = $this->buildBody($map, $object->extends !== null);
        if (! $body) {
            return;
        }

        $method = $class->addMethod(static::METHOD_NAME);
        $method
            ->setProtected()
            ->setStatic()
            ->addComment('@return callable[]')
            ->setReturnType('iterable')
            ->addBody(CodeStyle::indent($body));

        $method->addParameter('key')
            ->setType('string');
    }

    /**
     * @param $map list<array{"keys":string[],"callables":string[]}>
     * @return string
     */
    protected function buildBody(array $map, bool $hasParent): ?string
    {
        $useMatch = $this->withMatchSyntax;

        $method = self::METHOD_NAME;
        if ($useMatch) {
            $parentCall = "method_exists(parent::class, \"$method\") ? parent::$method(\$key) : []";
        } else {
            $parentCall = "if (method_exists(parent::class, \"$method\")) {\n yield from parent::$method(\$key);\n}";
        }

        if (empty($map)) {
            if (!$hasParent) {
                return null;
            }

            if ($useMatch) {
                return "return $parentCall;";
            } else {
                return "$parentCall;";
            }
        }

        if ($useMatch) {
            $body = "return match(\$key) {\n";
        } else {
            $body = "switch (\$key) {\n";
        }

        $innerBody = '';
        foreach ($map as $item) {
            // keys
            if ($useMatch) {
                $innerBody .= implode(", ", array_map(fn($k) => "\"$k\"", $item['keys']));
                $innerBody .= " => [ ";
            } else {
                $innerBody .= implode("\n", array_map(fn($k) => "case \"$k\":", $item['keys']));
                $innerBody .= "\n";
            }

            // values
            $callables = [];
            foreach ($item['callables'] as $callback) {
                if ($useMatch) {
                    $callables[] = "$callback";
                } else {
                    $callables[] = "yield $callback;";
                }
            }

            if ($useMatch) {
                $oneLineCallables = implode(", ", $callables);
                if (strlen($oneLineCallables) > 90) {
                    $innerBody .= "\n" . implode(",\n", $callables) . "\n],\n";
                } else {
                    $innerBody .= $oneLineCallables . " ],\n";
                }
            } else {
                $innerBody .= implode("\n", $callables) . "\n";
                $innerBody .= "break;\n\n";
            }
        }

        if ($hasParent) {
            if ($useMatch) {
                $innerBody .= "default => $parentCall\n";
            } else {
                $innerBody .= "default:\n$parentCall;\n";
            }
        }

        $innerBody = trim($innerBody);
        $body .= $innerBody . "\n};\n";

        return $body;
    }

    /**
     * @param Dto $object
     * @return array
     * @throws GeneratorException
     */
    protected function buildMap(Dto $object): array
    {
        $map = [];

        foreach ($object->fields as $field) {
            $callables = $this->buildCallables($field);
            if (empty($callables)) {
                continue;
            }
            foreach ($map as &$collected) {
                if ($collected['callables'] == $callables) {
                    $collected['keys'][] = $field->name;
                    continue 2;
                }
            }
            $map[] = ['keys' => [$field->name], 'callables' => $callables];
        }

        return $map;
    }

    /**
     * @param Field $field
     * @return string[]
     * @throws GeneratorException
     */
    protected function buildCallables(Field $field): array
    {
        $callables = [];
        foreach ($field->filters as $filter) {
            $callables[] = $this->buildClosure($filter);
        }
        $importer = $field->type->buildImporter($field->object->schema);
        if ($importer) {
            $callables[] = $this->buildClosure($importer);
        }
        return $callables;
    }

    /** @throws GeneratorException */
    protected function buildClosure(\Closure $closure): string
    {
        return $this->closurePrinter->print($closure);
    }
}
