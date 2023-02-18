<?php

namespace Klkvsk\DtoGenerator\Schema;

abstract class AbstractObject
{
    public readonly ?string $declaredInFile;
    public readonly ?int $declaredAtLine;

    public function __construct(
        public readonly string $name
    )
    {
        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $originalConstructorTrace = null;
        while (!empty($traces)) {
            $trace = array_shift($traces);
            if (isset($trace['class']) && $trace['class'] && is_a($trace['class'], self::class, true)) {
                $originalConstructorTrace = $trace;
            } else {
                break;
            }
        }
        $this->declaredInFile = $originalConstructorTrace['file'] ?? null;
        $this->declaredAtLine = $originalConstructorTrace['line'] ?? null;
    }
}