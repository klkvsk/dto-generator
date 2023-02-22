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
    protected int $id;
    protected string $title;
    protected Author $author;
    protected ?\DateTimeInterface $released;
    protected ?int $rating;

    /** @var list<Genre> $genres */
    protected array $genres;

    public function __construct(
        int $id,
        string $title,
        Author $author,
        ?\DateTimeInterface $released = null,
        ?int $rating = 5,
        array $genres = [],
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->author = $author;
        $this->released = $released;
        $this->rating = $rating;
        (function(Genre ...$_) {})( ...$genres);
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

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function getReleased(): ?\DateTimeInterface
    {
        return $this->released;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    /**
     * @return list<Genre>
     */
    public function getGenres(): array
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

    protected static function processors(string $key): \Generator
    {
        switch ($key) {
            case "id":
            case "rating":
                yield 'importer' => \Closure::fromCallable('intval');
                break;

            case "title":
                yield 'importer' => \Closure::fromCallable('strval');
                break;

            case "author":
                yield 'importer' => fn ($data) => call_user_func([ '\Klkvsk\DtoGenerator\Example\One\Author', 'create' ], $data);
                break;

            case "released":
                yield 'importer' => static fn ($d) => new \DateTimeImmutable($d, null);
                break;

            case "genres":
                yield 'importer' => fn ($array) => array_map(
                    fn ($data) => call_user_func([ '\Klkvsk\DtoGenerator\Example\One\Genre', 'from' ], $data),
                    (array)$array
                );
                break;
        }
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

        // process
        foreach ($data as $key => &$value) {
            foreach (static::processors($key) as $type => $processor) if ($value !== null) {
                if ($type === "validator" && call_user_func($processor, $value) === false) {
                    throw new \InvalidArgumentException("invalid value at key: $key");
                } else {
                    $value = call_user_func($processor, $value);
                }
            }
        }

        // create
        return new static(
            $data['id'],
            $data['title'],
            $data['author'],
            $data['released'],
            $data['rating'],
            $data['genres'],
        );
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

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
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
