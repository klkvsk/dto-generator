<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Schema;

class Enum extends AbstractObject
{
    public readonly string $name;
    public readonly array $cases;
    public readonly ?string $backedType;

    public function __construct(
        string     $name,
        array      $cases,
        EnumValues $enumKeys = EnumValues::AUTO,
        ?string    $backedType = null,
    ) {
        parent::__construct($name);

        if (! $backedType && ($enumKeys == EnumValues::ONE || $enumKeys == EnumValues::ZERO)) {
            $backedType = 'int';
        }

        $this->cases = $enumKeys->process($cases);
        $this->backedType = $backedType;
    }
}
