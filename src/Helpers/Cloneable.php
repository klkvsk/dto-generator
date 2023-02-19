<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Helpers;

use Klkvsk\DtoGenerator\Exception\SchemaException;
use ReflectionClass;
use ReflectionException;

trait Cloneable
{
    /**
     * @throws SchemaException
     */
    public function with(...$values): static
    {
        try {
            $ref = (new ReflectionClass(get_class($this)));
            $clone = $ref->newInstanceWithoutConstructor();

            foreach (get_object_vars($this) as $objectField => $objectValue) {
                $objectValue = array_key_exists($objectField, $values) ? $values[$objectField] : $objectValue;

                $declarationScope = $ref->getProperty($objectField)->getDeclaringClass()->getName();
                if ($declarationScope == self::class) {
                    $clone->$objectField = $objectValue;
                } else {
                    (fn() => $this->$objectField = $objectValue)
                        ->bindTo($clone, $declarationScope)();
                }
            }

            if (method_exists($clone, '__clone')) {
                $clone = clone $clone;
            }
            return $clone;

        } catch (ReflectionException $e) {
            throw new SchemaException('Could not clone ' . get_class($this), 0, $e);
        }
    }
}
