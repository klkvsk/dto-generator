<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Console;

use Composer\InstalledVersions;
use Klkvsk\DtoGenerator\DtoGenerator;
use Klkvsk\DtoGenerator\Exception\Exception as PackageException;
use Klkvsk\DtoGenerator\Exception\GeneratorException;
use Klkvsk\DtoGenerator\Generator\FeatureSupport;
use Klkvsk\DtoGenerator\Schema\Schema;
use splitbrain\phpcli\Options;
use splitbrain\phpcli\PSR3CLIv3;

class App extends PSR3CLIv3
{
    const SCHEMA_FILE_NAMING = 'dto.schema.php';

    protected function setup(Options $options): void
    {
        $options->setHelp(
            'Generates pure DTO classes with zero runtime dependencies'
        );
        $options->registerArgument('file', 'Schema file(s)', false);
        $options->registerOption('target', 'Generate with syntax compatible with PHP >= <version>', '', 'version');

        $options->registerOption('enums', 'Generate native enums (PHP >= 8.0)', 'e', 't|f');
        $options->registerOption('promoted', 'Use promoted properties in generated classes (PHP >= 8.1)', 'p', 't|f');
        $options->registerOption('callables', 'Use first-class callable syntax for closures (PHP >= 8.1)', 'c', 't|f');
    }

    /**
     * @throws PackageException
     */
    protected function main(Options $options): void
    {
        if ($options->getOpt('help')) {
            echo $options->help();
            return;
        }

        $files = $options->getArgs();
        if (empty($files)) {
            $files = $this->findSchemaFiles();
        }
        if (empty($files)) {
            $this->notice('See --help for more info');
            return;
        }
        $phpTargetVersion = $options->getOpt('target') ?: (PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION);
        $phpMinVersion = FeatureSupport::setupForPhp($phpTargetVersion);
        $this->info('Generating code for PHP >= ' . $phpMinVersion);

        try {
            $generator = new DtoGenerator();
            foreach ($files as $file) {
                if (! file_exists($file)) {
                    throw new GeneratorException("File was not found: $file");
                }
                $schema = require $file;
                if (! $schema instanceof Schema) {
                    throw new GeneratorException("Schema was not returned from $file");
                }
                $generator->setLogger($this);
                $generator->write($schema);
            }
        } catch (PackageException $e) {
            $this->error($e->getMessage());
            exit(1);
        }
    }

    protected function findSchemaFiles(): array
    {
        $this->warning('Schema-file is not specified, looking for "' . self::SCHEMA_FILE_NAMING . '" in project path');
        $found = [];

        $projectDir = @realpath(InstalledVersions::getRootPackage()['install_path']);
        if ($projectDir) {
            $projectFiles = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $projectDir,
                    \FilesystemIterator::CURRENT_AS_PATHNAME
                )
            );
            foreach ($projectFiles as $projectFile) {
                if (str_ends_with($projectFile, self::SCHEMA_FILE_NAMING)) {
                    $this->info('... found ' . $projectFile);
                    $found[] = $projectFile;
                }
            }
        }

        if (empty($found)) {
            $this->info('... found nothing');
        }

        return $found;
    }
}
