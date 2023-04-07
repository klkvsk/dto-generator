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
 *
 * ---
 */
enum Genre: string
{
    case ROMANCE = 'romance';
    case COMEDY = 'comedy';
    case DRAMA = 'drama';
    case NON_FICTION = 'non-fiction';
    case SCIENTIFIC_WORK = 'scientific-work';
}
