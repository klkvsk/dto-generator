<?php
declare(strict_types=1);

namespace Klkvsk\DtoGenerator\Generator;

use Klkvsk\DtoGenerator\DtoGenerator;
use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\Literal;
use Nette\Utils\Callback;
use Opis\Closure\ReflectionClosure;

class PrintableClosure
{
    protected string $code;

    /** @throws \ReflectionException */
    public function __construct(\Closure $closure)
    {
        $inner = Callback::unwrap($closure);
        if (Callback::isStatic($inner)) {
            $code = DtoGenerator::$useFirstClassCallableSyntax
                ? new Literal(implode('::', (array) $inner) . '(...)')
                : new Literal('\Closure::fromCallable(?)', [ $inner ]);
            $this->code = (string)$code;
            return;
        }
        $reflector = new ReflectionClosure($closure);
        $code = $reflector->getCode();
        foreach ($reflector->getUseVariables() as $key => $value) {
            if ($value instanceof \Closure) {
                $dump = new Literal((string)new static($value));
            } else {
                $dump = (new Dumper)->dump($value);
            }
            $code = preg_replace('/\$' . $key . '(?=[^a-z_0-9])/', (string)$dump, $code);
        }
        $this->code = trim($code);
    }

    public function __toString(): string
    {
        return $this->code;
    }


}