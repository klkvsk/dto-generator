<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Example\One;

/**
 * This class is auto-generated with klkvsk/dto-generator
 * Do not modify it, any changes might be overwritten!
 *
 * @see project://example/dto.schema.php (line 25)
 *
 * @link https://github.com/klkvsk/dto-generator
 * @link https://packagist.org/klkvsk/dto-generator
 */
class Author implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $firstName,
        public readonly ?string $lastName = null
    ) {
        $this->validate(['lastName' => ['tooShort' => fn ($x) => \strlen($x) > 5, 'tooLong' => fn ($x) => \strlen($x) < 40]]);
    }

    protected function validate(array $rules): void
    {
        array_walk($rules, fn(&$vs, $f) => array_walk($vs, fn(&$v) => $v = !call_user_func($v, $this->{$f})));
        $failedRules = array_filter(array_map(fn($r) => array_keys(array_filter($r)), $rules));
        if ($failedRules) throw new \InvalidArgumentException(json_encode($failedRules));
    }

    protected static function required(): array
    {
        return ['id', 'firstName'];
    }

    /**
     * @return callable[]
     */
    protected static function importers(string $key): iterable
    {
        return match($key) {
            "id" => [ intval(...) ],
            "firstName" => [ fn ($x) => \trim($x), strval(...), strval(...) ],
            "lastName" => [ fn ($x) => \trim($x), strval(...) ],
            default => []
        };
    }

    /**
     * @return static
     */
    public static function create(array $data): self
    {
        // check required
        if ($diff = array_diff(static::required(), array_keys($data))) {
            throw new \InvalidArgumentException("missing keys: " . implode(", ", $diff));
        }

        // import
        $constructorParams = [];
        foreach ($data as $key => $value) {
            foreach (static::importers($key) as $importer) if ($value !== null) {
                $value = call_user_func($importer, $value);
            }
            if (property_exists(static::class, $key)) {
                $constructorParams[$key] = $value;
            }
        }

        // create
        return new static(...$constructorParams);
    }

    public function toArray(): array
    {
        $array = [];
        foreach (get_mangled_object_vars($this) as $var => $value) {
            $var = preg_replace("/.+\0/", "", $var);
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d\TH:i:sP');
            }
            if (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            }
            $array[$var] = $value;
        }
        return $array;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $array = [];
        foreach (get_mangled_object_vars($this) as $var => $value) {
            $var = preg_replace("/.+\0/", "", $var);
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d\TH:i:sP');
            }
            if ($value instanceof \JsonSerializable) {
                $value = $value->jsonSerialize();
            }
            $array[$var] = $value;
        }
        return $array;
    }
}
