[![Latest Version on Packagist](https://img.shields.io/packagist/v/klkvsk/dto-generator.svg?style=flat-square)](https://packagist.org/packages/klkvsk/dto-generator)
![GitHub](https://img.shields.io/github/license/klkvsk/dto-generator?style=flat-square)
![GitHub last commit](https://img.shields.io/github/last-commit/klkvsk/dto-generator?style=flat-square)
![GitHub Repo stars](https://img.shields.io/github/stars/klkvsk/dto-generator?style=social)

# Generate DTO classes with zero runtime dependencies

This package allows you to generate clean and independent DTO-classes
(also called "value objects") by briefly declared schema in PHP-code.

## Installation

You only need this package in development environment, since generated classes
do not use anything from this library's code. That is why the preferred
way is to include it as `require-dev`:

    $ composer require --dev klkvsk/dto-generator

## Requirements

- PHP 8.1+: to run generator in development
- PHP 7.4 or 8.x: to use generated code in production

## Usage

### 1. Create schema-file

Schema is a regular PHP-file anywhere in your project.
The file should return a `Schema` object from top level.

Example schema:
```php
<?php
use Klkvsk\DtoGenerator\Schema as dto;
use Klkvsk\DtoGenerator\Schema\Types as t;

return dto\schema(
    namespace: 'MyProject\Data',
    objects: [
        new dto\object(
            name: 'Person',
            fields: [
                dto\field('name', t\string(), required: true),
                dto\field('age', t\int(), required: true),
            ]
        ),
    ]
);
```

### 2. Generate files

Code generation is done with `dto-gen` command:

```
$ ./vendor/bin/dto-gen [schema-file]
```

By default, generator searches for files named or ending with `dto.schema.php`,
but you can provide schema files manually as arguments.

Generator will try to guess the right path for output by
looking at autoload paths in composer.json. If it states PSR-4 mapping
for `"MyProject\\": "src/"` then the file above will be placed
in `src/Data/Person.php`.

To override this behaviour, you can specify `outputDir` directly:

```php
dto\schema(namespace: "MyProject\\Data", outputDir: "src/generated/", ...);
```

## Features

### Back-compatibility

To generate code targeting some minimal version of PHP, use:
```
./vendor/bin/dto-gen --target 7.4
```
This option enables or disables some newer language features in the resulting code.

### Enumerations

It is possible to generate not only DTOs, but related Enums too:
```php
dto\schema(
    objects: [
        dto\enum(
            name: 'PostStatus'
            cases: [ 'draft', 'published', 'deleted' ]
        ),
        dto\object(
            name: 'Post',
            fields: [
                dto\field('status', t\enum('PostStatus'), required: true),
                ...
            ]
        )
    ]
)
```
For PHP >= 8.0 native enums will be generated,
for older versions a very similar class-based implementation is used.

### Type system

DTOs serve a purpose to keep your data strongly typed. Types for schema are:
- `t\int`, `t\bool`, `t\string`, `t\float` - basic scalar types
- `t\enum`, `t\object` - for referencing other DTO objects
- `t\date` - using DateTimeImmutable
- `t\external` - for referencing any other non-DTO classes
- `t\list_(T)` - wraps around type T to declare T[]
- `t\mixed` - if you really don't know

(where `t` is an alias to `Klkvsk\DtoGenerator\Schema\Types`)

Also, you can extend the abstract `Type` class for your needs.

### Hydration and validation

DTOs can be created with a regular constructor or with `<DTO>::create(array $data)` method.

Method `create` accepts an associative array with data.
That data is then **filtered** (if it needs some cleaning up beforehand)
and **imported** (converted to proper types).
After that, the method calls a default constructor, passing imported fields to it.
The constructor get fields **validated** not only by type, but by a custom logic.

So, there are three stages of data manipulation, each can be described in schema
with callables:

1. `filter` prepares data to be imported
2. `importer` casts value to correct type or instantiates a nested object
3. `validator` checks that imported value meets specified criteria

`filter` and `importer` closures should return processed value.
If a `null` is returned, further closures are not called.

`validator` returns true/false, and on false an `InvalidArgumentException`
is thrown automatically. Also, you can throw your own exception and don't return anything.

Closures of type `filter` and `validator` are defined in schema:
```
dto\field('age', t\int(),
    filters: [ fn ($x) => preg_replace('/[^0-9]+/', $x) ],
    validators: [ fn ($x) => $x > 0 && $x < 100 ]
)
```

Closure of type `importer` is predefined for all types except `t\object`:
```
dto\field('file', t\external(SplFileInfo::class, fn ($x) => new SplFileInfo($x))
```

You can specify a custom importer if you extend `Type` for your own needs.

## Examples

See [/example/](./example) dir for the example schema and generated classes
for different PHP versions.

## License
The MIT License (MIT). Please see [License File](./LICENSE) for more information.
