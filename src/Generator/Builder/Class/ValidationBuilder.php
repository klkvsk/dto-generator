<?php

namespace Klkvsk\DtoGenerator\Generator\Builder\Class;

use Klkvsk\DtoGenerator\Generator\ClosurePrinter;
use Klkvsk\DtoGenerator\Generator\CodeStyle;
use Klkvsk\DtoGenerator\Schema\Dto;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;

class ValidationBuilder implements ClassMembersBuilderInterface
{
    const METHOD_NAME = 'validate';

    public function __construct(
        protected readonly ClosurePrinter $closurePrinter
    )
    {
    }


    public function build(Dto $object, PhpNamespace $ns, ClassType $class): void
    {
        $rules = [];
        foreach ($object->fields as $field) {
            $rulesForField = [];
            $unnamedRuleNum = 1;
            foreach ($field->validators as $validatorKey => $validatorClosure) {
                if (is_numeric($validatorKey)) {
                    $validatorKey = "#$unnamedRuleNum";
                    $unnamedRuleNum++;
                }
                $rulesForField[$validatorKey] = new Literal($this->closurePrinter->print($validatorClosure));
            }
            if (!empty($rulesForField)) {
                $rules[$field->name] = $rulesForField;
            }
        }

        if (empty($rules)) {
            return;
        }

        $this->buildMethod($class);

        $constructor = $class->getMethod('__construct');
        $constructor->addBody(
            CodeStyle::indent(
                (new Dumper())->format('$this->validate(?);', $rules)
            )
        );
    }

    public function buildMethod(ClassType $class)
    {
        $method = $class->addMethod(static::METHOD_NAME)
            ->setReturnType('void')
            ->setProtected()
            ->addBody('array_walk($rules, fn(&$vs, $f) => array_walk($vs, fn(&$v) => $v = false === call_user_func($v, $this->{$f})));')
            ->addBody('$failedRules = array_filter(array_map(fn($r) => array_keys(array_filter($r)), $rules));')
            ->addBody('if ($failedRules) throw new \InvalidArgumentException(json_encode($failedRules));');
        $method->addParameter('rules')->setType('array');
    }

    /**
     * @deprecated dont use it
     */
    private function validationMethodUnminified()
    {
        // this is just for explanation what's going on in compacted code above
        // lets say we have following rules:
        $rules = [
            'lastName' => [
                'longerThan5'   => fn($x) => strlen($x) > 5,
                'shorterThat40' => fn($x) => strlen($x) < 40,
            ]
        ];

        // walk through callables, run them, and replace with results
        array_walk(
            $rules,
            function (array &$validationClosures, string $fieldName): void
            {
                array_walk(
                    $validationClosures,
                    function (&$validationClosure) use ($fieldName)
                    {
                        if (call_user_func($validationClosure, $this->{$fieldName}) === false) {
                            $validationResult = true; // rule is considered failed if it returned 'false', raise a flag
                        } else {
                            $validationResult = false; // rule has not failed
                        }
                        // $rules is modified: replacing closure with its result
                        $validationClosure = $validationResult;
                    }
                );
            }
        );

        // lets say the lastName was 55 characters,
        // then $rules would contains [ 'lastName' => [ 'longerThan5' => false, 'shorterThan40' => true ] ]
        // which means that lastName is failed to be shorter that 40 (flag is raised)

        $failedRules = array_filter(
            array_map(
                function ($failureFlags) {
                    // filter out validations without raised flags
                    $raisedFailureFlags = array_filter($failureFlags, fn ($flag) => $flag != false);
                    // as we have validation names in keys, return them as values
                    $fieldNamesOfRaisedFlags = array_keys($raisedFailureFlags);
                    return $fieldNamesOfRaisedFlags;
                },
                $rules
            ),
            // filter out all fields without any flagged validations
            fn ($namesOfFailedRules): bool => !empty($namesOfFailedRules),
        );

        if ($failedRules) {
            // the message will be: {"lastName":["shorterThan40"]}
            throw new \InvalidArgumentException(json_encode($failedRules));
        }
    }
}
