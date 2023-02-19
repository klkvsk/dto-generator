<?php

use Klkvsk\DtoGenerator\Schema as dto;
use Klkvsk\DtoGenerator\Schema\Types as t;

return dto\schema(
    namespace: 'Klkvsk\\DtoGenerator\\Example\\One',
    objects: [
        $genre = dto\enum(
            name: 'Genre',
            cases: [
                'romance',
                'comedy',
                'drama',
                'non-fiction',
            ],
            enumKeys: dto\EnumValues::CONST,
            backedType: 'string'
        ),
        $author = dto\object(
            name: 'Author',
            fields: [
                dto\field('id', t\int(), required: true),
                dto\field('firstName', t\string(), required: true,
                    filters: [ fn ($x) => trim($x), strval(...) ],
                ),
                dto\field('lastName', t\string(),
                    filters: [ fn ($x) => trim($x) ],
                    validators: [ fn ($x) => strlen($x) > 2 ]
                ),
            ]
        ),
        $book = dto\object(
            name: 'Book',
            fields: [
                dto\field('id', t\int(), required: true),
                dto\field('title', t\string(), required: true),
                dto\field('released', t\date()),
                dto\field('genre', t\enum($genre)),
                dto\field('genres', t\list_(t\enum($genre))),
                dto\field('author', t\object('Author'), required: true),
                dto\field('references', t\list_(t\object('Book'))),
                dto\field('rating', t\int(), default: 5),
                dto\field('subDto', t\object('Foo\\SubDto')),
            ]
        ),

        dto\object(
            name: 'Foo\SubDto',
            fields: [
                dto\field('id', t\int(), required: true),
                dto\field('author', t\object('Author'), required: true),
            ]
        ),
    ],
);
