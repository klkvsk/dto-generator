<?php

namespace Klkvsk\DtoGenerator\Generator;

use Klkvsk\DtoGenerator\Exception\GeneratorException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;

/**
 * Virtualizer can register an in-place built scheme so autoloading will magically work.
 * Classes will be virtual in a meaning that you can use them, but they do not exist in code.
 *
 * This is a helper class intended for testing.
 * Not very helpful for anything else.
 *
 * Internally it does:
 *  1) register an autoloader to try to `include 'something://$className'`
 *  2) register a stream wrapper for `something://` that will return class code
 *
 * @example
 *
 * $ns = (new DtoGenerator)->build(
 *     new Schema(
 *         namespace: 'Test',
 *         objects: [
 *             new DTO(
 *                 name: 'Foo',
 *                 fields: [ new Field('id', Type::int()) ]
 *             )
 *         ]
 *     )
 * );
 *
 * Virtualizer::register($ns);
 *
 * $foo = new \Test\Foo(1);
 *
 */
final class Virtualizer
{
    public $context;

    protected string $code = '';
    protected int $pos = 0;

    /**
     * @var array<string, PhpNamespace>
     */
    protected static array $map = [];

    public static function register(PhpNamespace $namespace)
    {
        $key = md5($namespace->getName());
        if (in_array($key, stream_get_wrappers())) {
            throw new GeneratorException("stream wrapper '$key' is already registered");
        }

        self::$map[$key] = $namespace;

        stream_wrapper_register($key, static::class);
        spl_autoload_register(fn ($className) => require ("$key://$className"));
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        [ $key, $className ] = explode('://', $path);
        if (!array_key_exists($key, self::$map)) {
            throw new GeneratorException("virtualizer '$key' is not registered");
        }

        $namespace = self::$map[$key];

        foreach ($namespace->getClasses() as $class) {
            if ($namespace->getName() . '\\' . $class->getName() == $className) {
                if ($class instanceof ClassType || $class instanceof EnumType) {
                    $this->code = '<?php declare(strict_types=1);';
                    $this->code .= 'namespace ' . $namespace->getName() . ';';
                    $this->code .= (new PsrPrinter())->printClass($class);
                }
                break;
            }
        }

        return true;
    }

    public function stream_close()
    {
        $this->code = '';
        $this->pos = 0;
    }

    public function stream_set_option($option, $arg1, $arg2)
    {
    }

    public function stream_read($count)
    {
        $part = substr($this->code, $this->pos, $count);
        $this->pos += $count;
        return $part;
    }

    function stream_tell()
    {
        return $this->pos;
    }

    public function stream_eof()
    {
        return $this->pos >= strlen($this->code);
    }

    public function stream_stat()
    {
        return [];
    }
}