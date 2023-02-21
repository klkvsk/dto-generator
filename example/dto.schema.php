<?php

use Klkvsk\DtoGenerator\Schema as dto;
use Klkvsk\DtoGenerator\Schema\Types as t;

$outputDir = getenv('OUTPUT_DIR') ?: null;
if ($outputDir) {
    $outputDir = __DIR__ . DIRECTORY_SEPARATOR . $outputDir;
}

return dto\schema(
    namespace: 'Klkvsk\\DtoGenerator\\Example\\One',
    outputDir: $outputDir,
    objects: [
        dto\enum(
            name: 'Genre',
            cases: [
                'romance',
                'comedy',
                'drama',
                'non-fiction',
                'scientific-work'
            ]
        ),
        dto\object(
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
        dto\object(
            name: 'Book',
            fields: [
                dto\field('id', t\int(), required: true),
                dto\field('title', t\string(), required: true),
                dto\field('released', t\date()),
                dto\field('author', t\object('Author'), required: true),
                dto\field('rating', t\int(), default: 5),
                dto\field('genres', t\list_(t\enum('Genre'))),
            ]
        ),
        dto\object(
            name: 'ScienceBook',
            extends: 'Book',
            fields: [
                dto\field('references', t\list_(t\object('ScienceBook'))),
            ]
        ),
    ],
);
