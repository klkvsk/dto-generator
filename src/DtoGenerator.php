<?php

namespace Klkvsk\DtoGenerator;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Klkvsk\DtoGenerator\Exception\Exception;
use Klkvsk\DtoGenerator\Exception\GeneratorException;
use Klkvsk\DtoGenerator\Exception\SchemaException;
use Klkvsk\DtoGenerator\Generator\Closure;
use Klkvsk\DtoGenerator\Schema\AbstractObject;
use Klkvsk\DtoGenerator\Schema\DTO;
use Klkvsk\DtoGenerator\Schema\Enum;
use Klkvsk\DtoGenerator\Schema\Schema;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PsrPrinter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Throwable;

class DtoGenerator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public static bool $usePromotedParameters = false;
    public static bool $usePhpEnums = false;
    public static bool $useFirstClassCallableSyntax = false;


    protected Printer $printer;

    public function __construct()
    {
        $this->printer = new PsrPrinter();
        $this->logger = new NullLogger();
    }

    /**
     * @throws Exception
     */
    public function write(Schema $schema): void
    {
        $this->logger->debug("Building schema '$schema->namespace'");
        $ns = $this->build($schema);
        $outputDir = $this->getOutputDir($schema);
        $this->logger->debug("Writing schema '$schema->namespace'");
        foreach ($ns->getClasses() as $class) {
            $file = $outputDir . '/' . $class->getName() . '.php';
            $dir = dirname($file);
            if (!file_exists($dir)) {
                if (!@mkdir($dir, 0777, true)) {
                    $error = error_get_last();
                    $this->logger->error("Path '$dir' could not be created: {$error['message']}");
                    exit();
                }
            }

            assert($class instanceof EnumType || $class instanceof ClassType);
            $code = "<?php\n"
                . "declare(strict_types=1);\n\n"
                . "namespace {$ns->getName()};\n\n"
                . $this->printer->printClass($class, $ns);

            $oldCode = file_exists($file) ? file_get_contents($file) : '';
            $diff = $oldCode != $code;
            if ($diff) {
                if (!@file_put_contents($file, $code)) {
                    $error = error_get_last() ?: [ 'message' => 'unknown error'];
                    $this->logger->error("File '$file is not writable: {$error['message']}");
                    exit();
                }
            }
            $this->logger->log(
                $diff ? LogLevel::NOTICE : LogLevel::INFO,
                ($diff ? '+' : ' ') . ' ' . $class->getName() . ': ' . $file
            );
        }
    }

    public function build(Schema $schema): PhpNamespace
    {
        $ns = new PhpNamespace($schema->namespace);

        foreach ($schema->enums as $enum) {
            $this->buildEnum($ns, $enum);
        }

        foreach ($schema->dtos as $object) {
            $this->buildClass($ns, $object);
        }

        return $ns;
    }

    protected function buildEnum(PhpNamespace $ns, Enum $e): ClassType|EnumType
    {
        $this->logger->debug(" Building enum '{$ns->getName()}\\$e->name'");

        if (static::$usePhpEnums) {
            $enum = $ns->addEnum($e->name)
                ->setComment($this->buildComment($e))
                ->setType($e->backedType);
            foreach ($e->cases as $value => $case) {
                $enum->addCase($case, $value);
            }
            return $enum;
        }

        $enum = $ns->addClass($e->name)
            ->setComment($this->buildComment($e))
            ->setFinal();
        $enum->addProperty('map')->setType('array')->setStatic();
        $enum->addProperty('name')->setType('string');
        $enum->addProperty('value')->setType($e->backedType);

        $enumCtor = $enum->addMethod('__construct')
            ->setPrivate();
        $enumCtor->addParameter('name')
            ->setType('string');
        $enumCtor->addParameter('value')
            ->setType($e->backedType);
        $enumCtor
            ->addBody('$this->name = $name;')
            ->addBody('$this->value = $value;');

        $casesMethod = $enum->addMethod('cases')
            ->setComment('@return static[]')
            ->setStatic()
            ->setPublic()
            ->setReturnType('array');

        $enum
            ->addMethod('name')
            ->setPublic()
            ->setReturnType('string')
            ->setBody('return $this->name;');
        $enum
            ->addMethod('value')
            ->setPublic()
            ->setReturnType($e->backedType)
            ->setBody('return $this->value;');
        $tryFromMethod = $enum
            ->addMethod('tryFrom')
            ->setStatic()
            ->setPublic()
            ->setReturnType('self')
            ->setReturnNullable()
            ->addBody('$cases = self::cases();')
            ->addBody('return $cases[$value] ?? null;');
        $tryFromMethod
            ->addParameter('value')
            ->setType($e->backedType);

        $fromMethod = $enum
            ->addMethod('from')
            ->setStatic()
            ->setPublic()
            ->setReturnType('self')
            ->addBody('$case = self::tryFrom($value);')
            ->addBody('if (!$case) {')
            ->addBody('    throw new \ValueError(sprintf(')
            ->addBody('        "%s is not a valid backing value for enum %s",')
            ->addBody('        var_export($value, true), self::class')
            ->addBody('    ));')
            ->addBody('}')
            ->addBody('return $case;');

        $fromMethod
            ->addParameter('value')
            ->setType($e->backedType);

        $casesMap = [];
        foreach ($e->cases as $value => $case) {
            $enum->addMethod($case)
                ->setStatic()
                ->setPublic()
                ->setReturnType('self')
                ->addBody('return self::from(?);', [$value]);

            $casesMap[$value] = new Literal('new self(?, ?)', [$case, $value]);
        }

        $casesMethod->addBody('return self::$map = self::$map \?: ?;', [
            $casesMap
        ]);

        return $enum;
    }

    protected function buildClass(PhpNamespace $ns, DTO $object): ClassType
    {
        $this->logger->debug(" Building class '{$ns->getName()}\\$object->name'");

        $class = $ns->addClass($object->name);

        $class->addComment($this->buildComment($object));

        $constructor = $class->addMethod('__construct')
            ->setPublic();

        $creator = $class->addMethod('create')
            ->setStatic()
            ->setPublic()
            ->setReturnType('self')
            ->addBody('// defaults')
            ->addBody('$data += self::defaults();')
            ->addBody('')
            ->addBody('// check required')
            ->addBody('if ($diff = array_diff(array_keys($data), self::required())) {')
            ->addBody('    throw new \\InvalidArgumentException("missing keys: " . implode(", ", $diff));')
            ->addBody('}')
            ->addBody('')
            ->addBody('// process')
            ->addBody('foreach ($data as $key => &$value) {')
            ->addBody('    foreach (self::processors($key) as $type => $processor) {')
            ->addBody('        if ($value === null) break;')
            ->addBody('        if ($type === "validator" && !call_user_func($processor, $value)) {')
            ->addBody('            throw new \\InvalidArgumentException("invalid value at key: $key");')
            ->addBody('        } else {')
            ->addBody('            $value = call_user_func($processor, $value);')
            ->addBody('        }')
            ->addBody('    }')
            ->addBody('}')
            ->addBody('')
            ->addBody('// create')
            ->addBody('return new self(');

        $creator->addParameter('data')
            ->setType('array');

        $defaultsArray = [];
        $defaultsMethod = $class->addMethod('defaults')
            ->setProtected()
            ->setStatic()
            ->setReturnType('array');

        $requiredArray = [];
        $requiredMethod = $class->addMethod('required')
            ->setProtected()
            ->setStatic()
            ->setReturnType('array');

        $processorsMethod = $class->addMethod('processors')
            ->setProtected()
            ->setStatic()
            ->setReturnType('\Generator')
            ->addBody('switch ($key) {');
        $processorsMethod->addParameter('key')
            ->setType('string');

        foreach ($object->fields as $field) {
            $isNullable = !$field->required;
            $phpType = ($isNullable ? '?' : '')
                . ($field->type->sameNamespace ? '\\' . $ns->getName() : '')
                . (!in_array($field->type->phpType, [ 'int', 'bool', 'string', 'float', 'array', 'mixed' ]) ? '\\' : '')
                . $field->type->phpType;
            if ($field->type->phpTypeHint) {
                $phpTypeHint = ($isNullable ? '?' : '') . ($field->type->phpTypeHint);
            } else {
                $phpTypeHint = $phpType;
            }

            if ($field->default !== null) {
                $defaultsArray[$field->name] = $field->default;
            }
            if ($field->required) {
                $requiredArray[] = $field->name;
            }

            if (static::$usePromotedParameters) {
                $parameter = $constructor->addPromotedParameter($field->name);
                $parameter
                    ->setPublic()
                    ->setReadOnly();

                if ($field->type->phpTypeHint) {
                    $parameter->addComment("@var $phpTypeHint \$$field->name");
                }
            } else {
                $parameter = $constructor->addParameter($field->name);
                $constructor->addBody("\$this->$field->name = \$$field->name;");

                $property = $class->addProperty($field->name)
                    ->setProtected()
                    ->setNullable($isNullable)
                    ->setType($phpType);

                if ($field->default !== null || $isNullable) {
                    $property->setValue($field->default);
                }

                $getter = $class->addMethod('get' . ucfirst($field->name))
                    ->setPublic()
                    ->setReturnNullable($isNullable)
                    ->setReturnType($phpType)
                    ->setBody("return \$this->$field->name;");

                if ($field->type->phpTypeHint) {
                    $property->addComment("@var $phpTypeHint");
                    $getter->addComment("@return $phpTypeHint");
                }
            }

            $parameter
                ->setType($phpType)
                ->setNullable($isNullable);

            if ($field->default !== null || $isNullable) {
                $parameter->setDefaultValue($field->default);
            }

            if ($field->filters || $field->validators || $field->type->importer) {
                $processorsMethod->addBody('    case ?:', [ $field->name ]);
                foreach ($field->filters as $filter) {
                    $closure = new Closure($filter);
                    $processorsMethod->addBody("        yield 'filter' => $closure;");
                }
                if ($field->type->importer) {
                    $closure = new Closure($field->type->importer);
                    $processorsMethod->addBody("        yield 'importer' => $closure;");
                }
                foreach ($field->validators as $validator) {
                    $closure = new Closure($validator);
                    $processorsMethod->addBody("        yield 'validator' => $closure;");
                }
                $processorsMethod->addBody('        break;');
                $processorsMethod->addBody('');
            }

            $creator->addBody('    $data[?],', [ $field->name ]);
        }

        $creator->addBody(');');
        $processorsMethod->addBody('    default: return;');
        $processorsMethod->addBody('}');
        $defaultsMethod->addBody('return ?;', [ $defaultsArray ]);
        $requiredMethod->addBody('return ?;', [ $requiredArray ]);

        return $class;
    }

    protected function buildComment(AbstractObject $o): string
    {
        $doc = "This class is auto-generated with klkvsk/dto-generator\n"
            . "Do not modify it, any changes might be overwritten!\n";

        try {
            if ($o->declaredInFile) {
                $rootDir = realpath(InstalledVersions::getRootPackage()['install_path']);
                if ($rootDir === false) {
                    throw new GeneratorException();
                }
                if (!str_starts_with($o->declaredInFile, $rootDir)) {
                    throw new GeneratorException();
                }
                $pathRelativeToRoot = substr($o->declaredInFile, strlen($rootDir) + 1);

                $doc .= "\n@see project://$pathRelativeToRoot";
                if ($o->declaredAtLine) {
                    $doc .= " (line $o->declaredAtLine)";
                }
                $doc .= "\n";
            }
        } catch (Throwable $e) {
            $this->logger->warning($e->getMessage());
            // ignore
        }

        $doc .= "\n"
            . "@link https://github.com/klkvsk/dto-generator\n"
            . "@link https://packagist.org/klkvsk/dto-generator\n";

        return $doc;
    }

    /**
     * @throws SchemaException
     */
    protected function getOutputDir(Schema $schema): string
    {
        if ($schema->outputDir) {
            return $schema->outputDir;
        }

        foreach (ClassLoader::getRegisteredLoaders() as $classLoader) {
            $psr0Prefixes = $classLoader->getPrefixes();
            $psr4Prefixes = $classLoader->getPrefixesPsr4();
            foreach ([ 'psr0' => $psr0Prefixes, 'psr4' => $psr4Prefixes ] as $psr => $prefixesBundle) {
                foreach ($prefixesBundle as $prefixNs => $prefixes) {
                    foreach ($prefixes as $prefix) {
                        if (!str_starts_with($schema->namespace, $prefixNs)) {
                            continue;
                        }
                        $namespace = $schema->namespace;
                        if ($psr === 'psr4') {
                            $namespace = substr($namespace, strlen($prefixNs));
                        }
                        if (file_exists($prefix)) {
                            $prefix = realpath($prefix);
                        }
                        $fullPath = $prefix . DIRECTORY_SEPARATOR . $namespace;
                        $fullPathParts = preg_split('#[/\\\]#', $fullPath, -1, PREG_SPLIT_NO_EMPTY);
                        $normalizedPathParts = [ '' ];
                        foreach ($fullPathParts as $dir) {
                            if ($dir == '.') continue;
                            else if ($dir == '..') array_pop($normalizedPathParts);
                            else $normalizedPathParts []= $dir;
                        }
                        return implode(DIRECTORY_SEPARATOR, $normalizedPathParts);
                    }
                }
            }
        }

        return throw new SchemaException(
            "Output dir for '$schema->namespace' is unknown. "
            . "\nUsually it is guessed by PSR-4/PSR-0 auto-loading prefixes. "
            . "\nCheck your composer.json, or specify 'outputDir' in Schema constructor directly."
        );
    }
}