<?php

namespace Klkvsk\DtoGenerator\Generator;

use Klkvsk\DtoGenerator\DtoGenerator;
use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\Literal;
use Nette\Utils\Callback;
use Opis\Closure\ReflectionClosure;

class Closure
{
    protected $code;

    public function __construct(\Closure $closure)
    {
        $inner = Callback::unwrap($closure);
        if (Callback::isStatic($inner)) {
            $this->code = DtoGenerator::$useFirstClassCallableSyntax
                ? new Literal(implode('::', (array) $inner) . '(...)')
                : new Literal('\Closure::fromCallable(?)', [ $inner ]);
            return;
        }
        $reflector = new ReflectionClosure($closure);
        $code = $reflector->getCode();
        foreach ($reflector->getUseVariables() as $key => $value) {
            if ($value instanceof \Closure) {
                $dump = new Literal((string)new static($value));
            } else {
                $dump = (new Dumper())->dump($value);
            }
            $code = preg_replace('/\$' . $key . '(?=[^a-z_0-9])/', $dump, $code);
        }
        $this->code = trim($code);
    }

    public function __toString(): string
    {
        return $this->code;
    }


}