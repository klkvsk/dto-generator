<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Example\One;

/**
 * This class is auto-generated with klkvsk/dto-generator
 * Do not modify it, any changes might be overwritten!
 *
 * @see project://example/dto.schema.php
 *
 * @link https://github.com/klkvsk/dto-generator
 * @link https://packagist.org/klkvsk/dto-generator
 */
class Book implements \JsonSerializable
{
    /**
     * @param ?array<Genre> $genres
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly Author $author,
        #[\JetBrains\PhpStorm\Deprecated(reason: 'deprecation notice test')]
        public readonly ?\DateTimeInterface $released = null,
        public readonly ?int $rating = 5,
        public readonly ?array $genres = []
    ) {
        $genres && (function(Genre ...$_) {})( ...$genres);
    }

    protected static function defaults(): array
    {
        return ['rating' => 5];
    }

    protected static function required(): array
    {
        return ['id', 'title', 'author'];
    }

    /**
     * @return iterable<int,\Closure>
     */
    protected static function importers(string $key): iterable
    {
        return match($key) {
            "id", "rating" => [ intval(...) ],
            "title" => [ strval(...) ],
            "released" => [ static fn ($d) => new \DateTimeImmutable($d) ],
            "author" => [
                fn ($data) => call_user_func([ '\Klkvsk\DtoGenerator\Example\One\Author', 'create' ], $data)
            ],
            "genres" => [
                fn ($array) => array_map(
                    fn ($data) => call_user_func([ '\Klkvsk\DtoGenerator\Example\One\Genre', 'from' ], $data),
                    (array)$array
                )
            ],
            default => []
        };
    }

    /**
     * @return static
     */
    public static function create(array $data): self
    {
        // defaults
        $data += static::defaults();

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
        /** @psalm-suppress PossiblyNullArgument */
        return new static(...$constructorParams);
    }

    public function toArray(): array
    {
        $array = [];
        foreach (get_object_vars($this) as $var => $value) {
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d\TH:i:sP');
            }
            if (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            }
            if (class_exists(\UnitEnum::class) && $value instanceof \UnitEnum) {
                $value = $value->value;
            }
            if (is_object($value) && method_exists($value, "__toString")) {
                $value = (string)$value;
            }
            $array[$var] = $value;
        }
        return $array;
    }

    public function jsonSerialize(): array
    {
        $array = [];
        foreach (get_object_vars($this) as $var => $value) {
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d\TH:i:sP');
            }
            if ($value instanceof \JsonSerializable) {
                $value = $value->jsonSerialize();
            }
            if (class_exists(\UnitEnum::class) && $value instanceof \UnitEnum) {
                $value = $value->value;
            }
            if (is_object($value) && method_exists($value, "__toString")) {
                $value = (string)$value;
            }
            $array[$var] = $value;
        }
        return $array;
    }
}
