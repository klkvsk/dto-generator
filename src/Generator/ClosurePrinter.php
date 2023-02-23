<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Generator;

use Klkvsk\DtoGenerator\Exception\GeneratorException;
use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\Literal;
use Nette\Utils\Callback;
use Opis\Closure\ReflectionClosure;

class ClosurePrinter
{
    public function __construct(
        public readonly bool $useFirstClassCallableSyntax = true
    )
    {
    }

    /**
     * @throws GeneratorException
     */
    public function print(\Closure $closure): string
    {
        try {
            $inner = Callback::unwrap($closure);
            if (Callback::isStatic($inner)) {
                $code = $this->useFirstClassCallableSyntax
                    ? new Literal(implode('::', (array) $inner) . '(...)')
                    : new Literal('\Closure::fromCallable(?)', [ $inner ]);
                return (string)$code;
            }

            $reflector = new ReflectionClosure($closure);
            $code = $reflector->getCode();
            foreach ($reflector->getUseVariables() as $key => $value) {
                if ($value instanceof \Closure) {
                    $dump = $this->print($value);
                } else {
                    $dump = (new Dumper)->dump($value);
                }
                $code = preg_replace('/\$' . $key . '(?=[^a-z_0-9])/', $dump, $code);
            }

        } catch (\ReflectionException $e) {
            throw new GeneratorException('Failed to print closure', 0, $e);
        }

        return trim($code);
    }
}
