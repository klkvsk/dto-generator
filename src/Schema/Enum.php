<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema;

class Enum extends AbstractObject
{
    protected array $cases = [];
    protected string $backedType = 'int';

    public function __construct(
        string     $name,
        array      $cases
    ) {
        parent::__construct($name);

        $isList = array_is_list($cases);
        foreach ($cases as $key => $value) {
            $this->addCase($value, $isList ? null : $key);
        }
    }

    /** @return $this */
    public function addCase(int|string $value, string $constName = null): self
    {
        if (!is_int($value) && $this->backedType === 'int') {
            // switch to string type if any value is not int
            $this->backedType = 'string';
            // cast previous to strings
            $this->cases = array_map(strval(...), $this->cases);
        }

        if ($this->backedType === 'string') {
            $value = strval($value);
        }

        if ($constName === null) {
            // camelCase to snake_case
            $constName = preg_replace_callback(
                '/([a-z\d])([A-Z])/',
                fn ($m) => strtoupper($m[1]) . '_' . $m[2],
                $value
            );
            // clean up non alpha-num chars
            $constName = preg_replace('/[^a-zA-Z\d]/', '_', $constName);
            // to uppercase
            $constName = strtoupper($constName);
        }

        if ($constName === '') {
            $constName = '__EMPTY__';
        } else if (is_numeric($constName[0])) {
            $constName = '_' . $constName;
        }
        // prefix with underscore if starts with a digit
        $constName = preg_replace('/^\d/', '_\\0', $constName);

        $this->cases[$constName] = $value;

        return $this;
    }

    /**
     * @return array<string,int|string>
     */
    public function getCases(): array
    {
        return $this->cases;
    }

    public function getBackedType(): string
    {
        return $this->backedType;
    }
}

