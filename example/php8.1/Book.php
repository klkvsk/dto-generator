<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Example\One;

/**
 * This class is auto-generated with klkvsk/dto-generator
 * Do not modify it, any changes might be overwritten!
 *
 * @see project://example/dto.schema.php (line 38)
 *
 * @link https://github.com/klkvsk/dto-generator
 * @link https://packagist.org/klkvsk/dto-generator
 */
class Book implements \JsonSerializable
{
    /**
     * @param list<Genre> $genres
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly Author $author,
        public readonly ?\DateTimeInterface $released = null,
        public readonly ?int $rating = 5,
        public readonly array $genres = [],
    ) {
        (function(Genre ...$_) {})( ...$genres);
    }

    protected static function defaults(): array
    {
        return ['rating' => 5];
    }

    protected static function required(): array
    {
        return ['id', 'title', 'author'];
    }

    protected static function processors(string $key): \Generator
    {
        switch ($key) {
            case "id":
            case "rating":
                yield 'importer' => intval(...);
                break;

            case "title":
                yield 'importer' => strval(...);
                break;

            case "author":
                yield 'importer' => fn (array $data) => call_user_func([ '\Klkvsk\DtoGenerator\Example\One\Author', 'create' ]);
                break;

            case "released":
                yield 'importer' => static fn ($d) => new \DateTimeImmutable($d, null);
                break;

            case "genres":
                yield 'importer' => fn ($array) => array_map(
                    fn (array $data) => call_user_func([ '\Klkvsk\DtoGenerator\Example\One\Genre', 'from' ]),
                    (array)$array
                );
                break;
        }
    }

    public static function create(array $data): self
    {
        // defaults
        $data += self::defaults();

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
