<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Example\One;

/**
 * This class is auto-generated with klkvsk/dto-generator
 * Do not modify it, any changes might be overwritten!
 *
 * @see project://example/dto.schema.php (line 15)
 *
 * @link https://github.com/klkvsk/dto-generator
 * @link https://packagist.org/klkvsk/dto-generator
 */
final class Genre implements \JsonSerializable
{
    public static array $map;
    public string $name;
    public $value;

    private function __construct(string $name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return static[]
     */
    public static function cases(): array
    {
        return self::$map = self::$map ?? [
            'romance' => new self('ROMANCE', 'romance'),
            'comedy' => new self('COMEDY', 'comedy'),
            'drama' => new self('DRAMA', 'drama'),
            'non-fiction' => new self('NON_FICTION', 'non-fiction'),
            'scientific-work' => new self('SCIENTIFIC_WORK', 'scientific-work'),
        ];
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value()
    {
        return $this->value;
    }

    public static function tryFrom($value): ?self
    {
        $cases = self::cases();
        return $cases[$value] ?? null;
    }

    public static function from($value): self
    {
        $case = self::tryFrom($value);
        if (!$case) {
            throw new \ValueError(sprintf(
                "%s is not a valid backing value for enum %s",
                var_export($value, true), self::class
            ));
        }
        return $case;
    }

    public static function ROMANCE(): self
    {
        return self::from('romance');
    }

    public static function COMEDY(): self
    {
        return self::from('comedy');
    }

    public static function DRAMA(): self
    {
        return self::from('drama');
    }

    public static function NON_FICTION(): self
    {
        return self::from('non-fiction');
    }

    public static function SCIENTIFIC_WORK(): self
    {
        return self::from('scientific-work');
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->value;
    }
}
