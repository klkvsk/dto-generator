<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Example\One;

/**
 * This class is auto-generated with klkvsk/dto-generator
 * Do not modify it, any changes might be overwritten!
 *
 * @see project://example/dto.schema.php (line 49)
 *
 * @link https://github.com/klkvsk/dto-generator
 * @link https://packagist.org/klkvsk/dto-generator
 */
class ScienceBook extends Book implements \JsonSerializable
{
    /** @var list<ScienceBook> $references */
    protected array $references;

    public function __construct(
        int $id,
        string $title,
        Author $author,
        ?\DateTimeInterface $released = null,
        ?int $rating = 5,
        array $genres = [],
        array $references = [],
    ) {
        parent::__construct($id, $title, $author, $released, $rating, $genres);
        (function(ScienceBook ...$_) {})( ...$references);
        $this->references = $references;
    }

    /**
     * @return list<ScienceBook>
     */
    public function getReferences(): array
    {
        return $this->references;
    }

    protected static function defaults(): array
    {
        return array_merge(
            method_exists(parent::class, "defaults") ? parent::defaults() : [],
            []
        );
    }

    protected static function required(): array
    {
        return array_merge(
            method_exists(parent::class, "required") ? parent::required() : [],
            []
        );
    }

    protected static function processors(string $key): \Generator
    {
        switch ($key) {
            case "references":
                yield 'importer' => fn ($array) => array_map(
                    fn ($data) => call_user_func([ '\Klkvsk\DtoGenerator\Example\One\ScienceBook', 'create' ], $data),
                    (array)$array
                );
                break;
        }
        if (method_exists(parent::class, 'processors')) {
            yield from parent::processors($key);
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
        if ($diff = array_diff(array_keys($data), static::required())) {
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
            $data['references'],
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
