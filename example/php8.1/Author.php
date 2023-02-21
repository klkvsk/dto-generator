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
        public readonly ?string $lastName = null,
    ) {
    }

    protected static function required(): array
    {
        return ['id', 'firstName'];
    }

    protected static function processors(string $key): \Generator
    {
        switch ($key) {
            case "id":
                yield 'importer' => intval(...);
                break;

            case "firstName":
                yield 'filter' => fn ($x) => \trim($x);
                yield 'filter' => strval(...);
                yield 'importer' => strval(...);
                break;

            case "lastName":
                yield 'filter' => fn ($x) => \trim($x);
                yield 'importer' => strval(...);
                yield 'validator' => fn ($x) => \strlen($x) > 2;
                break;
        }
    }

    public static function create(array $data): self
    {
        // check required
        if ($diff = array_diff(array_keys($data), self::required())) {
            throw new \InvalidArgumentException("missing keys: " . implode(", ", $diff));
        }

        // process
        foreach ($data as $key => &$value) {
            foreach (self::processors($key) as $type => $processor) if ($value !== null) {
                if ($type === "validator" && call_user_func($processor, $value) === false) {
                    throw new \InvalidArgumentException("invalid value at key: $key");
                } else {
                    $value = call_user_func($processor, $value);
                }
            }
        }

        // create
        return new self(...$data);
    }

    public function toArray(): array
    {
        $array = [];
        foreach (get_mangled_object_vars($this) as $var => $value) {
            $var = preg_replace("/.+\0/", "", $var);
            if (is_object($value) && method_exists($value, "toArray")) {
                $value = call_user_func([$value, "toArray"]);
            }
            $array[$var] = $value;
        }
        return $array;
    }

    public function jsonSerialize(): array
    {
        $array = [];
        foreach (get_mangled_object_vars($this) as $var => $value) {
            $var = preg_replace("/.+\0/", "", $var);
            if (is_object($value) && $value instanceof \JsonSerializable) {
                $value = $value->jsonSerialize();
            }
            $array[$var] = $value;
        }
        return $array;
    }
}