<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema;

class Dto extends AbstractObject
{
    /**
     * @var \ArrayObject<string, Field>
     */
    public readonly \ArrayObject $fields;

    /**
     * @param string $name
     * @param null|class-string $extends
     * @param list<class-string> $implements
     * @param list<class-string> $uses
     * @param iterable<Field> $fields
     */
    public function __construct(
        string $name,
        public readonly ?string $extends = null,
        public readonly array $implements = [],
        public readonly array $uses = [],
        iterable $fields = [],
    ) {
        parent::__construct($name);
        $this->fields = new \ArrayObject();
        foreach ($fields as $field) {
            $this->field($field);
        }
    }

    public function field(Field $field): static
    {
        $this->fields->offsetSet($field->name, $field);
        return $this;
    }

    public function findField(string $fieldName): Field
    {
        return $this->fields->offsetGet($fieldName);
    }
}
