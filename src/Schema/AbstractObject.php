<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema;

use Klkvsk\DtoGenerator\Exception\SchemaException;
use Klkvsk\DtoGenerator\Helpers\Cloneable;

abstract class AbstractObject
{
    use Cloneable;

    public readonly ?string $declaredInFile;
    public readonly ?int $declaredAtLine;

    public readonly ?Schema $schema;

    public function __construct(public readonly string $name)
    {
        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $originalConstructorTrace = null;
        while (! empty($traces)) {
            $trace = array_shift($traces);
            if (isset($trace['class']) && is_a($trace['class'], self::class, true)) {
                $originalConstructorTrace = $trace;
            } else {
                break;
            }
        }
        $this->declaredInFile = $originalConstructorTrace['file'] ?? null;
        $this->declaredAtLine = $originalConstructorTrace['line'] ?? null;
        $this->schema = null;
    }

    public function getNamespace(): string
    {
        $pos = strrpos($this->name, '\\');
        if ($pos === false) {
            return $this->name;
        }
        return ltrim(substr($this->name, 0, $pos), '\\');
    }

    public function getShortName(): string
    {
        $pos = strrpos($this->name, '\\');
        if ($pos === false) {
            return $this->name;
        }
        return substr($this->name, $pos + 1);
    }

    /**
     * @throws SchemaException
     */
    public function withSchema(Schema $schema): static
    {
        $name = $this->name;
        if ($name[0] !== '\\') {
            $name = '\\' . $schema->namespace . '\\' . $name;
        }
        return $this->with(schema: $schema, name: $name);
    }
}
