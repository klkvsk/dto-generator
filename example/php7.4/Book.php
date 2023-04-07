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
    protected int $id;
    protected string $title;
    protected ?\DateTimeInterface $released;
    protected Author $author;
    protected ?int $rating;

    /** @var ?array<Genre> $genres */
    protected ?array $genres;

    public function __construct(
        int $id,
        string $title,
        Author $author,
        ?\DateTimeInterface $released = null,
        ?int $rating = 5,
        ?array $genres = []
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->released = $released;
        $this->author = $author;
        $this->rating = $rating;
        $genres && (function(Genre ...$_) {})( ...$genres);
        $this->genres = $genres;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getReleased(): ?\DateTimeInterface
    {
        return $this->released;
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    /**
     * @return ?array<Genre>
     */
    public function getGenres(): ?array
    {
        return $this->genres;
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
        switch ($key) {
            case "id":
            case "rating":
                yield \Closure::fromCallable('intval');
                break;

            case "title":
                yield \Closure::fromCallable('strval');
                break;

            case "released":
                yield static fn ($d) => new \DateTimeImmutable($d);
                break;

            case "author":
                yield fn ($data) => call_user_func([ '\Klkvsk\DtoGenerator\Example\One\Author', 'create' ], $data);
                break;

            case "genres":
                yield fn ($array) => array_map(
                    fn ($data) => call_user_func([ '\Klkvsk\DtoGenerator\Example\One\Genre', 'from' ], $data),
                    (array)$array
                );
                break;
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
        return new static(
            $constructorParams["id"],
            $constructorParams["title"],
            $constructorParams["author"],
            $constructorParams["released"] ?? null,
            $constructorParams["rating"] ?? true,
            $constructorParams["genres"] ?? null
        );
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

    public function jsonSerialize(): array
    {
        $array = [];
        foreach (get_mangled_object_vars($this) as $var => $value) {
            $var = substr($var, strrpos($var, "\0") ?: 0);
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
