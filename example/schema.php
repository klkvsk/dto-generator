<?php

use Klkvsk\DtoGenerator\Schema\DTO;
use Klkvsk\DtoGenerator\Schema\Enum;
use Klkvsk\DtoGenerator\Schema\EnumValues;
use Klkvsk\DtoGenerator\Schema\Field;
use Klkvsk\DtoGenerator\Schema\Schema;
use Klkvsk\DtoGenerator\Schema\Type;

return new Schema(
    namespace: 'Klkvsk\\DtoGenerator\\Example\\One',
    objects: [
        $genre = new Enum(
            name: 'Genre',
            cases: [
                'romance',
                'comedy',
                'drama',
                'non-fiction',
            ],
            enumKeys: EnumValues::CONST,
            backedType: 'string'
        ),
        $author = new DTO(
            name: 'Author',
            fields: [
                new Field('id', Type::int(), required: true),
                new Field('firstName', Type::string(), required: true,
                    filters: [ fn ($x) => trim($x), \Closure::fromCallable('strval') ],
                ),
                new Field('lastName', Type::string(), required: false,
                    filters: [ fn ($x) => trim($x) ],
                    validators: [ fn ($x) => strlen($x) > 2 ]
                ),
            ]
        ),
        $book = new DTO(
            name: 'Book',
            fields: [
                new Field('id', Type::int(), required: true),
                new Field('title', Type::string(), required: true),
                new Field('released', Type::dateTime(), required: false),
                new Field('genre', Type::enum($genre), required: false),
                new Field('genres', Type::arrayOf(Type::enum($genre)), required: false),
                new Field('author', Type::dto('Book'), required: true),
                new Field('references', Type::arrayOf(Type::dto('Book'))),
                new Field('rating', Type::int(), required: false, default: 5),
            ]
        ),
    ],
);
