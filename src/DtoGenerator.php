<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Klkvsk\DtoGenerator\Exception\Exception;
use Klkvsk\DtoGenerator\Exception\GeneratorException;
use Klkvsk\DtoGenerator\Exception\SchemaException;
use Klkvsk\DtoGenerator\Generator\Builder\Class\CreateMethodBuilder;
use Klkvsk\DtoGenerator\Generator\Builder\Class\DefaultsMethodBuilder;
use Klkvsk\DtoGenerator\Generator\Builder\Class\ExportMethodsBuilder;
use Klkvsk\DtoGenerator\Generator\Builder\Class\ProcessorsMethodBuilder;
use Klkvsk\DtoGenerator\Generator\Builder\Class\PropertiesBuilder;
use Klkvsk\DtoGenerator\Generator\Builder\Class\RequiredMethodBuilder;
use Klkvsk\DtoGenerator\Generator\Builder\ClassBuilder;
use Klkvsk\DtoGenerator\Generator\Builder\ClassBuilderInterface;
use Klkvsk\DtoGenerator\Generator\Builder\EnumBuilderInterface;
use Klkvsk\DtoGenerator\Generator\Builder\EnumLegacyBuilder;
use Klkvsk\DtoGenerator\Generator\Builder\EnumNativeBuilder;
use Klkvsk\DtoGenerator\Schema\AbstractObject;
use Klkvsk\DtoGenerator\Schema\ExtraFieldsPolicy;
use Klkvsk\DtoGenerator\Schema\Schema;
use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
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
    public static bool $useReadonlyProperties = false;
    public static bool $usePhpEnums = false;
    public static bool $useFirstClassCallableSyntax = false;
    public static bool $useMixedType = false;
    public static bool $useCreatorVariadic = false;

    public static bool $withListTypeChecks = true;
    public static bool $withFileComments = true;
    public static bool $withCreateMethods = true;
    public static bool $withPublicDefaultMethods = false;
    public static bool $withPublicRequiredMethods = false;
    public static ExtraFieldsPolicy $withExtraFieldsPolicy = ExtraFieldsPolicy::IGNORE;

    public static bool $withJsonSerialize = true;
    public static bool $withToArray = true;

    protected readonly ClassBuilderInterface $classBuilder;
    protected readonly EnumBuilderInterface $enumBuilder;
    protected Printer $printer;

    public function __construct()
    {
        $this->printer = new PsrPrinter();
        $this->logger = new NullLogger();

        $this->classBuilder = new ClassBuilder();

        $this->classBuilder->addMembersBuilder(
            new PropertiesBuilder(
                withPromotedParameters: self::$usePromotedParameters || self::$useReadonlyProperties,
                withPublicParameters: self::$useReadonlyProperties,
                withReadonlyParameters: self::$useReadonlyProperties,
                withGetters: !self::$useReadonlyProperties,
                withListTypeChecks: self::$withListTypeChecks,
            )
        );

        if (self::$withCreateMethods || self::$withPublicDefaultMethods) {
            $this->classBuilder->addMembersBuilder(
                new DefaultsMethodBuilder(withPublicAccess: self::$withPublicDefaultMethods)
            );
        }

        if (self::$withCreateMethods || self::$withPublicRequiredMethods) {
            $this->classBuilder->addMembersBuilder(
                new RequiredMethodBuilder(withPublicAccess: self::$withPublicRequiredMethods)
            );
        }

        if (self::$withCreateMethods) {
            $this->classBuilder
                ->addMembersBuilder(
                    new ProcessorsMethodBuilder(withFirstClassCallableSyntax: self::$useFirstClassCallableSyntax)
                )
                ->addMembersBuilder(
                    new CreateMethodBuilder(
                        withCreatorVariadic: self::$useCreatorVariadic,
                        extraFieldsPolicy: self::$withExtraFieldsPolicy,
                    )
                );
        }

        $this->classBuilder
            ->addMembersBuilder(
                new ExportMethodsBuilder(
                    toArray: self::$withToArray,
                    jsonSerialize: self::$withJsonSerialize,
                    dateFormat: \DateTimeInterface::ATOM
                )
            );

        $this->enumBuilder = self::$usePhpEnums ? new EnumNativeBuilder() : new EnumLegacyBuilder();
    }

    /**
     * @throws Exception
     */
    public function write(Schema $schema): void
    {
        $this->logger->debug("Building schema '$schema->namespace'");
        $namespaces = $this->build($schema);
        $outputDir = $this->getOutputDir($schema);
        $this->logger->debug("Writing schema '$schema->namespace'");
        foreach ($namespaces as $namespace) {
            $relativeNamespace = substr($namespace->getName(), strlen($schema->namespace));
            $relativeDir = null;
            if ($relativeNamespace) {
                $relativeDir = str_replace('\\', DIRECTORY_SEPARATOR, $relativeNamespace);
            }
            foreach ($namespace->getClasses() as $class) {
                $file = $outputDir
                    . ($relativeDir ? DIRECTORY_SEPARATOR . $relativeDir : '')
                    . DIRECTORY_SEPARATOR . $class->getName() . '.php';
                $this->writeClass($namespace, $class, $file);
            }
        }
    }

    /**
     * @param PhpNamespace $ns
     * @param ClassLike $class
     * @param string $file
     * @return void
     * @throws GeneratorException
     */
    protected function writeClass(PhpNamespace $ns, ClassLike $class, string $file): void
    {
        $dir = dirname($file);
        if (!file_exists($dir)) {
            if (!@mkdir($dir, 0777, true)) {
                $error = error_get_last();

                throw new GeneratorException("Path '$dir' could not be created: {$error['message']}");
            }
        }

        assert($class instanceof EnumType || $class instanceof ClassType);

        $code = "<?php\n"
            . "declare(strict_types=1);\n\n"
            . "namespace {$ns->getName()};\n\n"
            . $this->printer->printClass($class, $ns);

        // remove trailing comas in argument lists (not supported in php<8)
        $code = preg_replace('/,(\s*\))/', '\\1', $code);

        $oldCode = file_exists($file) ? file_get_contents($file) : '';
        $diff = $oldCode != $code;
        if ($diff) {
            if (!@file_put_contents($file, $code)) {
                $error = error_get_last() ?: ['message' => 'unknown error'];
                throw new GeneratorException("File '$file is not writable: {$error['message']}");
            }
        }
        $this->logger->log(
            LogLevel::INFO,
            "class {$ns->getName()}\\{$class->getName()}: "
        );
        $this->logger->log(
            $diff ? LogLevel::NOTICE : LogLevel::INFO,
            ($diff ? '+ ' : '- ') . $file
        );
    }

    /**
     * @param Schema $schema
     * @return array|PhpNamespace[]
     */
    public function build(Schema $schema): array
    {
        $namespaces = [];

        foreach ($schema->enums as $enum) {
            $this->logger->debug(" Building enum '$enum->name'");
            $nsName = $enum->getNamespace();
            $ns = $namespaces[$nsName] ??= new PhpNamespace($nsName);
            $class = $this->enumBuilder->build($enum, $ns);
            $class->addComment($this->buildComment($enum));
        }

        foreach ($schema->dtos as $dto) {
            $this->logger->debug(" Building class '$dto->name'");
            $nsName = $dto->getNamespace();
            $ns = $namespaces[$nsName] ??= new PhpNamespace($nsName);
            $class = $this->classBuilder->build($dto, $ns);
            $class->addComment($this->buildComment($dto));
        }

        return $namespaces;
    }

    protected function buildComment(AbstractObject $o): string
    {
        if (!static::$withFileComments) {
            return '';
        }
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
            foreach (['psr0' => $psr0Prefixes, 'psr4' => $psr4Prefixes] as $psr => $prefixesBundle) {
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
                        $normalizedPathParts = [''];
                        foreach ($fullPathParts as $dir) {
                            if ($dir == '.') {
                                continue;
                            } elseif ($dir == '..') {
                                array_pop($normalizedPathParts);
                            } else {
                                $normalizedPathParts [] = $dir;
                            }
                        }

                        return implode(DIRECTORY_SEPARATOR, $normalizedPathParts);
                    }
                }
            }
        }

        throw new SchemaException(
            "Output dir for '$schema->namespace' is unknown. "
            . "\nUsually it is guessed by PSR-4/PSR-0 auto-loading prefixes. "
            . "\nCheck your composer.json, or specify 'outputDir' in Schema constructor directly."
        );
    }
}
