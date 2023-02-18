# Generator for DTO classes with zero runtime dependencies

## Installation

You only need this package while development, since generated classes
do not use anything from this library's code. That is why preferred way
is to include it as `require-dev`:

    $ composer require --dev klkvsk/dto-generator

## Usage

### 1. Create schema-file

Schema is a regular PHP-file anywhere in your project. 
The file should return a `Schema` object from top level.

Example schema:
```php
<?php
use Klkvsk\DtoGenerator\{Schema, DTO, Field, Type}

return new Schema(
    namespace: 'MyProject\Data',
    objects: [
        new DTO(
            name: 'Person',
            fields: [
                new Field('name', Type::string(), required: true),
                new Field('age', Type::int(), required: true),
            ]
        ),
    ]
);
```

### 2. Generate files

Code generation is done with `dto-gen` command:

```
$ ./vendor/bin/dto-gen ./schema.php
```

By default, generator will try to guess the right path for output by
looking at autoload paths in composer.json. If it defines PSR-4 mapping
for `"MyProject\\": "src/"` then the file above will be placed 
in `src/Data/Person.php`.

To override this behaviour, you can specify `outputDir` directly:

```php
new Schema(namespace: "MyProject\\Data", outputDir: "src/generated/", ...);
```

## 
