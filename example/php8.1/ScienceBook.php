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
class ScienceBook extends Book
{
    /**
     * @param list<Genre> $genres
     * @param list<ScienceBook> $references
     */
    public function __construct(
        int $id,
        string $title,
        Author $author,
        ?\DateTimeInterface $released = null,
        ?int $rating = 5,
        array $genres = [],
        public readonly array $references = [],
    ) {
        parent::__construct($id, $title, $author, $released, $rating, $genres);
        (function(ScienceBook ...$_) {})( ...$references);
    }

    protected static function processors(string $key): \Generator
    {
        switch ($key) {
            case "references":
                yield 'importer' => fn ($array) => array_map(
                    fn (array $data) => call_user_func([ '\Klkvsk\DtoGenerator\Example\One\ScienceBook', 'create' ]),
                    (array)$array
                );
                break;
        }
        foreach (class_parents(self::class) as $parent) {
            if (method_exists($parent, 'processors')) {
                return call_user_func([$parent, 'processors'], $key);
            }
        }
    }

    public static function create(array $data): self
    {
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
}
