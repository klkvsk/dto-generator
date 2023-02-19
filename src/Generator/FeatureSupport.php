<?php

namespace Klkvsk\DtoGenerator\Generator;

use Klkvsk\DtoGenerator\DtoGenerator;
use Klkvsk\DtoGenerator\Exception\GeneratorException;

class FeatureSupport
{
    const PHP_MIN_VERSION = '7.4';

    protected static function configs(): iterable
    {
        yield '7.4' => function () {
            DtoGenerator::$usePhpEnums = false;
            DtoGenerator::$useMixedType = false;
            DtoGenerator::$useCreatorVariadic = false;
            DtoGenerator::$usePromotedParameters = false;
            DtoGenerator::$useFirstClassCallableSyntax = false;
        };
        yield '8.0' => function () {
            DtoGenerator::$usePhpEnums = true;
            DtoGenerator::$useMixedType = true;
            DtoGenerator::$useCreatorVariadic = true;
        };
        yield '8.1' => function () {
            DtoGenerator::$usePromotedParameters = true;
            DtoGenerator::$useFirstClassCallableSyntax = true;
        };
    }

    /**
     * @throws GeneratorException
     */
    public static function setupForPhp(string $targetVersion): string
    {
        if (version_compare($targetVersion, self::PHP_MIN_VERSION) < 0) {
            throw new GeneratorException(
                sprintf(
                    'Can not build for PHP %s. Minimal supported version is %s',
                    $targetVersion,
                    self::PHP_MIN_VERSION
                )
            );
        }
        $level = self::PHP_MIN_VERSION;

        foreach (self::configs() as $minVersion => $config) {
            if (version_compare($targetVersion, $minVersion) >= 0) {
                call_user_func($config);
                $level = $minVersion;
            }
        }

        return $level;
    }
}
